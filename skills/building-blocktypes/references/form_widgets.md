# Form Widgets in Block Interface

This document provides examples of how to implement common form widgets in the block's `add.php` and `edit.php` files.

## Page Selector Field

It's better to use `PageSelector` class to render page selector field.

```php
$pageSelector = app(\Concrete\Core\Form\Service\Widget\PageSelector::class);
echo $pageSelector->selectPage($fieldName, $selectedCID);
```

However, sometimes you need to initialize page selector manually, especially for repeatable fields.
Concrete provides us another way to set up page selector UI like below:

```javascript
const $el = $('div[data-field=ccm-link-selector]');
new ConcretePageSelector($el, {
    'inputName': 'links[i][cID]'
});
```

## File Selector Field

It's better to use `FileManager` class to render file selector field.

```php
$fileManager = app(\Concrete\Core\Application\Service\FileManager::class);
echo $fileManager->file($fieldID, $fieldName, t('Choose File'), $selectedFID); 
```

However, sometimes you need to initialize file chooser manually, expecially for repeatable fields.
Concrete provides us a Vue component to show file chooser UI like below:

```javascript
const $el = scope.querySelector('div[data-field=mcc-image-selector');
const fileInput = document.createElement('concrete-file-input');
fileInput.setAttribute('choose-text', "<?= t('Choose File') ?>");
fileInput.setAttribute('input-name', 'links[i][fID]');
$el.appendChild(fileInput);
Concrete.Vue.activateContext('cms', function (Vue, config) {
    new Vue({
        el: $el,
        components: config.components
    })
})
```

## Combo Box (Destination Picker)

Sometimes, you need to support multiple ways to set links. You can use `DestinationPicker` class to combine Page Selector, File Chooser, External Link.

```php
$destinationPicker = app(\Concrete\Core\Form\Service\DestinationPicker\DestinationPicker::class);
echo $destinationPicker->generate(
    'imageLink',
    $imageLinkPickers,
    $imageLinkHandle,
    $imageLinkValue
);
```

You can follow the way how feature block or hero image block use it.

However, sometimes you need to initialize a similar UI manually, especially for repeatable fields.
Concrete provides us another way to set up like below:

```php
<select class="form-select" data-field="combobox-selector" name="entries[{{i}}][linkType]">
    <option value="page" selected><?= t('Page') ?></option>
    <option value="external_url"><?= t('External URL') ?></option>
</select>
<div data-field="combobox-link" style="display: none;">
    <input type="url" class="form-control" name="entries[{{i}}][linkUrl]">
</div>
<div data-field="combobox-page" style="display: block;">
    <div data-field="page-selector-placeholder"></div>
</div>
...
<script>
function initComboBox(scope) {
    const selector = scope.querySelector('select[data-field=combobox-selector]');
    const linkField = scope.querySelector('div[data-field=combobox-link]');
    const pageField = scope.querySelector('div[data-field=combobox-page]');

    if (!selector || !linkField || !pageField) return;

    const toggle = function () {
        if (selector.value === 'external_url') {
            linkField.style.display = 'block';
            pageField.style.display = 'none';
        } else {
            linkField.style.display = 'none';
            pageField.style.display = 'block';
        }
    };

    selector.addEventListener('change', toggle);
    toggle();
}
</script>
```
