---
name: package-overview
description: Generate a comprehensive overview of installed Concrete CMS packages, including versions, metadata, and potential issues.
---

# Package Overview for Concrete CMS

This skill is designed to help developers and AI agents understand the ecosystem of add-ons and packages installed in a Concrete CMS project. It provides a structured way to audit versions, check for customizations, and identify potential compatibility issues.

## When to use this skill

Use this skill:
- When auditing a project for the first time.
- Before performing a major Concrete CMS or PHP upgrade.
- When trying to identify where a certain feature or block is coming from.
- When reviewing third-party dependencies.

## Workflow

1. **Generate Raw Data**: Run the `list_packages.php` script to gather metadata from the `packages` directory.
   ```bash
   php scripts/list_packages.php > .junie/docs/raw_packages.md
   ```

2. **Analyze Package Relationships (Agent Step)**:
   - For each package, check if there are corresponding overrides in the `application` directory. For example, if a package `theme_pixel` is installed, check for `application/themes/theme_pixel`.
   - Identify if any package is overriding core functionality or if it's being overridden itself.

3. **Check for Environment Compatibility (Agent Step)**:
   - Review the package's `controller.php` for `$appVersionRequired`.
   - Scan package code for PHP 8.x incompatibilities (using patterns from the `environment-compatibility-audit` skill).

4. **Identify Maintainability Issues**:
   - Check if the package is managed by Composer (look for `composer.json` in the package folder).
   - Check for custom modifications within the package directory itself (git logs).

5. **Generate Final Report**: Produce a structured Markdown report (e.g., `.junie/docs/packages.md`).

## Report Structure

The final report should include:

- **Executive Summary**: Total number of packages, overall health, and major concerns.
- **Detailed Package List**: A table or list with:
  - Handle and Name
  - Version
  - Source (Marketplace, Composer, Custom)
  - Customizations (Are there overrides in `application`?)
  - Compatibility Status (PHP/Concrete CMS)
- **Actionable Recommendations**:
  - Packages that need updates.
  - Packages that are incompatible with the target environment.
  - Customizations that should be reviewed.

## Agent Responsibilities

The agent should:
- Use the raw data to provide a human-readable summary of each package.
- Cross-reference packages with the results of the `override-check` skill.
- Specifically look for packages that might be "abandoned" (no git updates for a long time, or old version numbers).
