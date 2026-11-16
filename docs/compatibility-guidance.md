# Concrete CMS compatibility principles

Compatibility depends on the exact CMS, PHP, package and deployment versions. A package constraint states intended support; it does not establish that a particular deployment was tested. Bundled files may differ from active core files selected through updates, and CLI PHP may differ from the web runtime. Composer platform emulation is not runtime evidence.

Core overrides can retain old upstream behavior and mask later fixes. Compare old core, the customization and target core to distinguish inherited implementation from custom requirements. A release-note match alone does not establish that an override can be removed. Services, bindings, events, block templates, theme assets and package hooks can affect compatibility even without a directly matching override file.

For a PHP transition, consider each intervening minor version's migration guidance. Source/target compatibility differs from a requirement to support every runtime in a range. CMS requirements, manual add-ons, dependencies, extensions, database drivers, workers and integrations all contribute to the result. Static analysis provides bounded evidence and cannot establish application behavior on its own.

Verify version-specific facts against [Concrete CMS releases](https://github.com/concretecms/concretecms/releases), tagged source, [official PHP migration guides](https://www.php.net/manual/en/appendices.php), and vendor documentation. Unavailable evidence remains unknown rather than implied support. Staging behavior and restore feasibility are separate from code-level compatibility.

This repository owns general domain guidance. Team-specific audit skills, inventory scripts, reports, CI automation and client evidence are maintained separately. No new toolkit repository is required merely to consume this guidance.
