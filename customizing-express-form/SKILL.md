---
name: Customizing Express Form
description: Customize the appearance of Express forms in Concrete CMS
---

# Customizing Express Form

The Express Form is a core block type in Concrete CMS. You can customize the appearance of Express Forms by creating custom templates and overriding the default form context.

## Creating a Form Context Class

To define custom template locations for Express Forms, start by creating a custom `FrontendFormContext` class. This class allows you to specify a custom attribute context.

```php
<?php

namespace Application\Express\Form\Context;

use Concrete\Core\Express\Form\Context\FrontendFormContext as CoreFrontendFormContext;

class FrontendFormContext extends CoreFrontendFormContext
{   
    public function getAttributeContext()
    {
        return new \Application\Attribute\Context\FrontendFormContext();
    }
}
```

## Applying the Custom Form Context Class

To use your custom Form Context, you must create a custom `FormController` class and register it within the context registry.

```php
<?php

namespace Application\Express\Controller;

use Application\Express\Form\Context\FrontendFormContext;
use Concrete\Core\Express\Controller\StandardController;
use Concrete\Core\Express\Form\Context\FrontendFormContext as CoreFrontendFormContext;
use Concrete\Core\Form\Context\Registry\ContextRegistry;

class FormController extends StandardController
{
    public function getContextRegistry()
    {
        return new ContextRegistry([
            CoreFrontendFormContext::class => new FrontendFormContext()
        ]);
    }
}
```

Register your custom controller as the standard controller for Express by adding the following code to your application's bootstrap or a package's `on_start` method:

```php
$app->make(\Concrete\Core\Express\Controller\Manager::class)
    ->setStandardController(\Application\Express\Controller\FormController::class);
```

## Customizing the Attribute Form

By using the custom `FrontendFormContext` class, you can define custom search paths for attribute templates.

```php
<?php

namespace Application\Attribute\Context;

use Concrete\Core\Attribute\Context\FrontendFormContext as CoreFrontendFormContext;
use Concrete\Core\Filesystem\TemplateLocator;

class FrontendFormContext extends CoreFrontendFormContext
{
    public function __construct()
    {
        parent::__construct();
        $this->preferTemplateIfAvailable('custom');
    }
    
    public function setLocation(TemplateLocator $locator)
    {
        $locator->setTemplate('custom');
        return $locator;
    }
}
```

### Template Overrides
With the configuration above, the following overrides become active:
*   `application/attributes/attribute_handle/custom.php` overrides the default `concrete/attributes/attribute_handle/form.php`.
*   `application/elements/form/custom.php` overrides the default `concrete/elements/form/bootstrap5.php`.

## Using Custom Templates in a Package
If you are developing a package, you can point the locator to your package directory in the Attribute `FrontendFormContext` class:

```php
    public function __construct()
    {
        parent::__construct();
        // Look for 'custom.php' in the package first
        $this->preferTemplateIfAvailable('custom', 'your_package_handle');
    }
    
    public function setLocation(TemplateLocator $locator)
    {
        // Enables packages/your_package_handle/elements/form/custom.php
        $locator->setTemplate(['custom', 'your_package_handle']);
        return $locator;
    }
```

Also, you can enable overriding `elements/express/form/form/form.php` in your package by adding `setLocation` method in the Express Form `FrontendFormContext` class.

```php
    public function setLocation(TemplateLocator $locator)
    {
        $locator = parent::setLocation($locator);
        $locator->prependLocation([
            DIRNAME_ELEMENTS .
            '/' .
            DIRNAME_EXPRESS .
            '/' .
            DIRNAME_EXPRESS_FORM_CONTROLS .
            '/' .
            DIRNAME_EXPRESS_FORM_CONTROLS, // not a typo
            'your_package_handle' // Package Handle
        ]);

        return $locator;
    }
```