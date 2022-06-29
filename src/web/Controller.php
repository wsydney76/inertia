<?php

namespace wsydney76\inertia\web;

use Craft;
use craft\helpers\App;
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


    /*
     * Capture request for partial reload
     */
    public function beforeAction($action): bool
    {
        if (Craft::$app->request->headers->has('X-Inertia-Partial-Data')) {
            $this->only = Craft::$app->request->headers->get('X-Inertia-Partial-Data');
        }

        return true;
    }

    /**
     * @param string $view
     * @param array $params
     * @return array|string
     */
    public function render($view, $params = []): array|string
    {
        // Set params as expected in Inertia protocol
        // https://inertiajs.com/the-protocol
        $params = [
            'component' => $view,
            'props' => $this->getInertiaProps($params),
            'url' => $this->getInertiaUrl(),
            'version' => $this->getInertiaVersion()
        ];

        // XHR-Request: just return params
        if (Craft::$app->request->headers->has('X-Inertia')) {
            return $params;
        }

        // First request: Return full template
        return Craft::$app->view->renderTemplate(Inertia::getInstance()->settings->view, [
            'page' => $params
        ]);
    }

    /**
     * Merge shared props and individual request props
     *
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
     * Request URL
     *
     * @return string
     */
    private function getInertiaUrl(): string
    {
        return Craft::$app->request->getUrl();
    }

    /**
     * Asset version finger print
     *
     * @return string
     */
    private function getInertiaVersion(): string
    {
        return Inertia::getInstance()->getVersion();
    }

    /*
     * Check if prop was requested in partial reload
     */
    public function checkOnly($key): bool
    {
        return in_array($key, explode(',', $this->only), true);
    }

    /*
     * Get all props requested in partial reload (comma separated string)
     */
    public function getOnly(): ?string
    {
        return $this->only;
    }
}
