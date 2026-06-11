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

## Notes

- The old push model using GitHub Actions secrets is no longer required for Lolipop environments.
- If you use a private repository, store the GitHub token only in WordPress settings and never commit it.
