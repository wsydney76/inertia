<?php

namespace wsydney76\inertia\web;

use Craft;
use wsydney76\inertia\Inertia;

/**
 *
 * @property-read string $inertiaVersion
 * @property-read string $inertiaUrl
 */
class Controller extends \craft\web\Controller
{

    public array|int|bool $allowAnonymous = true;

    private ?string $only = '';

    public function beforeAction($action): bool
    {
        if (Craft::$app->request->headers->has('X-Inertia-Partial-Data')) {
            $this->only = Craft::$app->request->headers->get('X-Inertia-Partial-Data');
        }

        return true;
    }

    /**
     * @param string $component
     * @param array $params
     * @return array|string
     */
    public function inertia(string $component, array $params = []): array|string
    {
        $params = [
            'component' => $component,
            'props' => $this->getInertiaProps($params),
            'url' => $this->getInertiaUrl(),
            'version' => $this->getInertiaVersion()
        ];

        if (Craft::$app->request->headers->has('X-Inertia')) {
            return $params;
        }

        return Craft::$app->view->renderTemplate(Inertia::getInstance()->settings->view, [
            'page' => $params
        ]);
    }

    /**
     * @param array $params
     * @return array
     */
    private function getInertiaProps($params = []): array
    {
        return array_merge(
            Inertia::getInstance()->getShared(),
            $params
        );
    }

    /**
     * @return string
     */
    private function getInertiaUrl(): string
    {
        return Craft::$app->request->getUrl();
    }

    /**
     * @return string
     */
    private function getInertiaVersion(): string
    {
        return Inertia::getInstance()->getVersion();
    }

    public function checkOnly($key) {
        return in_array($key, explode(',', $this->only), true);
    }

    public function getOnly(){
        return $this->only;
    }
}
