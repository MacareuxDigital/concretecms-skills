---
name: building-block-templates
description: Create custom templates for Concrete CMS block types. Use this skill when you need to change the markup of an existing block type without modifying its core files.
---

# Building Block Templates

Custom templates allow you to override the default view of a block type. This is useful for styling blocks to match your theme or adding specific functionality.

## Basic Workflow: Static Markup to Custom Template

1.  **Identify the Block Type**: Determine which block type you want to create a template for (e.g., `autonav`, `content`, `image`).
2.  **Find the Default Template**:
    - Locate the original `view.php` for the block. Usually in `concrete/blocks/block_handle/view.php`.
    - **DO NOT** modify this file.
3.  **Create the Custom Template File**:
    - Create a new file in your theme or package:
        - Theme: `application/themes/your_theme/blocks/block_handle/templates/your_custom_template_name.php`
        - Package: `packages/your_package/blocks/block_handle/templates/your_custom_template_name.php`
4.  **Insert Static Markup**: Paste your static HTML markup into the new file.
5.  **Inject Dynamic Data**:
    - Open the original `view.php` you found in step 2.
    - Identify the variables used (e.g., `$controller`, `$bID`, `$content`).
    - Replace the static parts of your markup with the PHP code from the default template to output the actual block data.
    - Ensure you include the mandatory header: `defined('C5_EXECUTE') or die('Access Denied.');`

## Custom Template Naming Rules

- **Format**: Use `snake_case` (e.g., `feature_grid`, `hero_banner`).
- **Descriptive**: Choose a name that clearly describes the purpose or appearance (e.g., `simple_list` instead of `template1`).
- **Avoid Conflicts**: Do not use `view` as a template name, as it is reserved for the default view.
- **Display Name**: Concrete CMS will automatically convert the filename to a readable name in the UI (e.g., `feature_grid.php` becomes "Feature Grid").

## Basic Checklist for Block Templates

- [ ] **Access Check**: Does the file start with `defined('C5_EXECUTE') or die('Access Denied.');`?
- [ ] **XSS Prevention**: Are you escaping data correctly? Use the shorthand `<?= h($variable) ?>` for **ALL** output that shouldn't contain trusted HTML. Even if you think the data is safe, it's a best practice to use `h()` to prevent potential XSS vulnerabilities.
- [ ] **Localization**: Are strings wrapped in the translation function? Use `<?= t('Your String') ?>`.
- [ ] **Edit Mode Support**: Does the block still look/work correctly when the page is in edit mode? Sometimes you need to wrap JS-heavy templates in a check: `if (!$c->isEditMode()) { ... }`.
- [ ] **Asset Loading**: If the template requires specific CSS or JS, are they being required properly via the controller or by adding them to the template directory (Concrete will auto-load `view.css` and `view.js` if they are in the template folder)?
- [ ] **Empty State**: Does the template handle empty data gracefully?

