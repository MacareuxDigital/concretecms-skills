---
name: release-notes-analyzer
description: Fetch and analyze Concrete CMS release notes and breaking changes to identify actionable items for updates.
---

# Release Notes Analyzer for Concrete CMS

This skill is designed to help agents and developers systematically review Concrete CMS release notes to identify breaking changes, new features, and bug fixes that may affect a project's customizations or environment requirements.

## When to use this skill

Use this skill:
- Before starting a Concrete CMS core update.
- When planning an environment upgrade (PHP, MySQL, etc.).
- When auditing a project for potential issues after a minor or major version jump.

## Sources

The primary sources for release notes are:
- **Official Documentation**: [https://documentation.concretecms.org/9-x/developers/introduction/version-history](https://documentation.concretecms.org/9-x/developers/introduction/version-history)
- **GitHub Releases**: [https://github.com/concretecms/concretecms/releases](https://github.com/concretecms/concretecms/releases)

## How to use this skill

1. **Identify Version Range**: Determine the current version of the project and the target version.
2. **Fetch Release Notes**: Use `curl` or a browser tool to retrieve the content of the release notes for all versions in the range.
3. **Analyze Content**:
    - **Breaking Changes**: Look for the "Breaking Changes" or "Deprecations" sections. Note any changed class names, method signatures, or removed features.
    - **Bug Fixes**: Search for fixes that might relate to existing overrides in the project. If a bug fixed in core is also addressed by an override, the override may be removable.
    - **Requirement Changes**: Check for changes in minimum PHP, MySQL, or library versions.
4. **Generate Action Plan**: Create a list of actionable items based on the analysis.

## Agent Instructions for Analysis

When performing this analysis, the agent should:
- Search for keywords like "Deprecated", "Removed", "Changed", "Requires", "Security".
- Cross-reference mentioned core files with the files listed in the project's `overrides.md` (if available).
- Summarize the findings in a structured format:
    - **Core Logic Changes**: Impact on overrides.
    - **Environment Requirements**: Changes in PHP/DB versions.
    - **Recommended Removals**: Overrides that are no longer needed.
    - **Package Updates**: Updates needed for third-party packages or core-bundled libraries.

## Example Output Structure

```markdown
# Release Note Analysis: [Current Version] to [Target Version]

## Summary of Major Changes
- [Brief description of main focus of the updates]

## Breaking Changes & Deprecations
- **[Component Name]**: [Description of change and impact]

## Environment Requirement Changes
- **PHP**: [Min version change if any]
- **MySQL**: [Min version change if any]

## Actionable Items for Overrides
- **[File Path]**: [Why it needs attention based on release notes]
- **[File Path]**: [Recommended for removal as fix is now in core]

## Security Fixes
- [List relevant CVEs or security improvements]
```
