# Extending Functionality via Events and Hooks

Events allow you to extend or modify core behavior without overriding classes. Concrete CMS uses a Symfony/PSR-14 compatible Event Dispatcher (`director`) to broadcast lifecycle events across the system.

---

## 1. Registering Event Listeners

Listeners can be registered in:
- `application/bootstrap/app.php` (for project-wide events)
- Package `on_start()` method (for package-encapsulated functionality)

```php
use Concrete\Core\Page\Event as PageEvent;

/** @var \Concrete\Core\Application\Application $app */
$director = $app->make('director');

$director->addListener('on_page_view', function(PageEvent $event) {
    $page = $event->getPageObject();
    if ($page && !$page->isError()) {
        // Perform actions on page view (e.g. logging, analytics, setting session data)
    }
});
```

---

## 2. Common Core Events and Payload Types

| Event Name | Event Object Class | Typical Use Case |
| --- | --- | --- |
| `on_start` | `\Symfony\Component\EventDispatcher\GenericEvent` | Early initialization, loading route lists or services |
| `on_before_render` | `\Concrete\Core\Page\Event` | Injecting global assets or view variables |
| `on_page_view` | `\Concrete\Core\Page\Event` | Page view tracking, access logging |
| `on_page_add` | `\Concrete\Core\Page\Event` | Auto-assigning attributes or page structure |
| `on_page_version_approve` | `\Concrete\Core\Page\Event` | Purging reverse proxy cache, triggering notifications |
| `on_page_delete` | `\Concrete\Core\Page\DeletePageEvent` | Cleaning up related external records |
| `on_user_login` | `\Concrete\Core\User\Event\UserLogin` | Setting custom session variables, audit logging |
| `on_user_add` | `\Concrete\Core\User\Event\UserInfoWithPassword` | Syncing new users to CRM / external directories |
| `on_file_add` | `\Concrete\Core\File\Event\File` | Auto-tagging files, resizing or scanning uploads |
| `on_block_add` / `on_block_edit` | `\Concrete\Core\Block\Event\Block` | Clearing specific block cache fragments |

---

## 3. Dedicated Event Subscriber Pattern

For maintainability, group related listeners into an Event Subscriber class implementing `\Symfony\Component\EventDispatcher\EventSubscriberInterface`.

### Step 1: Create the Subscriber Class

File: `application/src/Concrete/Event/CoreLifecycleSubscriber.php`

```php
namespace Application\Concrete\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Concrete\Core\Page\Event as PageEvent;
use Concrete\Core\User\Event\UserLogin;

class CoreLifecycleSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'on_page_version_approve' => 'onPageVersionApprove',
            'on_user_login' => 'onUserLogin',
        ];
    }

    public function onPageVersionApprove(PageEvent $event)
    {
        $page = $event->getPageObject();
        // Custom logic when a page version is approved
    }

    public function onUserLogin(UserLogin $event)
    {
        $user = $event->getUserObject();
        // Custom logic on user login
    }
}
```

### Step 2: Register the Subscriber

In `application/bootstrap/app.php` or Package `on_start()`:

```php
$director = $this->app->make('director');
$director->addSubscriber($this->app->make(\Application\Concrete\Event\CoreLifecycleSubscriber::class));
```

---

## 4. Best Practices for Events

1. **Lightweight Execution:** Keep event listeners fast. Heavy operations (e.g. sending bulk emails, third-party API sync) should be offloaded to Concrete CMS Messenger / Queue workers (`$app->make(\Concrete\Core\Messenger\MessageBus::class)`).
2. **Check for Null / Error Objects:** Always ensure the entity in the event is valid (e.g., `$page && !$page->isError()`).
3. **Avoid Infinite Loops:** If your listener for `on_page_update` updates another page attribute, pass parameters or check flags to avoid recursive triggers.
