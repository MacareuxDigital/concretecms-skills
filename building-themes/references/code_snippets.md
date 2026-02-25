# Theme Development Code Snippets

## header_required with Metadata

It is recommended to pass metadata variables to the `header_required` element to ensure proper SEO and page titles, as seen in the `atomik` theme.

```php
View::element('header_required', [
    'pageTitle' => isset($pageTitle) ? $pageTitle : '',
    'pageDescription' => isset($pageDescription) ? $pageDescription : '',
    'pageMetaKeywords' => isset($pageMetaKeywords) ? $pageMetaKeywords : ''
]);
```


### `system_errors` for `view.php`

The `view.php` template is used for single pages (like the login page, dashboard pages, or custom single pages). It must include the `system_errors` element and `echo $innerContent;`.

```php
<?php
View::element('system_errors', [
    'format' => 'block',
    'error' => isset($error) ? $error : null,
    'success' => isset($success) ? $success : null,
    'message' => isset($message) ? $message : null,
]);

echo $innerContent;
?>
```
