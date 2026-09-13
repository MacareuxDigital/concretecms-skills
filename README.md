# Agent Skills for Concrete CMS projects

This repository contains skills for coding agents to work with Concrete CMS projects.
Usually, coding agents know Concrete CMS well, but sometimes they generate wrong code or use a legacy approach.
This skillset helps improve the quality of generated code and keep it compatible with current Concrete CMS versions.
The main focus is developing a custom package, because core does not bundle packages, so coding agents know little about that workflow.

Skills follow the [Agent Skills](https://agentskills.io/) format and can be installed with [`npx skills`](https://github.com/vercel-labs/skills) into Cursor, Claude Code, GitHub Copilot, and other agents.

[![skills.sh](https://skills.sh/b/MacareuxDigital/concretecms-skills)](https://skills.sh/MacareuxDigital/concretecms-skills)

## Installation

Install the collection into the current project (detected agents, including Cursor):

```bash
npx skills add MacareuxDigital/concretecms-skills
```

Install globally for Cursor on this machine:

```bash
npx skills add MacareuxDigital/concretecms-skills -g -a cursor -y
```

Install a single skill:

```bash
npx skills add MacareuxDigital/concretecms-skills --skill building-packages
```

List skills in this repository without installing:

```bash
npx skills add MacareuxDigital/concretecms-skills --list
```

Update installed skills later:

```bash
npx skills update
```

## Available skills

| Skill | Use when |
| --- | --- |
| `building-packages` | Creating or managing a custom Concrete CMS package |
| `building-blocktypes` | Creating or modifying custom block types |
| `building-block-templates` | Changing the markup of an existing block type without editing core files |
| `building-singlepages` | Adding dashboard pages or other single pages in a package |
| `building-themes` | Developing a custom theme (page templates, assets, v9+ containers) |
| `concrete-dashboard-crud` | Implementing Dashboard CRUD for Doctrine entities |
| `customizing-express-form` | Customizing the appearance of Express forms |
| `working-with-database` | Connecting to the database or querying from custom code |
| `concrete-cms-security` | Writing or reviewing Concrete CMS PHP with security in mind |
| `cms-updater` | Upgrading the Concrete CMS core while keeping `application/` overrides compatible |
| `override-check` | Inventorying `application/` overrides and writing `overrides.md` |
| `release-notes-analyzer` | Reviewing Concrete CMS release notes for breaking changes before an update |
| `environment-compatibility-audit` | Auditing PHP, MySQL, Apache, or Nginx upgrade compatibility |
| `package-overview` | Auditing installed packages, versions, and compatibility |

## Usage

After install, the agent loads a skill when the task matches its description. You can also invoke one by name in chat (for example `/building-packages`).

## Skill structure

Each skill is a folder under `skills/`:

- `SKILL.md` — instructions for the agent
- `references/` — supporting documentation loaded on demand (optional)
- `scripts/` — helper scripts the agent should run (optional)

## References for contributors

- [Agent Skills (agentskills.io)](https://agentskills.io/)
- [Anthropic - Agent skills best practices](https://platform.claude.com/docs/en/agents-and-tools/agent-skills/best-practices)
- [vercel-labs/skills](https://github.com/vercel-labs/skills)
