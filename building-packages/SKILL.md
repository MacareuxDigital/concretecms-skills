---
name: building-packages
description: Create and manage Concrete CMS packages. Use this skill when the user asks to create a custom package or manage existing packages.
---

# Building Packages

Concrete CMS packages are modular components that extend the functionality of the platform. This skill is essential for developers who want to create custom functionality or integrate third-party services into their Concrete CMS projects.

## Basic Workflow for Package Development

Follow these steps to develop a custom package in Concrete CMS:

1.  **Initialize the Package Directory**:
    Create a new directory under `packages/` with a lowercase, underscore-separated name (e.g., `packages/my_custom_package`).
2.  **Create the Package Controller**:
    Create a `controller.php` file in the package root. Define the package handle, version, and basic information.
    - [Package Structure and Controller Reference](references/package_structure.md)
3.  **Implement Installation Logic**:
    In the `install()` method of your controller, add code to register block types, themes, single pages, or other entities.
    - [Installing Entities Reference](references/install_entities.md)
4.  **Add Custom Functionality**:
    - **Extend Core Classes**: [Extending Core Classes Reference](references/extending_core.md)
    - **Custom Block Types**: Use the `building-blocktypes` skill.
    - **Routes and Services**: [Routing and Service Providers Reference](references/routing_services.md)
5.  **Install the Package**:
    Use the Concrete CMS Dashboard or CLI (`concrete/bin/concrete c5:package:install my_custom_package`) to install your package.
6.  **Update and Refine**:
    When making changes to the package structure (like adding a new block type), update the `$pkgVersion` in `controller.php`, implement the `upgrade()` method, and run the update from the Dashboard or CLI (`concrete/bin/concrete c5:package:update my_custom_package`).

## Reference Documentation

- [Package Structure and Creation](references/package_structure.md)
- [Installing Entities (Blocks, Themes, Attributes, etc.)](references/install_entities.md)
- [Extending Core Classes](references/extending_core.md)
- [Registering Routes and Service Providers](references/routing_services.md)