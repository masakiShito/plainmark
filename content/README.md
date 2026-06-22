# GitHub-managed content

`plainmark` can use Markdown files in this directory as the source of truth for WordPress posts and Works.

## Directory structure

```text
content/
├── posts/   # WordPress posts
└── works/   # Portfolio posts
```

Only `.md` files under `content/posts/` and `content/works/` are synchronized by WordPress **GitHub Pull Sync**.

## Setup

1. In WordPress, open **Tools → GitHub Pull Sync**.
2. Confirm the repository is `masakiShito/plainmark` and the branch is `main`.
3. Confirm content paths are:
   - `content/posts`
   - `content/works`
4. Leave GitHub token empty for a public repository. Add a fine-grained token only for private repositories or API rate-limit issues.
5. Click **GitHubから同期**.

WordPress fetches Markdown from GitHub and imports it. Existing content with the same `slug` and `post_type` is updated.

This pull model avoids inbound POST requests from GitHub Actions to WordPress, which can be blocked by shared hosting WAF/nginx rules.

## Article front matter

```yaml
---
title: "TypeScriptの型ガード"
slug: "typescript-type-guards"
post_type: "post"
status: "publish"
date: "2026-06-10"
article_type: "tutorial"
difficulty: "beginner"
technologies:
  - "TypeScript"
verified_status: "verified"
verified_date: "2026-06-10"
verified_env: "Node.js 24 / TypeScript 5.9 / macOS"
review_date: "2026-09-10"
ci_status: "passing"
ci_checked_at: "2026-06-20T09:30:00Z"
ci_run_url: "https://github.com/<owner>/<repo>/actions/runs/123456"
tested_path: "examples/react-state"
test_command: "npm test"
related_works:
  - "plainmark-wordpress-theme"
---
```

`related_works` accepts Portfolio slugs or numeric IDs.

Verification statuses:

- `verified`: tested and working
- `unverified`: not verified
- `deprecated`: no longer recommended

When `review_date` has passed, the public status automatically becomes **再確認が必要**.

### CI verification fields

These fields are optional. `verify-code.yml` normally writes `ci_status`, `ci_checked_at`, and `ci_run_url` back automatically from GitHub Actions. They can also be written manually for local testing.

| front matter key | Description |
| --- | --- |
| `ci_status` | CI result. Allowed values: `passing`, `failing`, `error`, `skipped`, `unknown`. Invalid values are stored as `unknown`. |
| `ci_checked_at` | CI checked timestamp, for example `2026-06-20T09:30:00Z`. The time portion is preserved. |
| `ci_run_url` | GitHub Actions run URL. |
| `tested_path` | Directory under `examples/` or another repository-relative directory tested by CI. |
| `test_command` | Command executed by CI in `tested_path`. |

### Code verification workflow

`verify-code.yml` reads `content/posts/*.md`, finds articles with both `tested_path` and `test_command`, runs the command, and writes the result back to the article front matter.

- `package.json` in `tested_path` triggers `npm ci || npm install` before the test command.
- `requirements.txt` in `tested_path` triggers `pip install -r requirements.txt` before the test command.
- Exit code `0` becomes `passing`.
- Non-zero test command exit code becomes `failing`.
- Setup or execution errors become `error`.
- Missing `tested_path`, missing `test_command`, or a missing directory is treated as `skipped`.

The workflow runs weekly, can be started manually with `workflow_dispatch`, and runs when `examples/**`, `.github/scripts/verify_examples.py`, or `.github/workflows/verify-code.yml` changes on `main`. It intentionally does not run on `content/posts/**` changes, so CI result write-back commits do not loop.

When CI results are written back, the workflow explicitly dispatches `sync-content.yml` so WordPress can pull the updated front matter. The workflow needs the standard repository `GITHUB_TOKEN` with `contents: write` and `actions: write`; the content sync workflow still uses its existing WordPress sync secrets.

### CI and Freshness Score

CI does not change `verified_status`. It is a signal for scoring and display.

- `ci_status: "failing"` subtracts the configured `ci_failing` weight and forces the Freshness rank to `stale` by default.
- `ci_status: "error"` subtracts the configured `ci_error` weight.
- `ci_status: "passing"` within the configured `ci_fresh_days` window, 90 days by default, waives the verified-date age penalty and the missing verified-date penalty.
- `plainmark_freshness_weights` can change `ci_fresh_days`, `ci_failing`, and `ci_error`.
- `plainmark_ci_failing_forces_stale` can disable the failing-to-stale rank override.

## Works front matter

```yaml
---
title: "Plainmark WordPress Theme"
slug: "plainmark-wordpress-theme"
post_type: "portfolio"
status: "publish"
technologies:
  - "WordPress"
  - "PHP"
  - "TypeScript"
work_summary: "技術知識と制作物をつなぐWordPressテーマ。"
work_problem: "技術記事とポートフォリオが分断される。"
work_solution: "記事・Works・技術タグを横断して関連付ける。"
work_github_url: "https://github.com/masakiShito/plainmark"
related_posts:
  - "typescript-type-guards"
---
```

`related_posts` accepts post slugs or numeric IDs.

## Notes

- The old push model using GitHub Actions secrets is no longer required for Lolipop environments.
- If you use a private repository, store the GitHub token only in WordPress settings and never commit it.
- Put verifiable article code under `examples/` and keep article front matter pointed at the relevant directory and command.
