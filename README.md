# Inertia Craft CMS Adapter

This is a Craft CMS server-side adapter for [Inertia](https://inertiajs.com).

__This is a Proof of Concept, please consider it as experimental.__

Based on the [Yii 2 server-side adapter](https://github.com/tbreuss/yii2-inertia).

[Demo project](https://github.com/wsydney76/craft_inertia)

With Inertia you are able to build single-page apps using classic server-side routing and controllers, without building an API. 

To use Inertia you need both a server-side adapter as well as a client-side adapter.
 
Be sure to follow the installation instructions for the [client-side framework](https://inertiajs.com/client-side-setup) you use.

Example setup using Vue 3, Tailwind CSS and Laravel Mix included in `examples/build`.


## Installation

Install via Composer:

```sh
composer require wsydney76/inertia
php craft plugin/install inertia
```

Edit `config/app.web.php`:

```php
<?php

use config\Env;
use wsydney76\inertia\web\Request;


return [
    'components' => [

        'request' => [
            'class' => Request::class,
            'cookieValidationKey' => 'your security key'
        ]

    ],
];

```

## Controllers

Your backend controllers should extend from `wsydney76\inertia\web\Controller`.

See examples in `examples` folder.

```php
<?php

namespace app\controllers;

use wsydney76\inertia\web\Controller;

class DemoController extends Controller
{
    public function actionIndex()
    {
        $params = [
            'data' => [],
            'links' => []
        ];
        return $this->render('demo/index', $params);
    }
}
```

## Routing

Use your Craft CMS server-side routes as usual. 
There is nothing special.

## CSRF protection

Axios is the HTTP library that Inertia uses under the hood.
Yii's CSRF protection is not optimized for Axios.

The easiest way to implement CSRF protection is using the customized `wsydney76\inertia\web\Request` component. 
Simply edit `config/app.web.php` file as shown above.

CSRF-Protection is experimental!

Please see the [security page](https://inertiajs.com/security) for more details.

### Shared data

The Craft CMS adapter provides a way to preassign shared data for each request. 
This is typically done outside of your controllers. 
Shared data will be automatically merged with the page props provided in your controller.

Massive assignment of shared data:  

```php
<?php

$shared = [
    'user' => [
        'id' => $this->getUser()->id,
        'first_name' => $this->getUser()->firstName,
        'last_name' => $this->getUser()->lastName,
    ],
    'flash' => $this->getFlashMessages(),
    'errors' => $this->getFormErrors(),
    'filters' => $this->getGridFilters()
];
Inertia::getInstance()->share($shared);
```

Shared data for one key:

```php
<?php

$user = [
    'id' => $this->getUser()->id,
    'first_name' => $this->getUser()->firstName,
    'last_name' => $this->getUser()->lastName
];
Inertia::getInstance()->share('user', $user);
```

A good strategy when using shared data outside of your controllers is to implement an action filter.

```php
<?php

namespace modules\frontend\components;

use yii\base\ActionFilter;

class SharedDataFilter extends ActionFilter
{
    public function beforeAction()
    {
        $shared = [
            'user' => $this->getUser(),
            'flash' => $this->getFlashMessages(),
            'errors' => $this->getFormErrors()
        ];
        Inertia::getInstance()->share($shared);
        return true;
    }
}    
```

And then use this action filter as a behaviour in your controller.

```php
<?php

namespace modules\frontend\controllers;

use app\components\SharedDataFilter;
use wsydney76\inertia\web\Controller;

class ContactController extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => SharedDataFilter::class
            ]
        ];
    }
    
    public function actionIndex()
    {
        // your action code
    }
}
```

You can also extend your controllers from a BaseController that implements a `beforeAction` method to set shared data. See `examples`.

Please see the [shared data page](https://inertiajs.com/shared-data) for more details.

## Partial reloads

XHR requests from inertia may include an `only` parameter (comma separated string) that indicates which props should be sent back.

See [docs](https://inertiajs.com/partial-reloads).

You can check for this in your controller via `$this->getOnly()` or `$this->checkOnly('prop')`

## Settings

Create a `config/inertia.php` file.

Example content: 

```php
<?php

return [
    'view' => 'frontend/main.twig',
    'assetsDirs' => [
        '@webroot/assets/inertia'
    ],
    'useVersioning' => false
];
```

Possible settings:

**view** 

The twig template used to render the inital request.

Includes the div the inertia app will be rendered to:

`<div id="app" data-page="{{ page|json_encode }}"></div>`

and calls the Inertia app:

`<script src="<path_to_app>/app.js"></script>`

Defaults to bundled `templates/inertia.twig`.

**useVersioning**

Whether Inertia's assets versioning shall be used. Set to false if this is already handled in your build process.

Defaults to `true`.

**assetsDirs**

Array of directories that will be checked for changed assets if `useVersioning = true`. Supports environment variables and aliases.

Defaults to `['@webroot/assets']`

**shareKey**

Internal key for shared props. No need to change this.

## Client-side setup

To use Inertia you need to setup your client-side framework. 
This primarily includes updating your main JavaScript file to boot the Inertia app. 
Please see the [client-side setup page](https://inertiajs.com/client-side-setup) for more details.

## More about Inertia

Visit [inertiajs.com](https://inertiajs.com/) to learn more.
