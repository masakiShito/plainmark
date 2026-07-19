---
title: "Reactのstateは変数ではなく『スナップショット』である"
slug: "react-state-snapshot"
post_type: "post"
status: "publish"
date: "2026-06-10"
article_type: "tutorial"
difficulty: "intermediate"
categories:
  - "React"
technologies:
  - "React"
  - "JavaScript"
verified_status: "unverified"
review_date: "2026-09-10"
tested_path: "examples/react-state"
test_command: "node --test"
ci_status: "passing"
ci_checked_at: "2026-07-19T18:54:01Z"
ci_run_url: "https://github.com/masakiShito/plainmark/actions/runs/29699499432"
---

Reactを書いていると、次のコードが直感に反して見えることがあります。

```tsx
function Counter() {
  const [count, setCount] = useState(0);

  const handleClick = () => {
    setCount(count + 1);
    setCount(count + 1);
    setCount(count + 1);
  };

  return <button onClick={handleClick}>{count}</button>;
}
```

3回更新しているのに、ボタンを1回押して増えるのは1だけです。

この理由は、Reactのstateが書き換え可能な変数ではなく、**レンダー時点のスナップショット**だからです。

## この記事で分かること

- `setState` を連続で呼んでも期待どおり増えない理由
- 非同期処理で古いstateが見える理由
- 更新関数・ref・Effectをどう使い分けるか

## stateはそのレンダー専用の値

最初のレンダーで `count` が0なら、そのレンダーから作られた `handleClick` の中でも `count` はずっと0です。

```tsx
setCount(count + 1); // setCount(1)
setCount(count + 1); // setCount(1)
setCount(count + 1); // setCount(1)
```

3回とも「1にする」と依頼しているため、結果は1になります。

`setCount` は、その場で現在の変数を書き換える処理ではありません。Reactに次のレンダーを予約する処理です。

```tsx
const handleClick = () => {
  console.log(count); // 0
  setCount(count + 1);
  console.log(count); // まだ0
};
```

## 3回増やすなら更新関数を使う

前の値を使って次の値を計算したい場合は、更新関数を渡します。

```tsx
const handleClick = () => {
  setCount((current) => current + 1);
  setCount((current) => current + 1);
  setCount((current) => current + 1);
};
```

Reactは更新を順番に処理します。

```text
0 → 1 → 2 → 3
```

値を渡す更新は「次のstateをこの値にする」、関数を渡す更新は「直前の更新結果から次のstateを計算する」という違いがあります。

## 非同期処理で古い値が見える理由

スナップショットの考え方は `setTimeout` でも重要です。

```tsx
function Counter() {
  const [count, setCount] = useState(0);

  const showLater = () => {
    setTimeout(() => {
      alert(count);
    }, 3000);
  };

  return (
    <>
      <button onClick={() => setCount((c) => c + 1)}>+1</button>
      <button onClick={showLater}>3秒後に表示</button>
    </>
  );
}
```

`count` が5のときに「3秒後に表示」を押し、その後すぐに加算しても、アラートには5が表示されます。

`showLater` は `count = 5` のレンダーから作られた関数だからです。これはバグではなく、クロージャがそのレンダーの値を保持している正常な挙動です。

## 最新値を読みたいならrefを使う

非同期処理から常に最新値を読みたい場合は、refを使えます。

```tsx
function Counter() {
  const [count, setCount] = useState(0);
  const latestCount = useRef(count);

  useEffect(() => {
    latestCount.current = count;
  }, [count]);

  const showLater = () => {
    setTimeout(() => {
      alert(latestCount.current);
    }, 3000);
  };

  return (
    <>
      <button onClick={() => setCount((c) => c + 1)}>+1</button>
      <button onClick={showLater}>3秒後に最新値を表示</button>
    </>
  );
}
```

役割は次のように分けると分かりやすいです。

- 画面表示に使う値はstate
- 再レンダーせず保持したい値はref
- 前の値から更新する場合は更新関数

## useEffectでも同じことが起きる

次のコードでは、最初のレンダーの `count = 0` が保持されます。

```tsx
useEffect(() => {
  const timer = setInterval(() => {
    setCount(count + 1);
  }, 1000);

  return () => clearInterval(timer);
}, []);
```

毎回 `setCount(1)` を呼ぶため、countは1で止まります。

更新関数を使えば、直前の値から計算できます。

```tsx
useEffect(() => {
  const timer = setInterval(() => {
    setCount((current) => current + 1);
  }, 1000);

  return () => clearInterval(timer);
}, []);
```

## 判断基準

Reactでstate周りに迷ったら、次の基準で考えると整理しやすいです。

### 前の値から次の値を計算する

```tsx
setCount((current) => current + 1);
```

### 非同期処理から最新値を読む

```tsx
latestValue.current
```

### 値が変わったら処理をやり直す

```tsx
useEffect(() => {
  // countに応じた処理
}, [count]);
```

### そのイベント時点の値を保持する

何もしなくても、クロージャがそのレンダーの値を保持します。

## まとめ

Reactのstateを理解するうえで重要なのは、次の3点です。

1. stateはレンダーごとのスナップショット
2. `setState` は現在の変数を書き換えず、次のレンダーを予約する
3. 前の値を使う更新では、更新関数を使う

「更新後も値が変わらない」「非同期処理で古い値が見える」と迷ったら、その関数がどのレンダーから作られたのかを確認してください。Reactの挙動をかなり予測しやすくなります。

この記事は、GitHubからWordPressへ同期するplainmarkのコンテンツ管理機能の動作確認も兼ねています。今回はWordPressのフロントコントローラ経由で同期します。
