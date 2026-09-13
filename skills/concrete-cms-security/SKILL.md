---
name: concrete-cms-security
description: Apply this skill whenever you are writing, reviewing, or modifying PHP code for Concrete CMS (concrete5) — custom blocks, packages, single pages, dashboard or dialog or backend controllers, REST endpoints, Express entities, themes, attribute types, jobs, or anything in concrete/controllers, concrete/blocks, controllers/, blocks/, packages/, src/. Use it even when the user does not say "security" — for example, adding a new endpoint, accepting a POST parameter, rendering a user-supplied string, unserializing block config, fetching a URL server-side, or handling a file upload. Concrete CMS shipped 90+ CVEs over the past four years and the failure modes repeat — admin strings rendered raw, missing CSRF tokens on state-changing GETs, unserialize() on stored data, file uploads validated after writing, sequential IDs without per-object authz, type-juggling guard bypasses, OAuth handlers that skip account-state checks. This skill encodes the patterns behind those CVEs so you do not reproduce them.
---

# Concrete CMS Security

Operational reference for writing or reviewing Concrete CMS PHP. Read top-to-bottom for a new task; jump to **Danger signals** or **Review priorities** when triaging an existing PR.

## Structure

1. **Danger signals** — grep patterns demanding deeper inspection
2. **Review priorities** — order to inspect a PR, by risk-per-minute
3. **Critical patterns (C1–C7)** — RCE / privesc / auth bypass
4. **High-frequency patterns (H1–H7)** — XSS, CSRF, IDOR, SSRF, etc.
5. **Pre-flight checklist**
6. **Threat model & history** — actors and recurring patterns

Sections 1–5 are the working reference. Section 6 is evidence.

Skip this skill only for documentation/marketing copy, frontend-only changes that add no endpoints, or read-only review without modification.

---

## Danger signals

Stop and apply the linked rule if any of these appear.

**RCE class:**

| Signal                                                | Rule             |
|-------------------------------------------------------|------------------|
| `unserialize(`                                        | C1               |
| `phar://`, `file://`, `data://`, `gopher://`          | C1, H4           |
| `include $x`, `require $x` (variable path)            | C3               |
| `move_uploaded_file(`                                 | C2               |
| `uniqid(` used for security/paths                     | C2               |
| `exec`, `shell_exec`, `system`, `passthru`, backticks | — block entirely |

**Auth / privilege class:**

| Signal                                                                      | Rule |
|-----------------------------------------------------------------------------|------|
| `$request->request->all()`, raw `$_POST` to update                          | C7   |
| `File::getByID(`, `Page::getByID(`, `Event::getByID(`, `Express*::getByID(` | H3   |
| `canAccess()` before a mutation                                             | C5   |
| `==` in auth/password/token/guard code                                      | C6   |
| `=== true/false` on a JSON-decoded field                                    | C6   |
| OAuth callback w/o `state`, session regen, or `uIsActive`                   | C7   |
| Password/email/2FA change w/o current-password re-verify                    | C7   |

**CSRF / state-change class:**

| Signal                                                                                                               | Rule          |
|----------------------------------------------------------------------------------------------------------------------|---------------|
| GET routes named `delete`, `download`, `do_update`, `star`, `approve*`, `rescan`, `install_*`, `add/removeFavorite*` | H2, C4        |
| Mutating controller missing `token->validate(...)`                                                                   | H2            |
| Token emitted in view but never validated                                                                            | H2            |
| `if ($valid) throw; else proceed` on token check                                                                     | H2 — inverted |

**XSS / output class:**

| Signal                                                             | Rule |
|--------------------------------------------------------------------|------|
| `<?= $var ?>` or `<?php echo $var` without `h()` in template       | H1   |
| `t('format %s', $userInput)` (translation as format string)        | H1   |
| `href="<?= $url ?>"`, `value="<?= $x ?>"` without attribute escape | H1   |
| User-controlled "numeric" field interpolated without cast          | H1   |

**File / permission / SSRF / path class:**

| Signal                                                                 | Rule   |
|------------------------------------------------------------------------|--------|
| `mkdir(` without explicit permissions argument                         | H6     |
| `chmod(..., 0777)`                                                     | H6     |
| `file_get_contents($userUrl)`, Guzzle/cURL on user URL                 | H4     |
| Redirects followed on server-side fetch without re-validation          | H4, C2 |
| ZIP/archive extraction without per-entry traversal check               | C2, C3 |
| String field concatenated into a `$path` that is read/written/included | C3     |
| `realpath()` not called before opening a constructed path              | C3     |
| `..` filtering done before URL/path decoding                           | C3     |

**High-risk subtrees** (extra scrutiny for any new code here):

```
concrete/controllers/dialog/                         ← 2026 CSRF cluster
concrete/controllers/backend/file/                   ← CSRF + IDOR
concrete/controllers/single_page/dashboard/extend/   ← CSRF→RCE (marketplace)
concrete/controllers/single_page/dashboard/system/update/  ← CSRF→RCE (core)
concrete/controllers/frontend/conversations/         ← IDOR cluster
concrete/blocks/express_entry_*/                     ← unserialize RCE × 2
concrete/blocks/{page_list,search,switch_language,form}/  ← XSS history
authentication/oauth/                                 ← state, session, uIsActive
src/File/{Importer*,Service/}                         ← extension-only validation, races
```

---

## Review priorities

Inspect in this order; flag at first failure rather than skimming the whole diff.

1. **New/modified controller actions** — HTTP method, what it mutates, CSRF token, permission check, blast-radius match. → C4, C5, H2
2. **GET routes that write/delete/install/update/approve/download** — should be POST + token. → H2, C4
3. **Object loads by integer ID** — per-object `canView*()` / `canEdit*()` before return/mutate. → H3
4. **File upload handlers** — content/magic-byte validation before `move_uploaded_file`; non-executable destination; no `uniqid()` for security; redirects disabled. → C2
5. **Template/path/filename inputs** — whitelist, `..`/null-byte/scheme rejection *after* decoding, `realpath()` confinement. → C3
6. **`unserialize(`, dynamic `include`/`require`, stream wrappers** — any of these reading DB/request/config = block the PR. → C1, C3
7. **Template output** — every interpolation through `h()` or Twig, including attribute values and translation arguments. → H1
8. **OAuth / auth callbacks** — `state`, session regen, `uIsActive`, current-password re-verify, field whitelist. → C7
9. **URL fetches** — private/metadata IP block, DNS rebinding, scheme restrictions, redirect handling. → H4
10. **Auth/guard comparisons** — `===` or `hash_equals()`; source-of-request validation. → C6
11. **Account/permission mutations** — action-specific permission, not just `canAccess()`. → C5, C7
12. **`composer.json`/`composer.lock` changes** — new deps inherit their CVE history. → H7

---

## Critical patterns (CVSS 7+ outcomes)

### C1. Never `unserialize()` data from DB, request, or block config

```php
// DON'T
$config = unserialize($this->btTable_filterFields);

// DO
$config = json_decode($this->btTable_filterFields, true);
if (!is_array($config) || !isset($config['expected_key'])) {
    throw new \UnexpectedValueException('Invalid config');
}
```

- No `unserialize()` on values from DB, request, files, or any admin/attacker-influenced channel.
- `json_decode()` against a known schema; validate structure before use.
- `phar://` also deserializes — filter `phar:` from any path passed to `file_exists`, `is_file`, `fopen`, `include`.
- Block-config columns holding serialized PHP (`btTable_filterFields`, `btTable_columns`) are smells. Migrate to JSON.
- If migration is impossible: `unserialize($data, ['allowed_classes' => false])`, then validate.

### C2. Validate uploads by content *before* writing; never trust extension

```php
// DON'T — race window: attacker can execute before unlink
move_uploaded_file($_FILES['f']['tmp_name'], $dest);
if (!$this->extensionAllowed($dest)) { unlink($dest); }

// DO — validate magic bytes / mime before any write
if (!in_array(mime_content_type($_FILES['f']['tmp_name']), $allowedMimes, true)
    || !$this->validateMagicBytes($_FILES['f']['tmp_name'])) {
    throw new \RuntimeException('Rejected');
}
move_uploaded_file($_FILES['f']['tmp_name'], $nonExecutablePath);
```

- Reject by content type and magic bytes, not extension. `.png` containing `<?php` must fail at validation, not get cleaned up after.
- Validate before bytes hit any path under webroot, `application/`, or `DIR_PACKAGES`.
- No `uniqid()` for security-sensitive paths — predictable. Use `bin2hex(random_bytes(16))`.
- Disable redirects on server-side fetches that will be unpacked/executed; validate final URL.
- ZIP/archive extraction: validate every entry name for traversal before extracting (zip-slip).

### C3. Whitelist any input becoming a file path or template name

```php
// DON'T
include DIR_BASE . '/application/blocks/' . $type . '/' . $template . '.php';

// DO
if (!preg_match('/^[A-Za-z0-9_-]+$/', $template)) {
    throw new \InvalidArgumentException('Invalid template name');
}
$base = DIR_BASE . '/application/blocks/' . $type;
$full = realpath($base . '/' . $template . '.php');
if ($full === false || strpos($full, realpath($base) . DIRECTORY_SEPARATOR) !== 0) {
    throw new \RuntimeException('Path escapes base');
}
include $full;
```

- Whitelist `[A-Za-z0-9_-]`. Reject `..`, absolute paths, null bytes, stream wrappers — *after* URL/path decoding (`..%2f`, `..%252f` are real).
- `realpath()`-confirm the result is under the intended root before opening.
- Template-name fields are code, not data — the framework will include the resolved path.

### C4. Package/marketplace/update endpoints need CSRF tokens, even on GET

```php
// DO
public function do_update() {
    if (!$this->canUpgrade()) throw new UserMessageException(t('Access Denied'));
    $token = $this->app->make('token');
    if (!$token->validate('do_core_update')) {
        throw new UserMessageException($token->getErrorMessage());
    }
    $this->performCoreUpdate($this->request->request->get('version'));
}
```

- Any route that fetches, installs, or executes code from a remote identifier (marketplace ID, package handle, version) is a CSRF-to-RCE target by default.
- Routes writing under `DIR_PACKAGES` or upgrading core MUST validate a token. Permission alone is insufficient.
- Reject GET where possible; require POST with action-scoped token.

### C5. Authorization must match the action's blast radius

```php
// DON'T — page-view permission gating a privilege-escalating mutation
if (!$this->canAccess()) throw new UserMessageException(t('Access Denied'));
$this->addUsersToGroup($_POST['users'], $_POST['gID']);

// DO — permission scoped to what's being granted
$group = Group::getByID((int) $this->request->request->get('gID'));
if (!$group || !$this->currentUserCanAssignToGroup($group)) {
    throw new UserMessageException(t('Access Denied'));
}
// ... plus CSRF token check ...
```

- Page-view permission ≠ mutate permission. Each action method needs a check matching what it can do.
- Adding to Administrators requires Administrator-level authority.
- Group-membership, permission-grant, and user-role changes are privilege-escalation-adjacent — check must be at least as strict as the privilege granted.

### C6. `===` and `hash_equals()`; validate request *source*, not just field values

```php
// DON'T — json_decode("true") returns PHP true, defeating the guard
if ($request->request->get('_fromCIF') === true) { /* skip validation */ }

// DO — validate the source (route, internal caller, auth context)
if ($this->isInternalCIFImport() && $this->authenticatedCaller()) { /* ... */ }
// constant-time for opaque tokens
if (hash_equals($expectedToken, $providedToken)) { /* ... */ }
```

- `===` / `!==` everywhere in auth, authorization, guard code, and token checks.
- `hash_equals()` for opaque tokens (OAuth `state`, CSRF, password hashes, API keys).
- Validate request source — controller path, HTTP method, authenticated caller — not just a flag in the body.

### C7. Re-auth for credential changes; `uIsActive` on every auth path

```php
// DO — field whitelist plus current-password re-verify
$fields = $this->request->request->only(['uFirstName', 'uLastName', 'uTimezone']);
if ($this->request->request->has('uPassword')) {
    if (!$this->verifyCurrentPassword($this->request->request->get('uPasswordCurrent'))) {
        throw new UserMessageException(t('Current password required'));
    }
    $fields['uPassword'] = $this->request->request->get('uPassword');
}
$this->getUserInfo()->update($fields);
```

- Whitelist accepted fields. Never `$request->request->all()` to a model update.
- Password / email / 2FA / session-hardening changes need current-password (or fresh factor) re-verify server-side.
- `uIsActive` checked at every auth path: username/password, OAuth, API tokens, "remember me", password-reset, SAML.
- OAuth callbacks specifically: validated `state`, regenerated session ID post-auth, `uIsActive` before issuing tokens, escaped integration metadata in views.

---

## High-frequency patterns (bulk of CVE volume)

### H1. HTML-escape every user-controllable string on output

```php
// DON'T
<h2><?= $integration->getName() ?></h2>
<a href="<?= $url ?>">link</a>
<?= t('Welcome %s', $userName) ?>

// DO — h() everywhere; Twig auto-escapes
<h2><?= h($integration->getName()) ?></h2>
<a href="<?= h($url) ?>">link</a>
<?= t('Welcome %s', h($userName)) ?>
```

- `h()` (or `app('helper/text')->entities()`) on every user-controllable string in PHP templates.
- Prefer Twig for new templates — auto-escapes by default.
- Translation helpers do NOT sanitize. Escape input *before* it enters a format string.
- Attribute values need escaping (`href`, `value`, `data-*`, `style`).
- Numeric-looking fields aren't safe — cast or validate types explicitly.
- Any name/title/label field an editor controls is a potential XSS payload.

### H2. CSRF token on every state-changing request

```php
// DO
public function delete() {
    if (!$this->canEditPages()) throw new UserMessageException(t('Access Denied'));
    $token = $this->app->make('token');
    if (!$token->validate('delete_pages')) {
        throw new UserMessageException($token->getErrorMessage());
    }
    $this->deletePages($this->request->request->get('cIDs'));
}
```

- State-changing GETs are CSRF-vulnerable. Validate a token regardless of HTTP method; prefer POST.
- Emitting a token in the view does not validate it.
- Test by removing the token — the action must fail. Inverted checks are real CVEs.
- Use action-scoped token names (`delete_pages`, `update_core`), not a single global token.

### H3. Per-object permission check on every load

```php
// DO
public function view($fID) {
    $file = File::getByID((int) $fID);
    if (!$file || !(new Checker($file))->canViewFile()) {
        return new JsonResponse(['error' => 'Not found'], 404); // not 403
    }
    return new JsonResponse($file->getJSONObject());
}
```

- Every controller that loads an object by ID checks the relevant permission before returning or mutating.
- 404 for objects the user can't see (not 403) — avoids existence disclosure.
- Frontend/dialog/REST endpoints have a poor history; checks are not optional there.
- Prefer public-identifier (UUID) URLs over sequential integers. The 9.5.1 Express Entry rewrite did exactly this.

### H4. Validate and pin URL fetch destinations

```php
// DO
$url = $this->validateExternalUrl($request->request->get('feed_url'));
$ip = $this->resolveAndValidateIp($url); // reject private/metadata
$body = $this->fetchByIp($url, $ip, ['follow_redirects' => false]);
```

- Resolve hostname yourself; reject private ranges (RFC1918, link-local, loopback, IPv6 equivalents, IPv4-mapped IPv6, cloud metadata `169.254.169.254`).
- Connect using validated IP, not hostname, to defeat DNS rebinding.
- Reject non-decimal-dotted IPs (hex, octal, big-endian integer).
- Disable redirects, or re-validate redirect targets with the same rules.
- Block non-HTTP(S) schemes: `file://`, `phar://`, `gopher://`, `data://`, `dict://`.
- Use `Concrete\Core\Url\Validation` utilities added in 9.5.1.

### H5. No verbose error handlers; no XXE-able XML in production

- Disable whoops and verbose handlers in production. Catch exceptions at controller boundaries; generic error to client; detail to server log.
- Never include `$_SERVER` / `$_ENV` in network-reachable error output.
- For XML/SVG: refuse external entities (`libxml_set_external_entity_loader`), set `LIBXML_NONET`, disable DOCTYPE if not needed.

### H6. Explicit file/directory permissions; never 0777

```php
// DON'T            // DO
mkdir($path);       mkdir($path, 0755, true);
chmod($f, 0777);    chmod($f, 0644);
```

- Always pass explicit perms to `mkdir`, `chmod`, `touch`. `0755` dirs, `0644` files.
- If `0777` seems necessary, the fix is changing ownership, not loosening permissions.

### H7. Keep dependencies patched

- Upstream CVEs in `composer.lock` are real CVEs for the site. Update on the same cadence as core.
- Prefer libraries already in Concrete's tree over pulling new packages.
- Watch in particular: Guzzle, Symfony components, league/oauth2-server, enshrined/svg-sanitized, Laminas/Zend Mail.

---

## Pre-flight checklist

Answer each with "yes" or "not applicable" before submitting:

1. **State change?** CSRF token validated (not just emitted), fails closed when missing?
2. **Object load by ID?** Per-object permission check before return/mutate? 404 for hidden objects?
3. **User-controllable output?** Every interpolation escaped — admin strings, attribute values, translation arguments?
4. **Deserialization or template path?** No `unserialize()` / dynamic include reachable from DB, request, or admin block config?
5. **File upload?** Content/magic-byte validation *before* write to webroot-reachable path? No `uniqid()` for security?
6. **Server-side URL fetch?** Private/metadata IPs blocked, DNS rebinding mitigated, schemes restricted, redirects disabled or re-validated?
7. **Account-state change?** Current-password verified for credential changes, field whitelist on updates, `uIsActive` on every auth path?
8. **Privilege boundary?** Permission matches action blast radius (not page-view)? `===` / `hash_equals()` in security comparisons?

---

## Threat model & history

**Three actor profiles** dominate the CVE history:

1. **Rogue admin / editor** — has elevated privileges; explicitly in-scope per Concrete's security team. Admin-supplied strings must still be HTML-encoded; admin-supplied serialized data must not reach `unserialize()`; admin-supplied paths must be whitelist-validated.
2. **Unauthenticated CSRF attacker** with an authenticated victim — most chains target admin sessions. Every state-changing request validates a token.
3. **Unauthenticated outsider** hitting a public endpoint — dialog/frontend/REST endpoints have repeatedly leaked data without auth. Every endpoint is unauthenticated until proven otherwise.

**Patterns recur — none of these rules are theoretical.** Each maps to multiple disclosed CVEs:

- **H1** admin-controlled XSS — 30+ CVEs across 2022–2026, every year
- **H2** state-change CSRF — 20+ CVEs, mostly batched in 9.5.1 after years of accumulation
- **H3** sequential-ID IDOR — 10+ CVEs (flagged CVE-2023-48653, exploded in 9.5.1)
- **C7** OAuth + credential re-auth — CVE-2021-40101 + CVE-2026-8327 (password-no-reverify, 5yr gap); CVE-2022-43687 (session fix), CVE-2022-43693 (missing `state`), CVE-2026-7887 (missing `uIsActive`)
- **C1** unserialize / PHAR — CVE-2021-40102, CVE-2026-3452, CVE-2026-8135
- **C2/C3** file & path RCE — 2021 Fortbridge race + `uniqid`, CVE-2022-21829, CVE-2026-8134
- **C6** type-juggling guards — CVE-2022-43690 (loose `==`), CVE-2026-8135 (`json_decode("true")`)
- **H4** SSRF in URL fetchers — CVE-2021-22969/22970/40109, CVE-2026-7890
- **H5** error/XML info disclosure — CVE-2022-43689 (SVG XXE), CVE-2022-43691 (whoops)

**Repeat-offender subsystems**: Express blocks, OAuth callbacks, marketplace/extend controllers, file uploader, dashboard dialog controllers. Mirror 9.5.1-era post-rewrite versions, not pre-rewrite originals.
