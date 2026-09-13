---
name: cms-updater
description: >-
  Upgrade the Concrete CMS core while keeping application/ overrides compatible.
  Use when the user asks to update Concrete CMS, replace the concrete/ directory,
  apply a new core tag, or run c5:update after a core bump.
---

# CMS updater

Upgrade the live core without silently dropping project overrides.

Replacing `concrete/` is destructive. Stage first. Patch `application/` second. Apply only after override-check has a target core.

## Checklist

Copy and track:

```
- [ ] detect (version, CLI, vendor-dir, gitignore, update.php)
- [ ] release-notes-analyzer for current -> target
- [ ] environment-compatibility-audit against the target PHP/MySQL
- [ ] stage the target core (do not replace yet)
- [ ] override-check with --target-core <staged>
- [ ] port or delete application/ overrides
- [ ] apply with --yes (creates a tar backup)
- [ ] composer install if vendor-dir lives under concrete/
- [ ] CLI c5:update
- [ ] smoke the site
```

## Detect

```bash
bash scripts/update_cms.sh detect --root "$(pwd)"
```

Read every line. If `concrete_gitignored=yes`, git history cannot diff core files. Staging is the only target tree.

If `application/config/update.php` exists, the live core may be under `updates/`, not `concrete/`. Detect prints `live_core`. Use that path everywhere.

If `composer_vendor_dir` is `./concrete/vendor` or `concrete/vendor`, a core swap deletes vendor. Plan `composer install` immediately after apply. Do not run a blind `composer update`.

## Stage

Pass a tag (`8.5.21`, `9.4.1`) or a tarball URL.

```bash
bash scripts/update_cms.sh \
  stage 8.5.21 --root "$(pwd)"
```

The script writes `staged_core=...`. Stop if download or extract fails. Do not apply.

Composer-managed cores that require `concretecms/core` or `concrete5/core` are not this tarball path. Tell the user and stop unless they asked to replace a copied `concrete/` tree anyway.

## Patch overrides before apply

Run [../override-check/SKILL.md](../override-check/SKILL.md) with `--target-core` set to `staged_core`.

For each override whose target-core status is `changed in target core`:

1. Read live core, application override, and staged core.
2. Port the new core logic into the override, or delete the override if the staged core already has the project's change.
3. Do not copy the staged file over the override unless the override is an identical leftover.

Also run [../release-notes-analyzer/SKILL.md](../release-notes-analyzer/SKILL.md) and [../environment-compatibility-audit/SKILL.md](../environment-compatibility-audit/SKILL.md).

## Apply

Pause unless the user already asked to replace core. Then:

```bash
bash scripts/update_cms.sh \
  apply /path/from/staged_core --root "$(pwd)" --yes
```

Apply refuses to run without `--yes`. It tars the live core into `.cursor/cms-update-backups/` first.

Do not `rm -rf concrete` by hand.

## After apply

1. If vendor lived under the old core, run `composer install` (not `composer update`) from the project root.
2. Find the CLI from detect (`concrete/bin/concrete` on v9, `concrete/bin/concrete5` on v8).
3. Run `$CLI c5:update` from the project root.
4. If `application/config/update.php` pointed at `updates/`, decide whether that file is still needed. A tree swap into `concrete/` often means removing a stale `update.php`.
5. Hit the homepage and the Dashboard login. Fix fatals before claiming the upgrade worked.

## Sibling skills

- Overrides: [../override-check/SKILL.md](../override-check/SKILL.md)
- Release notes: [../release-notes-analyzer/SKILL.md](../release-notes-analyzer/SKILL.md)
- Environment: [../environment-compatibility-audit/SKILL.md](../environment-compatibility-audit/SKILL.md)
