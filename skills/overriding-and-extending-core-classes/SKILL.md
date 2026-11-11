---
name: overriding-and-extending-core-classes
description: Override and extend Concrete CMS core classes, services, controllers, elements, and views using IoC container bindings, Service Providers, overrideable_core_class, config overrides, controllers, and events.
---

# Overriding and Extending Core Classes in Concrete CMS

Concrete CMS provides multiple mechanisms to customize, override, and extend core functionality without modifying files in the `/concrete` directory. Choosing the correct approach depends on whether you are modifying a service, an overrideable utility, a controller, a view, or an event-driven workflow.

## Golden Rules

1. **Never edit files in `/concrete` directly.** Core files are replaced during upgrades.
2. **Choose the least invasive extension mechanism:**
   - Prefer **Events** when reacting to or intercepting lifecycles.
   - Prefer **IoC Container Bindings (`$app->bind()`, `$app->extend()`)** or **Service Providers** when customizing service/helper classes.
   - Use **`overrideable_core_class()`** conventions for classes explicitly designed for filesystem overrides (e.g., `PageList`, `UserList`).
   - Use **Application Controller/View hierarchy** for block controllers, single pages, views, and elements.
   - Do **NOT** attempt to override Doctrine ORM entities via direct inheritance; use Attributes, Express, or associated entities instead.
3. **Always clear the cache** after adding or modifying class overrides (`concrete/bin/concrete c5:clear-cache` or Dashboard > Clear Cache).

---

## Strategy Selection Guide

| What You Want to Customize | Recommended Mechanism | Documentation |
| --- | --- | --- |
| Service, Helper, Manager, or Utility class resolved via `$app->make()` | **IoC Container Binding** or **Service Provider** | [IoC Container & Service Providers](references/ioc_container_and_providers.md) |
| Decorate or add resolvers/handlers to existing core managers (e.g., URL resolver, Asset repository) | **Container Extension (`$app->extend()`)** | [IoC Container & Service Providers](references/ioc_container_and_providers.md) |
| Classes utilizing `overrideable_core_class()` (e.g., `PageList`, `UserList`, Antispam, Captcha) | **`Application\Concrete\...` or Package `src/Concrete/...`** | [Overrideable Core Classes](references/overrideable_core_classes.md) |
| Core Block Type behavior or default view logic | **`application/blocks/{handle}/controller.php`** | [Controllers and Views](references/controllers_and_views.md) |
| Core Single Page behavior (e.g., Login, Dashboard pages) | **`application/controllers/single_page/...`** | [Controllers and Views](references/controllers_and_views.md) |
| Core Elements or View Templates (e.g., `header_required.php`, dialogs) | **`application/elements/...` or `application/views/...`** | [Controllers and Views](references/controllers_and_views.md) |
| Reacting to system actions (page publish, user login, page view) | **PSR-14 / Symfony Event Dispatcher (`director`)** | [Events and Hooks](references/events_and_hooks.md) |
| Storing additional data on Pages, Users, Files, or Custom data | **Attributes, Express Objects, or Associated Entities** | [Doctrine & Data Extensions](references/doctrine_and_entities.md) |

---

## Quick Reference Workflows

### 1. Overriding a Service via IoC Container (`application/bootstrap/app.php` or Package `on_start`)

Custom classes placed under `application/src/Concrete/` are automatically autoloaded under the `Application\Concrete\` namespace by Concrete CMS:

```php
use Concrete\Core\Mail\Service as CoreMailService;
use Application\Concrete\Mail\CustomMailService;

// In application/bootstrap/app.php or package on_start():
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

### 2. Overriding via `overrideable_core_class()`

For core classes loaded with `overrideable_core_class()` (such as `Concrete\Core\Page\PageList`):

1. Create `application/src/Concrete/Page/PageList.php`.
2. Define the namespace as `Application\Concrete\Page`.
3. Extend the core class:

```php
namespace Application\Concrete\Page;

use Concrete\Core\Page\PageList as CorePageList;

class PageList extends CorePageList
{
    public function filterByCustomCondition($value)
    {
        $this->query->andWhere('cv.cvCustomField = :customField')
            ->setParameter('customField', $value);
    }
}
```

### 3. Hooking into Core Events

```php
use Concrete\Core\Page\Event as PageEvent;

$director = $app->make('director');
$director->addListener('on_page_view', function(PageEvent $event) {
    $page = $event->getPageObject();
    // Execute custom logic on page view
});
```

---

## Detailed References

- [IoC Container & Service Providers](references/ioc_container_and_providers.md) — Overriding services via `$app->bind()`, `$app->singleton()`, `$app->extend()`, and custom Service Providers.
- [Overrideable Core Classes](references/overrideable_core_classes.md) — Working with `overrideable_core_class()` in `application/` and packages.
- [Controllers and Views](references/controllers_and_views.md) — Overriding block controllers, single page controllers, view templates, and elements.
- [Events and Hooks](references/events_and_hooks.md) — Intercepting system actions with the PSR-14 / Symfony Event Dispatcher.
- [Doctrine & Data Extensions](references/doctrine_and_entities.md) — Handling data model extensions without breaking Doctrine ORM mappings.
