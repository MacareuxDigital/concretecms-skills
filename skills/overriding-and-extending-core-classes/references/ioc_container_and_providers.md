# Overriding Services via IoC Container and Service Providers

The Inversion of Control (IoC) Container in Concrete CMS is the primary, cleanest way to override core services, helpers, editors, and managers. Any class instantiated via `$app->make()` or dependency injection can be swapped or decorated using the container.

---

## 1. Concrete CMS Autoloading Conventions

Before binding custom classes, understand how Concrete CMS autoloader (`Concrete\Core\Foundation\ClassAutoloader`) resolves application classes without manual `require` statements:

| Namespace Prefix | Mapped Directory | Description |
| --- | --- | --- |
| `Application\Concrete\...` | `application/src/Concrete/...` | **Default general PHP classes and services (Recommended)** |
| `Application\Entity\...` | `application/src/Entity/...` | Doctrine ORM entities |
| `Application\Block\...` | `application/blocks/...` | Block controllers (modified PSR-4) |
| `Application\Controller\...` | `application/controllers/...` | Page and single page controllers |
| `Application\Theme\...` | `application/themes/...` | Theme page controllers (`page_theme.php`) |
| `Application\Job\...` | `application/jobs/...` | Background jobs |
| `Application\Attribute\...` | `application/attributes/...` | Custom attribute type controllers |

> **Important**: If you place classes under `application/src/Concrete/` (e.g. `application/src/Concrete/Mail/CustomMailService.php`), use the namespace `Application\Concrete\Mail\CustomMailService`. It will be autoloaded automatically with zero configuration.
>
> If you want to use custom namespaces (such as `Application\Mail\...` without `Concrete`, or `MyCompany\Package\...`), you must register an autoloader in `application/bootstrap/autoload.php` (or use Composer `composer.json`, or `$pkgAutoloaderRegistries` in a package controller).

---

## 2. Container Binding Methods

You can register container bindings either in `application/bootstrap/app.php` (for site-specific overrides) or in your package controller's `on_start()` or `register()` method (for reusable packages).

### A. Basic Binding (`$app->bind()`)
Creates a new instance of your custom class each time the service is requested:

```php
use Concrete\Core\Mail\Service as CoreMailService;
use Application\Concrete\Mail\CustomMailService;

$app->bind(CoreMailService::class, CustomMailService::class);
```

File: `application/src/Concrete/Mail/CustomMailService.php`
```php
namespace Application\Concrete\Mail;

use Concrete\Core\Mail\Service as CoreMailService;

class CustomMailService extends CoreMailService
{
    public function sendMail($to, $subject, $body, $headers = '')
    {
        // Custom mail sending logic
        return parent::sendMail($to, $subject, $body, $headers);
    }
}
```

### B. Singleton Binding (`$app->singleton()`)
Maintains a single shared instance throughout the request lifecycle:

```php
use Concrete\Core\Html\Service\Navigation as CoreNavigationService;
use Application\Concrete\Html\CustomNavigationService;

$app->singleton(CoreNavigationService::class, CustomNavigationService::class);
```

### C. Contextual or Factory Closure
When instantiation requires specific dependencies or configuration:

```php
use Concrete\Core\Page\Type\Composer\Control\Type\Type as ComposerControlType;

$app->bind(ComposerControlType::class, function ($app) {
    return new \Application\Concrete\Page\Type\Composer\Control\Type\CustomType(
        $app->make(\Concrete\Core\Database\Connection\Connection::class)
    );
});
```

### D. Extending / Decorating an Existing Service (`$app->extend()`)
Modifies or enriches a service object after it is resolved without replacing the entire class:

```php
use Concrete\Core\Url\Resolver\Manager\ResolverManager;

$app->extend(ResolverManager::class, function (ResolverManager $manager, $app) {
    $manager->addResolver('custom_resolver', $app->make(\Application\Concrete\Url\CustomResolver::class));
    return $manager;
});
```

---

## 3. Using Custom Service Providers

For structured or complex overrides, encapsulate bindings in a Service Provider (`Concrete\Core\Foundation\Service\Provider`).

### Step 1: Create the Service Provider Class

File: `application/src/Concrete/Foundation/Service/Provider/CustomEditorServiceProvider.php`
```php
namespace Application\Concrete\Foundation\Service\Provider;

use Concrete\Core\Editor\CkeditorEditor;
use Concrete\Core\Foundation\Service\Provider as ServiceProvider;
use Application\Concrete\Editor\CustomCkeditorEditor;

class CustomEditorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(CkeditorEditor::class, CustomCkeditorEditor::class);
    }
}
```

### Step 2: Register the Provider

#### Option A: In `application/config/app.php`
```php
<?php

return [
    'providers' => [
        'core_editor' => \Application\Concrete\Foundation\Service\Provider\CustomEditorServiceProvider::class,
    ],
];
```

#### Option B: In a Package Controller `on_start()`
```php
namespace Concrete\Package\MyCustomPackage;

use Concrete\Core\Package\Package;
use Concrete\Core\Foundation\Service\ProviderList;
use Concrete\Package\MyCustomPackage\Src\Editor\CustomEditorServiceProvider;

class Controller extends Package
{
    protected $pkgHandle = 'my_custom_package';

    public function on_start()
    {
        /** @var ProviderList $list */
        $list = $this->app->make(ProviderList::class);
        $list->registerProvider(CustomEditorServiceProvider::class);
    }
}
```

---

## 4. Best Practices for Container Overrides

1. **Always inherit from the original core class or implement the corresponding interface:**
   ```php
   namespace Application\Concrete\Mail;

   use Concrete\Core\Mail\Service as CoreMailService;

   class CustomMailService extends CoreMailService
   {
       public function sendMail($to, $subject, $body, $headers = '')
       {
           // Pre-processing or custom delivery logic
           return parent::sendMail($to, $subject, $body, $headers);
       }
   }
   ```
2. **Match Constructor Signatures:** If you override `__construct()`, ensure all dependencies expected by parent classes or container autowiring are properly received and passed to `parent::__construct()`.
3. **Use FQCN (Fully Qualified Class Name) constants:** Always use `ClassName::class` instead of hardcoded strings (e.g. `\Concrete\Core\Mail\Service::class`).
4. **Follow Autoloading Conventions:** Place custom application classes in `application/src/Concrete/` with the `Application\Concrete\` namespace so they are resolved seamlessly.
