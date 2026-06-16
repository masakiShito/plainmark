# plainmark

**The only WordPress theme that tracks whether your technical articles still work.**

Most blog themes help you publish. plainmark helps you maintain.  
Built for engineers who care about the accuracy of what they've written.

---

## What makes it different

| | Zenn / Qiita | Generic WP themes | **plainmark** |
|---|---|---|---|
| Content ownership | Platform-owned | Site-owned | GitHub Markdown as the source of truth |
| Article freshness | Not tracked | Not tracked | Freshness score, review dates, verification state |
| Code reliability | Reader must guess | Reader must guess | Verified environment and lifecycle metadata |
| Blog / portfolio bridge | Separate services | Usually separate | Articles and Works connect both ways |
| Knowledge structure | Tags | Categories/tags | Knowledge Map, Learning Paths, Skills Page |

plainmark is not just a theme for publishing technical posts. It is a maintenance layer for technical knowledge.

---

## Key Features

### Content Lifecycle

- **Freshness System** — Calculates a 0-100 freshness score from verification dates, review deadlines, dependency metadata, and reader feedback.
- **Verification Card** — Shows whether an article is verified, unverified, due for review, or deprecated.
- **Review Reminder** — Surfaces articles that need maintenance before they silently rot.
- **Reader Feedback** — Lets readers report stale information and feeds that signal back into the article lifecycle.

### Knowledge Structure

- **Blog ↔ Works Bridge** — Connects articles and portfolio works so knowledge and output reinforce each other.
- **Knowledge Map** — Visualizes relationships between posts, works, technologies, and series.
- **Learning Paths** — Generates recommended reading sequences from technology tags, difficulty, freshness, and series metadata.
- **Skills Page** — Aggregates technologies from articles and works into an output-based skill sheet.

### Technical Publishing

- **Code Playground** — Runs JavaScript / HTML / CSS inside articles.
- **Multi-file Code Tabs** — Adds file-switching code blocks with `[code_tabs]`.
- **Article Changelog** — Displays article-level update history.
- **Revision Diff** — Makes content changes easier to inspect.
- **Reader Persona** — Allows sections to be targeted by reader level or framework.

### Git-native Workflow

- **GitHub Markdown Sync** — Syncs `content/posts/` and `content/works/` into WordPress.
- **Pull and Push Sync** — Supports both server-pulled and GitHub Actions-pushed workflows.
- **YAML Front Matter** — Manages slug, technology tags, verification status, and related content in Markdown.

### SEO & Social

- **Article-type-aware JSON-LD** — Emits TechArticle, HowTo, FAQPage, CreativeWork, Course, and ItemList schema where appropriate.
- **Dynamic OGP** — Generates meaningful social previews even without a custom thumbnail.
- **RSS Tech Metadata** — Extends feeds with freshness scores, verification status, and technology tags.

---

## Quick Start

```bash
git clone https://github.com/masakiShito/plainmark.git
cd plainmark
docker compose up -d
```

Theme files: `theme/`  
Content: `content/posts/`, `content/works/`  
Sync setup: WordPress admin → Tools → GitHub Pull Sync

See [`content/README.md`](content/README.md) for content structure details.

---

## Who is this for?

plainmark is for engineers who treat articles as maintained assets, not disposable posts.

It fits especially well if you:

- write tutorials that can become outdated,
- publish code snippets that should still run,
- want your portfolio and technical notes to support each other,
- keep Markdown in GitHub but still want the WordPress editing experience,
- care about showing readers when an article was last checked.

---

## License

GPL-2.0
