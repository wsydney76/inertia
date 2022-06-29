<?php

use craft\helpers\App;
use wsydney76\inertia\web\Request;


return [
    'components' => [

        'request' => [
            'class' => Request::class,
            'cookieValidationKey' => App::env('$SECURITY_KEY')
        ]

    ],
];
