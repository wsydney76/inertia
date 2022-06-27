<?php

namespace wsydney76\inertia;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\Application;
use craft\web\View;
use wsydney76\inertia\models\Settings;
use yii\base\Event;
use yii\web\Response;

class Inertia extends Plugin
{

    /**
     * @inheritDoc
     */
    public function init()
    {

        parent::init();

        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['inertia'] = __DIR__ . '/templates';
            }
        );

        // Unset header since at least yii\web\ErrorAction is testing it
        // Craft::$app->request->headers->set('X-Requested-With', null);
        Craft::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'applicationAfterRequestHandler']);
        Craft::$app->response->on(Response::EVENT_BEFORE_SEND, [$this, 'responseBeforeSendHandler']);
    }

    /**
     * @param Event $event
     */
    public function applicationAfterRequestHandler($event)
    {
        if (!Craft::$app->request->isConsoleRequest) {
            $response = Craft::$app->getResponse();
            if ($response->getHeaders()->has('X-Redirect')) {
                $url = $response->headers->get('X-Redirect', null, true);
                $response->headers->set('Location', $url);
            }
        }
    }

    /**
     * @param Event $event
     */
    public function responseBeforeSendHandler($event)
    {
        $request = Craft::$app->getRequest();
        $method = $request->getMethod();

        /** @var Response $response */
        $response = $event->sender;

        if (!$request->headers->has('X-Inertia')) {
            if ($request->enableCsrfValidation) {
                $request->getCsrfToken(true);
            }
            return;
        }

        if ($response->isOk) {
            $response->format = Response::FORMAT_JSON;
            $response->headers->set('X-Inertia', 'true');
        }

        if ($method === 'GET') {
            if ($request->headers->has('X-Inertia-Version')) {
                $version = $request->headers->get('X-Inertia-Version', null, true);
                if ($version !== $this->getVersion()) {
                    $response->setStatusCode(409);
                    $response->headers->set('X-Inertia-Location', $request->getAbsoluteUrl());
                    return;
                }
            }
        }

        if ($response->getIsRedirection()) {
            if ($response->getStatusCode() === 302) {
                if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
                    $response->setStatusCode(303);
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $hashes = [];
        foreach ($this->settings->assetsDirs as $assetDir) {
            $hashes[] = $this->hashDirectory(Craft::getAlias($assetDir));
        }
        return md5(implode('', $hashes));
    }

    /**
     * @param array|string $key
     * @param array/null $value
     */
    public function share($key, $value = null)
    {
        if (is_array($key)) {
            Craft::$app->params[$this->settings->shareKey] = array_merge($this->getShared(), $key);
        } elseif (is_string($key) && is_array($value)) {
            Craft::$app->params[$this->settings->shareKey] = array_merge($this->getShared(), [$key => $value]);
        }
    }

    /**
     * @param string|null $key
     * @return array
     */
    public function getShared($key = null)
    {
        $shareKey = $this->settings->shareKey;
        if (is_string($key) && isset(Craft::$app->params[$shareKey][$key])) {
            return Craft::$app->params[$shareKey][$key];
        }
        if (isset(Craft::$app->params[$shareKey])) {
            return Craft::$app->params[$shareKey];
        }
        return [];
    }

    /**
     * Generate an MD5 hash string from the contents of a directory.
     *
     * @param string $directory
     * @return boolean|string
     * @todo optimize by using webpack build info or a cache
     */
    private function hashDirectory($directory)
    {
        $files = [];
        $dir = dir($directory);
        while (($file = $dir->read()) !== false) {
            if ($file != '.' and $file != '..') {
                if (is_dir($directory . '/' . $file)) {
                    $files[] = $this->hashDirectory($directory . '/' . $file);
                } else {
                    $files[] = md5_file($directory . '/' . $file);
                }
            }
        }
        $dir->close();
        return md5(implode('', $files));
    }

    protected function createSettingsModel(): Model
    {
        return new Settings();
    }

}
