# plainmark

**The only WordPress theme that tracks whether your technical articles still work.**

Most blog themes help you publish. plainmark helps you maintain.  
Built for engineers who care about the accuracy of what they've written.

---

## Current status / Beta

plainmark is currently in **beta**.

The repository already contains the core implementation for the theme, the bundled `plainmark-core` plugin, GitHub-managed Markdown content, editorial lifecycle metadata, portfolio linkage, structured data, and local Docker development.

However, the project should still be treated as an actively evolving product rather than a stable public release. Some features are implemented but still need broader real-world verification, documentation, and regression testing across WordPress, hosting, and content-sync environments.

Recommended expectations:

- Use it for personal sites, prototypes, and controlled production experiments.
- Review code and test the deployment flow before using it for a public production site.
- Expect breaking changes while the theme/plugin boundary and sync workflow continue to stabilize.
- Prefer the GitHub Pull Sync flow for shared hosting environments such as Lolipop.

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

## Feature status

| Area | Feature | Status | Notes |
|---|---|---:|---|
| Theme foundation | Classic WordPress theme templates | Implemented | Main templates, archive pages, single posts, portfolio pages, and custom routes exist. |
| Local development | Docker Compose environment | Implemented | WordPress, MySQL, and phpMyAdmin are provided for local development. |
| Core data model | `plainmark-core` plugin | In progress | Core modules are being migrated into the plugin while the theme keeps compatibility fallbacks. |
| Portfolio | Portfolio custom post type | Implemented | Used for Works and output-based portfolio pages. |
| Technology taxonomy | Shared technology tags | Implemented | Used across posts, works, maps, learning paths, skills, RSS, and JSON-LD. |
| Markdown workflow | Markdown import/export | Implemented | Supports front matter-driven posts and works. |
| GitHub workflow | GitHub Pull Sync | Implemented | Recommended sync model for shared hosting. See `content/README.md`. |
| GitHub workflow | GitHub Actions Push Sync | Implemented / Legacy-compatible | REST endpoint exists, but pull sync is preferred for environments where inbound requests are blocked. |
| Content lifecycle | Verification status | Implemented | Supports verified, unverified, review due, and deprecated states. |
| Content lifecycle | Freshness score | Implemented | Calculates freshness from verification, review dates, dependency metadata, and feedback-related signals. |
| Content lifecycle | Review reminders / inventory | Implemented / Needs verification | Admin-side inventory exists, but broader operational testing is still needed. |
| Content lifecycle | Reader feedback | Implemented / Needs verification | AJAX feedback exists; deeper freshness integration and abuse protection should be validated. |
| Reliability metadata | Dependency watcher | Implemented / Needs verification | Checks npm and PyPI major versions and feeds dependency status into freshness. |
| Knowledge structure | Blog ↔ Works bridge | Implemented | Supports direct and reverse relations between articles and portfolio works. |
| Knowledge structure | Knowledge Map | Implemented / Needs verification | Route and template exist; real-content graph quality should be tested. |
| Knowledge structure | Technology Map | Implemented / Needs verification | Route and template exist; visualization behavior should be tested with larger content sets. |
| Knowledge structure | Learning Paths | Implemented / Needs verification | Generated from technology tags, difficulty, series, and freshness. |
| Knowledge structure | Skills Page | Implemented | Aggregates technologies across articles and works. |
| Knowledge structure | Skills README export | Implemented | Exports a GitHub Profile README-style Markdown summary. |
| Technical publishing | Code Playground | Implemented / Needs security review | Runs JavaScript / HTML / CSS in sandboxed article embeds. |
| Technical publishing | Multi-file Code Tabs | Implemented | Provides `[code_tabs]` and `[code_tab]` shortcodes. |
| Technical publishing | Article Changelog | Implemented | Uses article metadata and renders update history. |
| Technical publishing | Revision Diff | Implemented / Needs verification | Uses WordPress revisions to display latest content diff. |
| Technical publishing | Reader Persona sections | Implemented | Provides `[persona]` and legacy `[context]` shortcodes. |
| SEO | Article-aware JSON-LD | Implemented | Supports TechArticle, HowTo, FAQPage, CreativeWork, Course, ItemList, and WebSite schemas. |
| Social | Dynamic OGP | Implemented / Needs verification | OGP generation exists; social preview behavior should be checked in real crawlers. |
| Feeds | RSS tech metadata | Implemented | Adds freshness, verification, difficulty, and technology metadata to RSS. |
| Quality | PHPCS workflow | Implemented | Coding standards workflow exists. Automated functional tests are not yet comprehensive. |
| Release | Versioned public release | Not yet | No stable release has been cut yet. |

---

## Theme and core plugin responsibilities

plainmark is split between a presentation-focused theme and a bundled core plugin.

### Theme: `theme/`

The theme owns the public reading experience and visual presentation.

Responsibilities:

- Front-end templates for posts, archives, pages, and portfolio works.
- Layout, typography, navigation, dark mode, article UI, and responsive styling.
- Public-facing feature pages such as Knowledge Map, Learning Paths, Skills, and Technology Map.
- Front-end rendering for verification cards, freshness badges, related content, code tabs, playgrounds, changelogs, and article enhancements.
- SEO and social output that is directly tied to page rendering, such as JSON-LD, OGP, and RSS metadata.
- Compatibility fallbacks while modules are being migrated into `plainmark-core`.

### Core plugin: `plugins/plainmark-core/`

The core plugin owns data structures and editorial governance that should survive theme changes.

Responsibilities:

- Custom post types and taxonomies, especially Portfolio / Works and Technology.
- Article, work, snippet, and sync-related metadata registration.
- Markdown import/export and front matter normalization.
- GitHub content sync modules.
- Editorial inventory and admin settings screens.
- Content bridge metadata between articles and works.
- Snippet library and other reusable data models.

### Current boundary note

Some modules still exist in both theme-integrated and plugin-integrated forms for compatibility. The long-term direction is:

- keep persistent content/data features in `plainmark-core`, and
- keep rendering, layout, and reader-facing presentation in the theme.

---

## Minimum verification checklist

Before treating a deployment as stable, run through this checklist.

### Local setup

- [ ] Copy `.env.example` to `.env` and set database values.
- [ ] Run `docker compose up -d`.
- [ ] Open WordPress locally and complete the initial setup.
- [ ] Activate the `plainmark` theme.
- [ ] Activate the bundled `plainmark-core` plugin.
- [ ] Confirm there are no fatal errors in the WordPress admin area.

### Build and code quality

- [ ] Run Composer install if PHPCS dependencies are needed.
- [ ] Run `composer lint` or the PHPCS workflow equivalent.
- [ ] Run `npm install` inside `theme/`.
- [ ] Run `npm run build` inside `theme/`.
- [ ] Confirm generated CSS and JS assets load on the front end.

### Content model

- [ ] Create or import one article from `content/posts/`.
- [ ] Create or import one work from `content/works/`.
- [ ] Confirm the Technology taxonomy is shared across posts and works.
- [ ] Confirm article front matter fields are saved correctly.
- [ ] Confirm work front matter fields are saved correctly.

### GitHub content sync

- [ ] Open WordPress admin → Tools → GitHub Pull Sync.
- [ ] Confirm repository, branch, and content paths.
- [ ] Run a pull sync from GitHub.
- [ ] Confirm existing content updates by matching `slug` and `post_type`.
- [ ] Confirm GitHub path, SHA, and synced timestamp metadata are stored.

### Article lifecycle

- [ ] Set `verified_status`, `verified_date`, `verified_env`, and `review_date` on an article.
- [ ] Confirm the verification card appears on the article page.
- [ ] Confirm an expired `review_date` changes the public state to review due.
- [ ] Add dependency metadata and confirm dependency status is calculated on save.
- [ ] Confirm Freshness Score changes when verification or dependency data changes.

### Knowledge and portfolio structure

- [ ] Link an article to a work with `related_works`.
- [ ] Link a work back to an article with `related_posts`.
- [ ] Confirm related cards render on both article and work pages.
- [ ] Open `/knowledge-map/` and confirm content relationships render.
- [ ] Open `/technology-map/` and confirm technology relationships render.
- [ ] Open `/learning-paths/` and confirm paths are generated from real content.
- [ ] Open `/skills/` and confirm technology counts include articles and works.

### Technical publishing

- [ ] Add a `[code_tabs]` block and confirm tab switching works.
- [ ] Add a `[playground]` block and confirm JavaScript execution works in the sandbox.
- [ ] Add an article changelog and confirm it renders.
- [ ] Update an article with revisions enabled and confirm the revision diff UI works.
- [ ] Add a `[persona]` section and confirm it renders correctly.

### SEO, social, and feeds

- [ ] Inspect article HTML and confirm JSON-LD is valid JSON.
- [ ] Check tutorial articles for HowTo schema when appropriate.
- [ ] Check error-solution articles for FAQPage schema when appropriate.
- [ ] Confirm portfolio pages output CreativeWork schema.
- [ ] Confirm OGP tags are generated for posts and works.
- [ ] Open the RSS feed and confirm plainmark metadata appears.

### Deployment

- [ ] Confirm production hosting supports the required PHP and WordPress versions.
- [ ] Deploy theme files.
- [ ] Deploy and activate `plainmark-core`.
- [ ] Flush permalinks after activation.
- [ ] Confirm custom routes such as `/knowledge-map/`, `/learning-paths/`, and `/skills/` work.
- [ ] Run a production sync test with one article and one work before importing all content.

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
