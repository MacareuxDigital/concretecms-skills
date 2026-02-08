# Registering Routes and Service Providers

## Registering Routes

If you need to add custom routes in Concrete, better to create a route definition file.

```php
<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Application\Application $app
 * @var Concrete\Core\Routing\Router $router
 */

/*
 * Base path: /ccm/hello_world
 * Namespace: HelloWorld\API\Controller
 */

$router->get('/message', 'Message::getMessage');
```

Then, create a RouteList class to register the route definition.

```php
<?php

namespace HelloWorld\Routing;

use Concrete\Core\Routing\RouteListInterface;
use Concrete\Core\Routing\Router;

class RouteList implements RouteListInterface
{
    public function loadRoutes(Router $router)
    {
        $router->buildGroup()
            ->setPrefix('/ccm/hello_world')
            ->setNamespace('HelloWorld\API\Controller')
            ->routes('misc.php', 'hello_world') // File name and package handle
        ;
    }
}
```

The RouteList class should be loaded in a package controller.

```php
use Concrete\Core\Routing\RouterInterface;
use HelloWorld\Routing\RouteList;

...

    public function on_start()
    {
        /** @var \Concrete\Core\Routing\Router $router */
        $router = $this->app->make(RouterInterface::class);
        $router->loadRouteList(new RouteList());
    }
```

## Registering Service Providers

You can override core classes with custom ones by using the IoC container.
Also, it's a good practice to register your custom classes for reusability.
It'll be enabled to override your custom classes from the application directory for a specific project.

This is an example service provider for the "Ckeditor5" package.

```php
<?php

namespace Concrete\Package\Ckeditor5;

use Concrete\Core\Editor\CkeditorEditor;
use Concrete\Core\Foundation\Service\Provider as ServiceProvider;

class EditorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(CkeditorEditor::class, Ckeditor5Editor::class);
    }
}
```

You can register the service provider in the on_start method.

```php
use Concrete\Core\Foundation\Service\ProviderList;

...

    public function on_start()
    {
        /** @var ProviderList $list */
        $list = $this->app->make(ProviderList::class);
        $list->registerProvider(EditorServiceProvider::class);
    }
```
