# Interpreting Concrete CMS security guidance

The main skill provides domain-specific coding guardrails and investigation leads. Pattern matches, numeric identifiers, GET route names, missing defense-in-depth, or a dependency advisory alone do not prove a site vulnerability. Establish actor authority, reachability, attacker-controlled input, mitigating controls and concrete cross-boundary impact before making that claim. Fully authorized same-principal actions require a demonstrated boundary violation to qualify as vulnerabilities.

Verify APIs, permission behavior, affected version ranges and fixes against the exact deployed version and primary advisories. Historical examples and CVE counts are not current support or vulnerability assertions. Distinguish runtime-specific behavior, including PHAR handling, from historical vulnerabilities.

Escaping depends on output context: HTML encoding alone does not validate a URL scheme or make JavaScript, CSS or rich text safe. Trace the data to its actual use and evaluate all applicable controls.

Use an evidence-based audit methodology such as the upstream [Cloudflare security-audit skill](https://github.com/cloudflare/security-audit-skill) when conducting an audit.
