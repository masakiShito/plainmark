# plainmark

**技術知識のライフサイクルを管理する WordPress テーマ**

書いた記事が古くなっていないか。コードは今でも動くのか。  
plainmark は「書いて終わり」ではなく、**検証 → 鮮度管理 → 更新**のサイクルをテーマレベルでサポートします。

---

## Why plainmark?

### Zenn / Qiita と何が違うのか

| | Zenn / Qiita | plainmark |
|---|---|---|
| 記事の所有権 | プラットフォーム依存 | GitHub の Markdown が原本。あなたが所有する |
| 鮮度管理 | なし。古い記事が検索上位に残り続ける | Freshness スコア + レビュー期限 + 読者報告で自動管理 |
| コードの検証状態 | 不明。動くかどうかは読者まかせ | 検証済み環境を記事に明示。Code Playground で実行確認 |
| ポートフォリオとの関係 | 別サービスが必要 | Blog ↔ Works を双方向リンク。「知識」と「成果物」を接続 |
| 知識の構造化 | タグのみ | Knowledge Map, Learning Paths, Skills で多角的に可視化 |

### Hugo / Gatsby と何が違うのか

plainmark は SSG ではなく WordPress テーマです。

- 管理画面で記事の検証ステータス・レビュー日・シリーズ設定を GUI で管理
- 非エンジニアの協力者、編集者、デザイナーにも使いやすい
- GitHub Markdown 同期で「Git 管理の Markdown」と「WordPress の GUI」を両立

---

## Key Features

### Content Lifecycle（他テーマにない機能群）

- **Freshness System** — 記事ごとの鮮度スコア（0-100）を自動計算。検証日の経過、レビュー期限超過、依存情報の欠如をスコアに反映
- **Verification Card** — 記事冒頭に「動作確認済み / 未検証 / 非推奨」のステータスと検証環境を表示
- **Review Reminder** — レビュー期限が近い記事を管理画面ダッシュボードとメールで通知
- **Reader Feedback** — 読者が「古い情報がある」と報告でき、報告が蓄積すると自動でステータス変更

### Knowledge Structure

- **Blog ↔ Works Bridge** — 記事とポートフォリオの双方向リンク。「この知識を使った制作物」「この制作物に関連する記事」
- **Knowledge Map** — 記事・Works・技術タグ・シリーズの関係をグラフで可視化
- **Learning Paths** — 技術タグ × 難易度 × シリーズから、おすすめの読む順番を自動生成
- **Skills Page** — 記事と Works で使った技術を集計し、アウトプットに基づくスキルシートを自動生成

### Technical Publishing

- **Code Playground** — 記事内で JavaScript / HTML / CSS を実行。検証環境バッジ + 期待出力の表示にも対応
- **Multi-file Code Tabs** — `[code_tabs]` でファイル切り替え付きコードブロック
- **Article Changelog** — 記事ごとの変更履歴を `<details>` で折りたたみ表示
- **Revision Diff** — 直近の記事更新差分をワンクリックで確認
- **Reader Persona** — 読者のレベル / フレームワーク別にコンテンツセクションを出し分け

### Git-native Workflow

- **GitHub Markdown Sync** — `content/posts/` と `content/works/` の Markdown を WordPress へ同期
- **Pull 型 + Push 型** — ロリポップ等の WAF 環境にも対応した 2 つの同期方式
- **YAML Front Matter** — slug、技術タグ、検証ステータス、関連コンテンツを Markdown で管理

### SEO & Social

- **Article-type-aware JSON-LD** — 記事タイプに応じて TechArticle / HowTo / FAQPage / CreativeWork を自動切替
- **Dynamic OGP** — アイキャッチ未設定でもタイトル + カテゴリ + 難易度バッジの OGP 画像を自動生成
- **RSS Tech Metadata** — Freshness スコア、検証ステータス、技術タグを RSS フィードに拡張出力

---

## Quick Start

```bash
git clone https://github.com/masakiShito/plainmark.git
cd plainmark
docker compose up -d
```

Theme files: `theme/`  
Content: `content/posts/`, `content/works/`  
Sync setup: WordPress 管理画面 → ツール → GitHub Pull Sync

→ 詳細は [`content/README.md`](content/README.md) を参照

## License

GPL-2.0
