<?php

namespace wsydney76\inertia\web;

use Craft;
use yii\web\JsonParser;

class Request extends \craft\web\Request
{

    // Set token name as expected by Inertia JS (using axios behind the scenes)

    public const CSRF_HEADER = 'X-XSRF-TOKEN';

    public $csrfParam = 'XSRF-TOKEN';

    public $csrfCookie = ['httpOnly' => false];

    public function init(): void
    {
        parent::init();

        $this->parsers['application/json'] = JsonParser::class;
    }

    /**
     * @return string|null the CSRF token sent via [[CSRF_HEADER]] by browser. Null is returned if no such header is sent.
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function getCsrfTokenFromHeader(): ?string
    {
        $token = $this->headers->get(static::CSRF_HEADER);

        $data = Craft::$app->getSecurity()->validateData($token, $this->cookieValidationKey);
        if ($data === false) {
            return null;
        }

        $data = @unserialize($data, ['allowed_classes' => false]);

        if (is_array($data) && isset($data[0], $data[1]) && $data[0] === $this->csrfParam) {
            return Craft::$app->security->maskToken($data[1]);
        }

        return null;
    }
}
