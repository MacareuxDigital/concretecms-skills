# Pagination in Blocks

This document provides examples of how to implement custom pagination in Concrete CMS blocks using Pagerfanta.

## Custom Pagerfanta View Template

Example Pagerfanta view template class:

```php
<?php

namespace Concrete\Package\Example\Search\Pagination\View;

class CustomTemplate extends \Pagerfanta\View\Template\Template
{
    /**
     * @return string
     */
    public function container()
    {
        return sprintf(
            '<div class="%s">%%pages%%</div>',
            $this->option('css_container_class')
        );
    }

    /**
     * @param int $page
     * @return string
     */
    public function page($page)
    {
        return $this->pageWithText($page, (string)$page);
    }

    /**
     * @param int $page
     * @param string $text
     * @return string
     */
    public function pageWithText($page, $text)
    {
        return sprintf(
            '<a href="%s" class="%s">%s</a>',
            $this->generateRoute($page),
            $this->option('css_page_class'),
            $text
        );
    }

    /**
     * @return string
     */
    public function previousDisabled()
    {
        return '';
    }

    /**
     * @param int $page
     * @return string
     */
    public function previousEnabled($page)
    {
        return sprintf(
            '<a href="%s" class="%s">%s</a>',
            $this->generateRoute($page),
            $this->option('css_page_class'),
            $this->option('prev_message')
        );
    }

    /**
     * @return string
     */
    public function nextDisabled()
    {
        return '';
    }

    /**
     * @param int $page
     * @return string
     */
    public function nextEnabled($page)
    {
        return sprintf(
            '<a href="%s" class="%s">%s</a>',
            $this->generateRoute($page),
            $this->option('css_page_class'),
            $this->option('next_message')
        );
    }

    /**
     * @return string
     */
    public function first()
    {
        return $this->page(1);
    }

    /**
     * @param int $page
     * @return string
     */
    public function last($page)
    {
        return $this->page($page);
    }

    /**
     * @param int $page
     * @return string
     */
    public function current($page)
    {
        return sprintf(
            '<span class="%s">%s</span>',
            $this->option('css_current_class'),
            $page
        );
    }

    /**
     * @return string
     */
    public function separator()
    {
        return sprintf(
            '<span class="%s">%s</span>',
            $this->option('css_dots_class'),
            $this->option('dots_message')
        );
    }
}
```

## Custom Pagination View Class

Example Pagination View class:

```php
<?php

namespace Concrete\Package\Example\Search\Pagination\View;

use Concrete\Core\Search\Pagination\View\ViewInterface;
use Pagerfanta\View\Template\TemplateInterface;
use Pagerfanta\View\TemplateView;

class CustomView extends TemplateView implements ViewInterface
{
    /**
     * @return TemplateInterface
     */
    protected function createDefaultTemplate()
    {
        return new CustomTemplate();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'custom';
    }

    /**
     * @return string[]
     */
    public function getArguments()
    {
        return [
            'css_container_class' => 'container mx-auto flex justify-center space-x-2 my-16',
            'css_page_class' => 'px-4 py-2 bg-gray-300 rounded',
            'css_current_class' => 'px-4 py-2 bg-blue-500 text-white rounded',
            'prev_message' => '«',
            'next_message' => '»',
            'dots_message' => '...',
        ];
    }
}
```

## Pagination View Manager

Example Pagination View Manager class:

```php
<?php

namespace Concrete\Package\Example\Search\Pagination\View;

use Concrete\Core\Search\Pagination\View\Manager as CoreManager;

class Manager extends CoreManager
{
    protected function createApplicationDriver()
    {
        return new CustomView();
    }
}
```

## Registering the Pagination View Manager

Example of how to register the custom Pagination View Manager (usually in a package controller or `app.php`):

```php
$app->bind('manager/view/pagination', \Concrete\Package\Example\Search\Pagination\View\Manager::class);
```
