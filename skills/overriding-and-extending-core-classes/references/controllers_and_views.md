# Overriding Controllers, Views, and Elements

Concrete CMS uses an environment-based filesystem hierarchy. Any file in `/concrete` that corresponds to a block, single page, element, or view can be overridden by placing an equivalent file in `/application` with the corresponding namespace.

---

## 1. Overriding Core Block Type Controllers

To modify or extend the logic of a core block type (such as `content`, `autonav`, `page_list`, `form`, or `image`), place a controller in `application/blocks/{block_handle}/controller.php`.

### Namespace and Inheritance Rules
- **Path:** `application/blocks/{block_handle}/controller.php`
- **Namespace:** `Application\Block\{CamelCaseHandle}`
- **Extends:** `\Concrete\Block\{CamelCaseHandle}\Controller`

### Example: Customizing the Core Content Block Controller

File: `application/blocks/content/controller.php`

```php
<?php

namespace Application\Block\Content;

use Concrete\Block\Content\Controller as CoreContentController;

class Controller extends CoreContentController
{
    public function view()
    {
        parent::view();

        // Get the content variable set by the core controller
        $content = $this->get('content');

        // Modify or process content before rendering
        if (is_string($content)) {
            $content = str_replace('<!--custom_token-->', date('Y'), $content);
            $this->set('content', $content);
        }
    }

    public function save($data)
    {
        // Custom sanitization or pre-processing before saving
        if (isset($data['content'])) {
            $data['content'] = trim($data['content']);
        }

        parent::save($data);
    }
}
```

---

## 2. Overriding Core Block Templates

- **Default View Template Override:**
  Place `view.php` in `application/blocks/{block_handle}/view.php` to override the core block's default rendering.
- **Custom Template:**
  Place `view.php` in `application/blocks/{block_handle}/templates/{custom_template_handle}/view.php` to provide an selectable custom template (refer to the `building-block-templates` skill).

---

## 3. Overriding Single Page Controllers

To customize the controller logic for a single page (such as `/login`, `/register`, or `/dashboard/...`), place the controller file in `application/controllers/single_page/`.

### Namespace and Inheritance Rules
- **Path:** `application/controllers/single_page/{path}.php`
- **Namespace:** `Application\Controller\SinglePage\{SubNamespace}`
- **Extends:** `\Concrete\Controller\SinglePage\{SubNamespace}\{ClassName}`

### Example: Customizing the Login Controller

File: `application/controllers/single_page/login.php`

```php
<?php

namespace Application\Controller\SinglePage;

use Concrete\Controller\SinglePage\Login as CoreLoginController;

class Login extends CoreLoginController
{
    public function view($type = null)
    {
        parent::view($type);

        // Pass additional data to the login view
        $this->set('customLoginNotice', t('Please log in with your organization credentials.'));
    }

    public function authenticate($authType = null)
    {
        // Custom pre-authentication checks or logging
        return parent::authenticate($authType);
    }
}
```

### Example: Customizing a Dashboard Page Controller

File: `application/controllers/single_page/dashboard/pages/search.php`

```php
<?php

namespace Application\Controller\SinglePage\Dashboard\Pages;

use Concrete\Controller\SinglePage\Dashboard\Pages\Search as CoreSearchController;

class Search extends CoreSearchController
{
    public function view()
    {
        parent::view();

        // Modify or extend search parameters
    }
}
```

---

## 4. Overriding Single Page View Templates

Place the corresponding view file in `application/single_pages/`:

- Core: `concrete/single_pages/login.php`
- Override: `application/single_pages/login.php`

```php
<?php
defined('C5_EXECUTE') or die('Access Denied.');
?>

<div class="custom-login-wrapper">
    <?php if (isset($customLoginNotice)) { ?>
        <div class="alert alert-info"><?= h($customLoginNotice) ?></div>
    <?php } ?>

    <!-- Standard Concrete Login Form elements -->
    <?php View::element('system_errors', ['error' => $error ?? null]); ?>
    
    <form method="post" action="<?= $view->action('authenticate') ?>">
        <?= $valt->output('login') ?>
        <div class="mb-3">
            <label for="uName" class="form-label"><?= t('Username / Email') ?></label>
            <input type="text" name="uName" id="uName" class="form-control" autofocus>
        </div>
        <div class="mb-3">
            <label for="uPassword" class="form-label"><?= t('Password') ?></label>
            <input type="password" name="uPassword" id="uPassword" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary"><?= t('Log In') ?></button>
    </form>
</div>
```

---

## 5. Overriding Core Elements and Views

Concrete CMS elements (such as header meta tags, dashboard headers, search bars) and views (dialogs, panels) can be overridden by mirroring their path under `application/`:

### Core Elements
- `concrete/elements/header_required.php` $\rightarrow$ `application/elements/header_required.php`
- `concrete/elements/footer_required.php` $\rightarrow$ `application/elements/footer_required.php`
- `concrete/elements/dashboard/search.php` $\rightarrow$ `application/elements/dashboard/search.php`

### Core Views & Dialogs
- `concrete/views/dialogs/page/add.php` $\rightarrow$ `application/views/dialogs/page/add.php`
- `concrete/views/panels/page/design.php` $\rightarrow$ `application/views/panels/page/design.php`

---

## 6. Checklist When Overriding Controllers and Views

1. **Verify Exact Namespace:** Ensure `Application\Block\...` or `Application\Controller\SinglePage\...` matches the exact directory casing and structure.
2. **Call `parent::method()`:** In overridden controller methods (`view()`, `edit()`, `save()`), always call the parent method unless deliberately suppressing core behavior.
3. **Clear Cache:** Run `concrete/bin/concrete c5:clear-cache` to ensure the class loader and template loader register the new files.
