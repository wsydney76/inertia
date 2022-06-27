<?php

namespace wsydney76\inertia\models;

use craft\base\Model;

class Settings extends Model
{
    /** @var array */
    public $assetsDirs = ['@webroot/assets'];

    /** @var string */
    public $shareKey = '__inertia__';

    /** @var string */
    public $view = 'inertia/inertia.twig';
}
