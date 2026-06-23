// 1レンダー内で state はスナップショットとして固定される、という記事の主張をモデル化する。
export function runRender(initialState, handler) {
  let pendingState = initialState;
  const setState = (updater) => {
    pendingState = typeof updater === 'function' ? updater(pendingState) : updater;
  };
  // handler に渡す count は initialState のスナップショット。レンダー途中で変化しない。
  handler(initialState, setState);
  return pendingState; // 次のレンダーの state になる
}
