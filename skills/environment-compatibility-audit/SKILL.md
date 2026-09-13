---
name: environment-compatibility-audit
description: A guideline and set of commands for auditing Concrete CMS projects for compatibility with PHP, MySQL, Apache, and Nginx upgrades.
---

# Environment Compatibility Audit Guideline

This guideline provides a structured approach for auditing Concrete CMS projects when upgrading the server environment (PHP, MySQL/MariaDB, Web Server).

## When to use this skill

Use this skill when:
- You are planning to upgrade PHP (e.g., to 8.x).
- You are migrating to a new version of MySQL or MariaDB.
- You are switching or upgrading the web server (Apache to Nginx, or vice-versa).
- You encounter errors after an environment change.

## 1. Gathering Environment Information

The most reliable way to get a snapshot of the current environment is from the Concrete CMS Dashboard or via the CLI.

### Option A: Dashboard (Recommended)
**Action**: Go to **Dashboard > System & Settings > Environment > Information** and copy the entire text. Save this to a file (e.g., `.junie/docs/environment_info.txt`) or provide it directly to the AI agent.

### Option B: CLI
If you have SSH access, you can run:
```bash
concrete/bin/concrete5 c5:info
```
Or for newer versions:
```bash
concrete/bin/concrete c5:info
```

### ⚠️ Important Note on Environment Parity
The local environment might have different configurations (PHP version, database settings, extensions) than the production environment. **Always clarify which environment the report is coming from.** If possible, provide reports for both local and production to ensure full compatibility.

This report provides:
- Core and Database versions.
- Server software (Apache/Nginx) and API (FPM/CGI).
- PHP version, extensions, and critical settings (memory_limit, max_execution_time).
- Database version, SQL mode, and character set.
- Lists of installed Packages and Overrides.

## 2. PHP Compatibility (focus on 8.x)

Refer to the PHP 8.x compatibility section for specific code audits.

### Automated Scans (Grep Commands)
Use `grep` to identify common PHP 8+ incompatibilities in `application` and `packages`.

```bash
# Deprecated/Removed Functions
find application -name "*.php" -not -path "*/vendor/*" -exec grep -nE "(each|create_function|money_format|get_magic_quotes_gpc|libxml_disable_entity_loader|fgetss|gzgetss|restore_include_path|mhash)\s*\(" {} +

# Syntax Changes (Curly brace array access)
find application -name "*.php" -not -path "*/vendor/*" -exec grep -nE "\$[a-zA-Z0-9_]+\{" {} +
```

## 3. MySQL / MariaDB Compatibility

Upgrading from MySQL 5.7 to 8.0+ or MariaDB 10.x involves several changes.

### A. Reserved Keywords
MySQL 8.0 introduced new reserved words (e.g., `groups`, `rank`, `window`).
- **Audit Tip**: Search for these keywords in custom SQL queries or table/column names.
```bash
find application -name "*.php" -not -path "*/vendor/*" -exec grep -nEi "(['\"])(groups|rank|window|function|member|system)(['\"])" {} +
```

### B. Strict Mode
MySQL 8.0 is stricter about invalid dates (`0000-00-00`) and integer widths.
- **Audit Tip**: Check for `0000-00-00` in code or database exports.
```bash
grep -r "0000-00-00" application
```

## 4. Web Server Compatibility (Apache/Nginx)

### A. Apache (.htaccess)
- Ensure `mod_rewrite` is enabled.
- Check for custom rules in `.htaccess` that might need to be ported to Nginx.

### B. Nginx (Configuration)
- Concrete CMS requires specific fastcgi settings and rewrite rules for "Pretty URLs".
- **Verification**: Check if the Nginx config has the equivalent of:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 5. Package Compatibility (Composer)

When environment changes, dependencies often need updates.
- **Action**: Run `composer check-platform-reqs` to see if the current environment meets the requirements of installed packages.
- **Action**: Run `composer update --dry-run` to see potential updates for the new PHP version.

## 5. Verification Checklist
1. [ ] PHP compatibility scan completed.
2. [ ] SQL reserved keyword check performed.
3. [ ] `composer check-platform-reqs` passes.
4. [ ] Pretty URLs work on the target web server.
5. [ ] Database connection works with the new DB version/driver.
