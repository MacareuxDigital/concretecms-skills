---
name: building-themes
description: This skill provides guidance and standard workflows for developing custom themes for Concrete CMS, including directory structure, page templates, asset management, and v9+ features like containers. Use this when creating or modifying Concrete CMS themes.
---

# Building Themes

## Overview

This skill enables the development of professional, feature-rich themes for Concrete CMS. It covers the essential components like `page_theme.php`, page templates, asset registration, and advanced features such as grid support and containers. 

For the most up-to-date best practices and implementation examples, refer to the `concrete/themes/atomik` directory in the core. It serves as the primary reference for modern theme development in Concrete CMS.

## Workflow

### 1. Setup Theme Directory
Themes should be placed within a package for better portability. Otherwise, they can be stored in the `application` directory.
- Path: `packages/your_package_handle/themes/your_theme_handle/`
- Path: `application/themes/your_theme_handle/`

### 2. Create Core Files
Every theme requires at least:
- `page_theme.php`: The main configuration class for the theme.
- `default.php`: The default page template.
- `view.php`: Required for single pages. Should include the `system_errors` element and `$innerContent`.
- `elements/header.php` & `elements/footer.php`: Shared header and footer.
- **Recommended**: Sub-divide elements for simplicity (e.g., `elements/header_top.php`, `elements/header.php`, `elements/footer.php`, `elements/footer_bottom.php`).

### 3. Configure `page_theme.php`
- Define namespace: `Concrete\Package\YourPackageHandle\Theme\YourThemeHandle`
- **Required Methods**:
    - `getThemeName()`: Return the localized name of the theme.
    - `getThemeDescription()`: Return the localized description of the theme.
- Implement `getThemeSupportedFeatures()` to optimize asset loading.
    - **JS Library Themes**: If the theme includes a full-featured JavaScript library (e.g., Bootstrap 5 JS, jQuery), list all supported features (e.g., `FeatureConstants::NAVIGATION`, `FeatureConstants::FORMS`) to avoid conflicts with Concrete's internal asset loading.
    - **No-JS Themes**: If the theme does not include any JavaScript, it is recommended **not** to implement this method.
- Configure grid support via `$pThemeGridFrameworkHandle`.
    - **Core Frameworks**: If using a supported framework (e.g., `bootstrap5`, `bootstrap4`), set this property to the corresponding handle.
    - **Custom Frameworks**: If the theme uses a custom or unsupported CSS framework (e.g., Tailwind, Bulma), **do not** set this property.
    - **Supported Handles**: Find available frameworks in `concrete/src/Page/Theme/GridFramework/Type` (e.g., `bootstrap5`, `foundation`, `nine_sixty`).

### 4. Implement Templates
- Use `$view->inc('elements/header.php')` to include shared elements. For more modularity, use multiple includes like `$view->inc('elements/header_top.php')` and `$view->inc('elements/header.php')`.
- Ensure `header_required` and `footer_required` elements are present.
    - **Note on `header_required`**: It is recommended to pass metadata variables to this element. See the [header_required snippet](references/code_snippets.md#header_required-with-metadata) for the recommended implementation.
- Use `Area` for editable regions.
- **Single Page Templates (`view.php`)**: Must include the `system_errors` element and `echo $innerContent;` to display system messages and single page content. See the [system_errors snippet](references/code_snippets.md#system_errors-for-viewphp) for the recommended implementation.
- Wrap the main theme content in a `div` element with `$c->getPageWrapperClass()`. Do **not** apply this class to the `body` element.

### 5. Manage Assets
- Use `registerAssets()` in `PageTheme` to require core assets or provide theme-specific ones.
- **Prefer Core Assets**: Always require core assets (e.g., `jquery`, `bootstrap`, `vue`) even if the theme directory contains the same libraries. This avoids conflicts and ensures compatibility with Concrete CMS's internal logic.
- Minimize redundant CSS/JS by declaring what the theme provides.

### 6. Register Theme in Package
- Update the package `controller.php` or use a CIF XML file to install the theme.

## Guidelines

- **Namespacing**: Always follow Concrete CMS PSR-4 naming conventions.
- **Reference**: Use `concrete/themes/atomik` as the gold standard for implementation details. Other bundled themes may be legacy or for internal use.
- **Security**: Include `defined('C5_EXECUTE') or die("Access Denied.");` at the top of every PHP file.
- **Localization**: Use `t()` for all user-facing strings.
- **Modular Elements**: For complex themes, it is recommended to split header and footer elements (e.g., `header_top.php` for metadata/scripts and `header.php` for navigation) to keep individual files simple and maintainable.
- **Page Wrapper**: Always use `$c->getPageWrapperClass()` on a wrapper `div` immediately inside the `body` tag, rather than on the `body` itself. This ensures that Concrete CMS's UI and dialogs work correctly within the theme's layout.
- **Asset Management**: 
    - **Core Libraries**: Always prefer core assets over bundling your own for common libraries like jQuery, Bootstrap, moment.js, vue.js, and FullCalendar.
    - **Check Bundled Assets**: To see which libraries are bundled in the core, check the `assets` array in `concrete/config/app.php`.
    - **Conflict Prevention**: Using core assets prevents multiple versions of the same library from being loaded, which can cause significant JavaScript errors.
- **Theme Features**: 
    - Use `getThemeSupportedFeatures()` to declare what your theme provides. This prevents Concrete CMS from loading redundant or conflicting core assets.
    - If your theme is a "fully featured" theme with its own JS bundle, you should return an array containing all relevant `FeatureConstants`.
    - If your theme is simple/static and has no JavaScript, omitting this method allows Concrete CMS to handle asset injection more predictably.
- **Grid Framework**: 
    - The `$pThemeGridFrameworkHandle` property enables the built-in Layout system to use your theme's grid classes.
    - Only use this if your theme is compatible with one of the core grid frameworks found in `concrete/src/Page/Theme/GridFramework/Type`.
    - Common handles include `bootstrap5`, `bootstrap4`, `bootstrap3`, `foundation`, and `nine_sixty`.
    - If your theme uses a different CSS framework (like Tailwind CSS) or a custom grid system, do not define `$pThemeGridFrameworkHandle`.
- **Modern Features**: Favor Containers over hardcoded areas for flexible layouts in Concrete CMS v9+.
- **Containers**:
    - **Naming**: Always ask the user for clarification before naming a container if the name is not explicitly provided.
    - **Element Path**: `themes/your_theme_handle/elements/containers/your_container_handle.php`
    - **Implementation**: Use `Concrete\Core\Area\ContainerArea` to define editable regions within the container.
    - **Registration**: Define containers in the package's `config/install.xml` file using the `<containers>` tag.
    - **Usage**: Containers are added to areas in the CMS by users, providing structured, reusable layouts.

