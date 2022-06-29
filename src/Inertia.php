<?php

namespace wsydney76\inertia;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\App;
use craft\web\Application;
use craft\web\View;
use wsydney76\inertia\models\Settings;
use yii\base\Event;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class Inertia extends Plugin
{

    /**
     * @inheritDoc
     */
    public function init()
    {

        parent::init();


        // Enable use of default template on the frontend
        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['inertia'] = __DIR__ . '/templates';
            }
        );

        // Don't do anything if it is not a frontend request
        if (Craft::$app->request->isSiteRequest) {
            // Unset header since at least yii\web\ErrorAction is testing it
            Craft::$app->request->headers->set('X-Requested-With', null);
            Craft::$app->on(Application::EVENT_BEFORE_REQUEST, [$this, 'applicationBeforeRequestHandler']);
            Craft::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'applicationAfterRequestHandler']);
            Craft::$app->response->on(Response::EVENT_BEFORE_SEND, [$this, 'responseBeforeSendHandler']);
        }
    }

    /**
     * Check CSRF Token (experimental)
     *
     * @param Event $event
     */
    public function applicationBeforeRequestHandler($event): void
    {
        if (!Craft::$app->config->general->enableCsrfProtection) {
            return;
        }

        $request = Craft::$app->request;
        if ($request->isPost) {
            if ($request->getCsrfTokenFromHeader() === null) {
                throw new BadRequestHttpException('Unable to verify your data submission.');
            }
        }
    }

    /**
     * Set Location header for redirects
     *
     * @param Event $event
     */
    public function applicationAfterRequestHandler($event): void
    {
        $response = Craft::$app->getResponse();
        if ($response->getHeaders()->has('X-Redirect')) {
            $url = $response->headers->get('X-Redirect', null, true);
            $response->headers->set('Location', $url);
        }
    }

    /**
     * Handle Inertia headers
     * see https://inertiajs.com/the-protocol
     *
     * @param Event $event
     */
    public function responseBeforeSendHandler($event): void
    {
        $request = Craft::$app->getRequest();
        $method = $request->getMethod();

        /** @var Response $response */
        $response = $event->sender;

        // Set fresh CSRF Token in first request
        if (!$request->headers->has('X-Inertia')) {
            if ($request->enableCsrfValidation) {
                $request->getCsrfToken(true);
            }
            return;
        }

        // XHR-Request: Return as JSON
        if ($response->isOk) {
            $response->format = Response::FORMAT_JSON;
            $response->headers->set('X-Inertia', 'true');
        }

        // Check for changed assets
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

        // Adjust Statuscode
        if ($response->getIsRedirection()) {
            if ($response->getStatusCode() === 302) {
                if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
                    $response->setStatusCode(303);
                }
            }
        }
    }

    /**
     * Get versioning finger print
     *
     * @return string
     */
    public function getVersion(): string
    {
        if (!$this->settings->useVersioning) {
            return '__noversioning__';
        }

        $hashes = [];
        foreach ($this->settings->assetsDirs as $assetDir) {
            $hashes[] = $this->hashDirectory(App::parseEnv($assetDir));
        }

        return md5(implode('', $hashes));
    }

    /**
     * Set shared props
     *
     * @param array|string $key
     * @param array/null $value
     */
    public function share(array|string $key, $value = null): void
    {
        if (is_array($key)) {
            Craft::$app->params[$this->settings->shareKey] = array_merge($this->getShared(), $key);
        } elseif (is_string($key) && is_array($value)) {
            Craft::$app->params[$this->settings->shareKey] = array_merge($this->getShared(), [$key => $value]);
        }
    }

    /**
     * Get Shared Props
     *
     * @param string|null $key
     * @return array
     */
    public function getShared(string $key = null): array
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
    private function hashDirectory(string $directory): bool|string
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

    /*
     * Plugin settings
     */
    protected function createSettingsModel(): Model
    {
        return new Settings();
    }

}
