---
name: override-check
description: >-
  Inventory Concrete CMS files under application/ that override core or add
  custom code, then write overrides.md. Use when the user asks for an override
  check, a customization inventory, whether an application override is still
  needed, or as the first step of a Concrete CMS core upgrade.
---

# Override check

Scan `application/` against the live core. Write a report an updater can trust.

Do not hand-walk the tree. Run `scripts/check_overrides.php`. The script classifies files, skips vendored and gitignored paths, and diffs overrides.

## Checklist

Copy and track:

```
- [ ] Locate the project root (directory that contains application/)
- [ ] Run check_overrides.php from that root
- [ ] If a target core is staged, pass --target-core
- [ ] Cross-check Dashboard Environment Information "Overrides" if the user pasted it
- [ ] Write .cursor/docs/overrides.md from the raw scan
- [ ] Recommend removal only when the target core clearly covers the override
- [ ] Delete the raw scan file
```

## Run the scanner

Skill dir is the directory that contains this `SKILL.md`.

```bash
php scripts/check_overrides.php \
  --root "$(pwd)" \
  > .cursor/docs/raw_overrides.md
```

Optional:

```bash
php scripts/check_overrides.php \
  --root "$(pwd)" \
  --core /path/to/live/concrete \
  --target-core /path/to/staged/concrete \
  --max-diff-lines 60
```

`--json` prints the same records as JSON.

If `application/` is missing, stop. Do not invent a root.

If `concrete/` is gitignored, do not use `git diff` against tags. Pass `--target-core` to a real extracted core directory. Dashboard updates may live under `updates/` and `application/config/update.php`. The script reads that file when present.

## Write the report

Read the raw scan. For each listed file, write an overview from the diff or preview, not from the filename.

Output path is `.cursor/docs/overrides.md`. Create `.cursor/docs/` if needed. If `.junie/docs/overrides.md` already exists and `.cursor/docs/` does not, update the existing Junie file instead.

Use this shape:

```markdown
# Overrides

## Summary
One short English paragraph. Name the live core version from scan meta.
Say whether risk is mostly templates, or real core logic overrides.

## Removable overrides
Files that can go away because the target core already contains the change.
If none, write "None." Do not guess.

## custom_block_template
### `application/blocks/example/templates/foo/view.php`
- Overview: ...
- Git: ...
- Related: ...
- Core update: unchanged | changed in target core | n/a

(repeat one heading per category that the scan emitted)

## Recommendations for maintainers
Checklist of update risks.

## Recommendations for testers
Pages, blocks, and forms to click after a core update.
```

Rules:

- `application/bootstrap/app.php` is custom wiring. Describe routes, events, and bindings. Do not treat it as a core override.
- Identical copies of core files are still overrides. Say they match live core and whether they should stay.
- A removable recommendation needs a cited target-core change. If `--target-core` was not passed, the removable section is "Not evaluated. No target core."
- English only for Summary, Removable overrides, Recommendations for maintainers, and Recommendations for testers.

## Dashboard cross-check

If the user provided Environment Information, compare its Overrides list to the scan. Report paths that appear in only one list.

## Cleanup

Delete `raw_overrides.md` after `overrides.md` is written.

## Sibling skills

- Core upgrade: [../cms-updater/SKILL.md](../cms-updater/SKILL.md)
- Release notes: [../release-notes-analyzer/SKILL.md](../release-notes-analyzer/SKILL.md)
