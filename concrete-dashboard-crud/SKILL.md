---
name: concrete-dashboard-crud
description: This skill provides instructions and best practices for implementing a CRUD (Create, Read, Update, Delete) interface for Doctrine entities within the Concrete CMS Dashboard, following standard core patterns.
---
# Concrete CMS Dashboard CRUD Implementation

This skill provides instructions and best practices for implementing a CRUD (Create, Read, Update, Delete) interface for Doctrine entities within the Concrete CMS Dashboard, following the standard core patterns.

## Architecture Overview

A standard CRUD interface in Concrete CMS Dashboard consists of several layers:

1.  **Doctrine Entity**: The data model.
2.  **Search Infrastructure**:
    *   **ItemList**: Extends `\Concrete\Core\Search\ItemList\Database\ItemList`. Handles querying the database.
    *   **SearchProvider**: Extends `\Concrete\Core\Search\Provider\AbstractSearchProvider`. Provides the search configuration.
    *   **ColumnSet**: Extends `\Concrete\Core\Search\ColumnSet\ColumnSet`. Defines table columns.
    *   **Result**: Extends `\Concrete\Core\Search\Result\Result`. Handles result rendering.
3.  **Dashboard Page Controller**: Extends `DashboardPageController`. Manages actions (view, add, edit, submit, delete).
4.  **UI Elements**:
    *   **Single Page View**: The main template.
    *   **Search Header**: An element for the search bar.
    *   **Header Menu**: An element for the "Add" button and pagination settings.

## 1. Search Infrastructure

### ItemList

Always extend `\Concrete\Core\Search\ItemList\Database\ItemList`. Do not use `EntityItemList` as it is rarely used in core and less flexible.

```php
namespace YourNamespace\Search\ItemList;

use Concrete\Core\Search\ItemList\Database\ItemList;
use Concrete\Core\Search\Pagination\PaginationProviderInterface;
use Pagerfanta\Doctrine\DBAL\QueryAdapter;

class YourEntityList extends ItemList implements PaginationProviderInterface
{
    protected function createQuery()
    {
        $this->query->select('e.id')->from('YourEntityTable', 'e');
        return $this->query;
    }

    public function getResult($queryRow)
    {
        $em = \ORM::entityManager();
        return $em->find('YourNamespace\Entity\YourEntity', $queryRow['id']);
    }

    public function getPaginationAdapter()
    {
        return new QueryAdapter($this->deliverQueryObject());
    }
    
    public function filterByKeywords($keywords)
    {
        $this->query->andWhere($this->query->expr()->like('e.name', $this->query->createNamedParameter('%' . $keywords . '%')));
    }
}
```

### SearchProvider

```php
namespace YourNamespace\Search\SearchProvider;

use Concrete\Core\Search\Provider\AbstractSearchProvider;
use YourNamespace\Search\ItemList\YourEntityList;
use YourNamespace\Search\ColumnSet\YourEntityColumnSet;

class YourEntitySearchProvider extends AbstractSearchProvider
{
    public function getCustomAttributeKeys() { return []; }
    public function getBaseColumnSet() { return new YourEntityColumnSet(); }
    public function getCurrentColumnSet() { return new YourEntityColumnSet(); }
    public function getDefaultColumnSet() { return new YourEntityColumnSet(); }
    
    public function getItemList()
    {
        return new YourEntityList();
    }
}
```

## 2. Dashboard Controller

The controller should manage the search state and pass necessary variables to elements.

```php
public function view()
{
    $provider = $this->app->make(YourEntitySearchProvider::class);
    $list = $provider->getItemList();

    if ($this->request->query->has('keywords')) {
        $list->filterByKeywords($this->request->query->get('keywords'));
    }

    $itemsPerPage = $this->request->query->get('itemsPerPage', 10);
    $list->setItemsPerPage($itemsPerPage);

    $result = $provider->createSearchResultObject($provider->getDefaultColumnSet(), $list);
    
    $headerMenu = $this->app->make(ElementManager::class)->get('your_package/search/menu', 'your_package_handle');
    $headerMenu->set('result', $result);
    $headerMenu->set('itemsPerPage', $itemsPerPage);
    $headerMenu->set('itemsPerPageOptions', $provider->getItemsPerPageOptions());
    $headerMenu->set('urlHelper', $this->app->make('helper/url'));

    $headerSearch = $this->app->make(ElementManager::class)->get('your_package/search/search', 'your_package_handle');
    $headerSearch->set('headerSearchAction', $this->action('view'));

    $this->set('result', $result);
    $this->set('headerMenu', $headerMenu);
    $this->set('headerSearch', $headerSearch);
}
```

## 3. UI Implementation

### Delete Confirmation

To implement a standard Concrete CMS delete confirmation dialog, use `ConcreteAlert.confirm()` in your search results table.

#### Controller

In your `view()` method, ensure the deletion action URL is available.

```php
$this->set('deleteAction', $this->action('delete'));
```

#### View Template

Add a hidden form or a `data-modal` attribute containing the confirmation form for each row.

```php
<tr data-details-url="<?= $view->action('edit', $item->getItem()->getId()) ?>">
    <?php foreach ($item->getColumns() as $column) { ?>
        <td><?= $column->getColumnValue() ?></td>
    <?php } ?>
    <td class="text-right">
        <?php
        $deleteForm = '<form method="post" action="' . $view->action('delete', $item->getItem()->getId()) . '">'
            . app('helper/validation/token')->output('delete', true)
            . t('Are you sure you want to delete this item?')
            . '</form>';
        ?>
        <button type="button" class="btn btn-danger btn-xs" 
                data-modal="<?= h($deleteForm) ?>" 
                onclick="ccm_deleteItem(this)">
            <?= t('Delete') ?>
        </button>
    </td>
</tr>

<script>
    var ccm_deleteItem = function(elem) {
        var modal = elem.getAttribute('data-modal');
        ConcreteAlert.confirm(modal, function() {
            var submitButton = document.querySelector('.ui-dialog button[data-dialog-action]');
            submitButton.disabled = true;
            var modalForm = document.querySelector('#ccm-popup-confirmation form');
            modalForm.submit();
        }, 'btn-danger', '<?= t('Delete') ?>');
    };
</script>
```

### Search Element (`search.php`)
Use `app('helper/form')` to get the form helper.

```php
<?php $form = app('helper/form'); ?>
<div class="ccm-header-search-form">
    <form method="get" action="<?= $headerSearchAction ?>">
        <div class="input-group">
            <?= $form->text('keywords', ['placeholder' => t('Search')]) ?>
            <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
        </div>
    </form>
</div>
```

### View Template
Use full-width content and standard table classes.

```php
<div class="ccm-dashboard-content-full">
    <table class="ccm-search-results-table">
        <thead>
            <tr>
                <?php foreach ($result->getColumns() as $column) { ?>
                    <th class="<?= $column->getColumnStyleClass() ?>">
                        <a href="<?= h($column->getColumnSortURL()) ?>"><?= $column->getColumnTitle() ?></a>
                    </th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($result->getItems() as $item) { ?>
                <tr data-details-url="<?= $view->action('edit', $item->getItem()->getId()) ?>">
                    <?php foreach ($item->getColumns() as $column) { ?>
                        <td><?= $column->getColumnValue() ?></td>
                    <?php } ?>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <div class="ccm-search-results-pagination">
        <?= $result->getPagination()->renderView('dashboard') ?>
    </div>
</div>
```

## Best Practices

*   **CSRF Protection**: Always use `$this->token->validate('action_name')` in `submit` and `delete` methods.
*   **Deletion Safety**: Before deleting an entity, check for references in other tables and add a descriptive error if deletion is blocked.
*   **Redirect after Post**: Always return a redirect after successful `submit` or `delete`.
*   **Coding Standards**: Run `concrete/bin/concrete c5:phpcs fix {path}` on your PHP classes.
*   **Element Manager**: Use `ElementManager` to load header search and menu elements instead of creating new `Element` objects manually.
