# Extending Data Models and Doctrine Entities

In Concrete CMS, core models such as Pages, Users, Files, and Express objects are backed by Doctrine ORM or database tables. Because of how Doctrine metadata mapping works, **you cannot simply subclass a core Doctrine Entity to override it.** Doing so creates metadata collisions and query failures.

Instead, use the following core-supported patterns to extend data models.

---

## 1. Custom Attribute Keys (Recommended for Pages, Users, Files)

Concrete CMS features an extensible Attribute system. Rather than altering core database schemas or entities, attach custom attributes to extend core objects.

### Programmatic Attribute Assignment Example

```php
use Concrete\Core\Page\Page;
use Concrete\Core\Attribute\Key\Category as AttributeKeyCategory;

$page = Page::getByID($cID);
if ($page && !$page->isError()) {
    // Set custom attribute value
    $page->setAttribute('custom_external_id', 'EXT-12345');
    
    // Retrieve custom attribute value
    $externalId = $page->getAttribute('custom_external_id');
}
```

Refer to the `building-packages` skill ([attribute_keys.md](../building-packages/references/attribute_keys.md)) for instructions on creating new attribute keys during package installation.

---

## 2. Standalone Custom Entities Linked to Core IDs

When you need relational data, complex structured records, or audit logs attached to core objects, create a standalone custom Doctrine Entity with a reference to the core ID (`cID`, `uID`, `fID`).

### Example: Custom Page Metadata Entity

```php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="CustomPageMetadata", indexes={
 *     @ORM\Index(name="idx_cID", columns={"cID"})
 * })
 */
class PageMetadata
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $cID;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $crmReference;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $lastSyncedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPageID(): int
    {
        return $this->cID;
    }

    public function setPageID(int $cID): self
    {
        $this->cID = $cID;
        return $this;
    }

    public function getCrmReference(): ?string
    {
        return $this->crmReference;
    }

    public function setCrmReference(?string $crmReference): self
    {
        $this->crmReference = $crmReference;
        return $this;
    }

    public function getLastSyncedAt(): ?\DateTime
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(\DateTime $lastSyncedAt): self
    {
        $this->lastSyncedAt = $lastSyncedAt;
        return $this;
    }
}
```

---

## 3. Hooking into Doctrine Lifecycle Events

To observe or alter database persistence operations for Doctrine entities, register a Doctrine Event Subscriber with the Entity Manager.

### Step 1: Create the Doctrine Event Subscriber

File: `application/src/Concrete/Doctrine/EventSubscriber/EntityLifecycleSubscriber.php`

```php
namespace Application\Concrete\Doctrine\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class EntityLifecycleSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::postUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // Execute pre-save logic
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // Execute post-update logic (e.g. invalidate cache, send notifications)
    }
}
```

### Step 2: Register the Doctrine Subscriber

In `application/bootstrap/app.php` or Package `on_start()`:

```php
/** @var \Doctrine\ORM\EntityManagerInterface $em */
$em = $app->make(\Doctrine\ORM\EntityManagerInterface::class);
$em->getEventManager()->addEventSubscriber($app->make(\Application\Concrete\Doctrine\EventSubscriber\EntityLifecycleSubscriber::class));
```

---

## 4. Summary of Data Model Extension Rules

- **Do NOT** create classes like `class MyCustomPageEntity extends \Concrete\Core\Entity\Page\Page` expecting Doctrine to replace the core entity.
- **Do** use Custom Attributes for simple custom fields on Pages, Users, and Files.
- **Do** use Express Objects for no-code/low-code custom data types with relationships.
- **Do** use custom standalone Doctrine Entities for complex relational or high-performance data needs.
