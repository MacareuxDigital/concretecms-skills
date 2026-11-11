# Overriding Classes via `overrideable_core_class()`

Concrete CMS contains a dedicated class-loading helper function: `overrideable_core_class()`. This function allows specific core classes to be automatically overridden simply by placing a subclass in the `application/src/Concrete/` directory or within an installed package's `src/Concrete/` directory.

---

## 1. How `overrideable_core_class()` Resolves Classes

When Concrete CMS resolves a class via `overrideable_core_class('Concrete\\Core\\Path\\To\\Class')`, it checks locations in the following order of precedence:

1. **Application Directory Override:**
   `Application\Concrete\Path\To\Class` (located in `application/src/Concrete/Path/To/Class.php`)
2. **Package Overrides:**
   Installed package overrides (e.g., `packages/my_package/src/Concrete/Path/To/Class.php`)
3. **Core Default:**
   `Concrete\Core\Path\To\Class` (located in `concrete/src/Path/To/Class.php`)

---

## 2. Common Overrideable Core Classes

Typical classes loaded via `overrideable_core_class()` include:

- `Concrete\Core\Page\PageList`
- `Concrete\Core\User\UserList`
- `Concrete\Core\File\FileList`
- `Concrete\Core\Antispam\Service` and custom Antispam controllers (e.g. `AkismetController`)
- `Concrete\Core\Captcha\Service` and Captcha controllers (e.g. `SecurimageController`)
- Search providers and query builders

> **Tip:** To verify if a core class supports this mechanism, search the `/concrete` source code for `overrideable_core_class('Concrete\Core\...`.

---

## 3. Overriding in the Application Directory

### Example: Overriding `Concrete\Core\Page\PageList`

1. **File Location:** `application/src/Concrete/Page/PageList.php`
2. **Namespace:** `Application\Concrete\Page`
3. **Class Implementation:**

```php
<?php

namespace Application\Concrete\Page;

use Concrete\Core\Page\PageList as CorePageList;

class PageList extends CorePageList
{
    /**
     * Add a custom filter method to PageList.
     *
     * @param string $category
     * @return $this
     */
    public function filterByCustomCategory($category)
    {
        $this->query->andWhere('cv.cvCustomCategory = :category')
            ->setParameter('category', $category);

        return $this;
    }

    /**
     * Override default sorting behavior.
     */
    public function sortByRelevance()
    {
        $this->query->orderBy('p.cDateAdded', 'desc');
    }
}
```

Whenever core code (or custom code) calls `new PageList()` via the class alias or factory, your `Application\Concrete\Page\PageList` implementation will be loaded.

---

## 4. Overriding in a Package

You can provide core class overrides from within a custom package.

### Example: Custom Antispam Controller in a Package

Suppose you have a package with handle `spam_shield` implementing an antispam controller for `akismet`:

1. **File Location:** `packages/spam_shield/src/Concrete/Antispam/AkismetController.php`
2. **Class Name & Namespace:**
   ```php
   <?php

   namespace Concrete\Package\SpamShield\Src\Concrete\Antispam;

   use Concrete\Core\Antispam\Controller as AntispamController;

   class AkismetController extends AntispamController
   {
       public function isSpam($content, $user)
       {
           // Custom spam detection logic
           return false;
       }
   }
   ```

---

## 5. Important Rules and Notes

- **Always extend the core class:** Your replacement class must inherit from the original `Concrete\Core\...` class so type hints and existing method calls remain valid.
- **Cache Clearing:** After creating an override in `application/src/Concrete/`, always clear the cache via `concrete/bin/concrete c5:clear-cache` or the Dashboard.
