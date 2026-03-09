---
name: building-blocktypes
description: Create and manage Concrete CMS block types. Use this skill when the user asks to create or modify custom block types.
---

# Building Block Types

Block types are the fundamental units of content in Concrete CMS. They allow users to add different types of content (text, images, forms, etc.) to pages.

## Basic Workflow for Block Type Development

1.  **Define the Block Type Directory**:
    - If in a package: `packages/your_package/blocks/block_handle/`
    - If in application: `application/blocks/block_handle/`
2.  **Create the Controller**:
    - Create `controller.php` in the block directory.
3.  **Create the View**:
    - Create `view.php` for the public output.
4.  **Create Add/Edit Forms**:
    - Create `add.php` and `edit.php` for the dashboard interface.
5.  **Define Database Schema**:
    - Create `db.xml` (Doctrine XML format) to define the block's data table.
    - Refer to the `working-with-database` skill for the XML format.
6.  **Register the Block Type**:
    - Install via Dashboard or programmatically in a package `install()` method.
7.  **Update Package Version** (if the block type belongs to a package):
    - Increment `$pkgVersion` in the package's `controller.php`.
    - The version bump is required for the `upgrade()` method to run and install the new block type on existing sites.

## Reference Documentation

- [Form Widgets (Page/File Selector, Combo Box)](references/form_widgets.md)
- [Custom Pagination with Pagerfanta](references/pagination.md)

