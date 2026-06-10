# plainmark

A WordPress theme for engineers who want to manage technical knowledge, verified code examples, and portfolio case studies in one place.

## Key features

- Technical article metadata, TOC, series, reading progress and code utilities
- Markdown import/export with YAML front matter
- Article verification status, tested environment and review dates
- Bidirectional relationships between Blog posts and Portfolio Works
- GitHub-managed Markdown content synchronized by GitHub Actions
- Dynamic OGP images and article-type-aware JSON-LD
- Changelog, reader context switching and multi-file code tabs
- Knowledge Map and automatic Skills page

## GitHub-managed content

Markdown files under `content/posts/` and `content/works/` can be synchronized to WordPress through `.github/workflows/sync-content.yml`.

See [`content/README.md`](content/README.md) for setup, security notes and front matter examples.

## Theme

The WordPress theme is located in [`theme/`](theme/).
