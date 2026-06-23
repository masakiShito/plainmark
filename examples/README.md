# examples/

記事のコードサンプルを CI で検証可能な形で置くディレクトリ。

- 記事の front matter で `tested_path`(このディレクトリ配下の相対パス)と `test_command` を指定する。
- `package.json` があれば `npm ci`(無ければ `npm install`)、`requirements.txt` があれば `pip install -r` を実行後、`test_command` を走らせる。
- 終了コード 0 → passing / 非0 → failing / セットアップ失敗 → error / ディレクトリ無し or 指定無し → skipped。
- 結果は verify-code.yml が記事の front matter(`ci_status`/`ci_checked_at`/`ci_run_url`)へ書き戻す。

例: 記事に `tested_path: "examples/react-state"` / `test_command: "npm test"` と書き、`examples/react-state/` にコードとテストを置く。

## react-state

`react-state/` は CI 連携の最初の実例。React の state スナップショット挙動を検証するテストが含まれる。
