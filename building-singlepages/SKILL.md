---
name: building-singlepages
description: This skill provides instructions and best practices for creating and managing single pages in Concrete CMS packages. Use this skill when you need to add custom dashboard pages, specific application logic pages, or any landing pages that don't follow the standard page type/template system.
---

# Building Single Pages in Packages

Single pages are unique pages within a Concrete CMS site that are typically used for administrative dashboards, custom application logic, or specific landing pages that don't follow the standard page type/template system.

## Creating a Single Page in a Package

### 1. File Structure

- **Controller**: `packages/your_package/controllers/single_page/your_page.php`
- **View**: `packages/your_package/single_pages/your_page.php`

The path to the single page is relative to these directories. For example, for a page at `/dashboard/my_module/settings`:
- Controller: `packages/your_package/controllers/single_page/dashboard/my_module/settings.php`
- View: `packages/your_package/single_pages/dashboard/my_module/settings.php`

### 2. Implementation

#### Controller

The controller should extend `Concrete\Core\Page\Controller\DashboardPageController` for dashboard pages, or `Concrete\Core\Page\Controller\PageController` for frontend pages.

```php
<?php
namespace Concrete\Package\YourPackage\Controller\SinglePage\Dashboard\MyModule;

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Config\Repository\Liaison;

class Settings extends DashboardPageController
{
    /**
     * Get package-specific configuration repository.
     */
    protected function getConfig(): ?Liaison
    {
        /** @var PackageService $packageService */
        $packageService = $this->app->make(PackageService::class);
        $package = $packageService->getClass('your_package_handle');
        if ($package) {
            return $package->getFileConfig();
        }

        return null;
    }

    public function view()
    {
        $config = $this->getConfig();
        $settingValue = $config->get('my_module.setting_key', 'default');
        $this->set('settingValue', $settingValue);
    }

    public function save()
    {
        if (!$this->token->validate('save_settings')) {
            $this->error->add($this->token->getErrorMessage());
        }

        if (!$this->error->has()) {
            $config = $this->getConfig();
            $config->save('my_module.setting_key', $this->post('settingValue'));
            $this->flash('success', t('Settings saved successfully.'));
            return $this->buildRedirect($this->action('view'));
        }
        
        $this->view();
    }
}
```

#### View

The view is a standard PHP file. For dashboard pages, it's typically wrapped in the dashboard theme.

```php
<?php defined('C5_EXECUTE') or die("Access Denied."); ?>

<form method="post" action="<?= $view->action('save') ?>">
    <?= $token->output('save_settings') ?>
    
    <div class="form-group">
        <?= $form->label('settingValue', t('My Setting')) ?>
        <?= $form->text('settingValue', $settingValue) ?>
    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <button class="float-end btn btn-primary" type="submit"><?= t('Save') ?></button>
        </div>
    </div>
</form>
```

### 3. Installation

In your package controller's `install()` or `upgrade()` method:

```php
use Concrete\Core\Page\Single as SinglePage;

public function install()
{
    $pkg = parent::install();
    $page = SinglePage::add('/dashboard/my_module/settings', $pkg);
    if ($page) {
        $page->update(['cName' => 'My Settings']);
    }
    return $pkg;
}
```

## Best Practices

- **Configuration**: Use `$package->getFileConfig()` to get a configuration repository specific to your package. This avoids prefixing all keys with `package.your_package_handle.`.
- **Security**: Always use CSRF tokens (`$token->output()`, `$token->validate()`) in forms.
- **Redirection**: For parent dashboard pages that should just redirect to a child, use `$this->buildRedirectToFirstAccessibleChildPage()` in the `view()` method of the parent controller and provide an empty view file. This approach ensures that the user is redirected only to a page they have permission to view.
