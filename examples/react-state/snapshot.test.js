import test from 'node:test';
import assert from 'node:assert/strict';
import { runRender } from './snapshot.js';

test('スナップショット値での直接 set を3回 → +1 にしかならない', () => {
  const next = runRender(0, (count, setCount) => {
    setCount(count + 1);
    setCount(count + 1);
    setCount(count + 1);
  });
  assert.equal(next, 1);
});

test('関数形式 set を3回 → +3 に積み上がる', () => {
  const next = runRender(0, (count, setCount) => {
    setCount((prev) => prev + 1);
    setCount((prev) => prev + 1);
    setCount((prev) => prev + 1);
  });
  assert.equal(next, 3);
});
