# GitHub-managed content

`plainmark` can use Markdown files in this directory as the source of truth for WordPress posts and Works.

## Directory structure

```text
content/
├── posts/   # WordPress posts
└── works/   # Portfolio posts
```

Only `.md` files under `content/posts/` and `content/works/` are synchronized by `.github/workflows/sync-content.yml`.

## Setup

1. In WordPress, open **Tools → GitHub Content**.
2. Generate a synchronization secret.
3. Add the displayed values to GitHub **Settings → Secrets and variables → Actions**:
   - `PLAINMARK_SYNC_URL`
   - `PLAINMARK_SYNC_SECRET`
4. Add or update a Markdown file under `content/posts/` or `content/works/`.
5. Push to `main`, or run **Sync Markdown content to WordPress** manually from GitHub Actions.

The workflow sends changed Markdown files to the authenticated WordPress REST endpoint. Existing content with the same `slug` and `post_type` is updated.

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

## Security

- Never commit `PLAINMARK_SYNC_SECRET` to the repository.
- Store it only as a GitHub Actions repository secret.
- Regenerate it from WordPress immediately if it is exposed.
