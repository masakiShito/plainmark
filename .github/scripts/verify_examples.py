#!/usr/bin/env python3
"""Run CI checks declared in article front matter and write results back."""
import os
import re
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path

REPO = Path(__file__).resolve().parents[2]
POSTS = REPO / "content" / "posts"
FM_KEYS = ("ci_status", "ci_checked_at", "ci_run_url")


def split_front_matter(text):
    if not text.startswith("---"):
        return None, text
    parts = text.split("\n---", 1)
    if len(parts) < 2:
        return None, text
    fm = parts[0][3:].lstrip("\n")
    body = parts[1]
    return fm, body


def read_fm_value(fm, key):
    m = re.search(rf'(?m)^{re.escape(key)}:\s*"?([^"\n]*)"?\s*$', fm)
    return m.group(1).strip() if m else ""


def set_fm_value(fm_lines, key, value):
    line = f'{key}: "{value}"'
    pattern = re.compile(rf"^{re.escape(key)}:")
    for i, item in enumerate(fm_lines):
        if pattern.match(item):
            fm_lines[i] = line
            return fm_lines
    fm_lines.append(line)
    return fm_lines


def run(cmd, cwd):
    try:
        result = subprocess.run(cmd, cwd=cwd, shell=True, timeout=900)
        return result.returncode
    except Exception as exc:  # noqa: BLE001
        print(f"  exec error: {exc}", file=sys.stderr)
        return None


def verify(tested_path, test_command):
    target = REPO / tested_path
    if not tested_path or not test_command or not target.is_dir():
        return "skipped"
    if (target / "package.json").is_file():
        if run("npm ci || npm install", target) not in (0,):
            return "error"
    if (target / "requirements.txt").is_file():
        if run("pip install -r requirements.txt", target) != 0:
            return "error"
    code = run(test_command, target)
    if code is None:
        return "error"
    return "passing" if code == 0 else "failing"


def run_url():
    base = os.environ.get("GITHUB_SERVER_URL", "https://github.com")
    repo = os.environ.get("GITHUB_REPOSITORY", "")
    run_id = os.environ.get("GITHUB_RUN_ID", "")
    return f"{base}/{repo}/actions/runs/{run_id}" if repo and run_id else ""


def main():
    changed = False
    now = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    url = run_url()

    for md_file in sorted(POSTS.glob("*.md")):
        text = md_file.read_text(encoding="utf-8")
        fm, body = split_front_matter(text)
        if fm is None:
            continue

        tested_path = read_fm_value(fm, "tested_path")
        test_command = read_fm_value(fm, "test_command")
        if not tested_path or not test_command:
            continue

        status = verify(tested_path, test_command)
        print(f"{md_file.name}: {tested_path} -> {status}")

        fm_lines = fm.rstrip("\n").split("\n")
        set_fm_value(fm_lines, "ci_status", status)
        set_fm_value(fm_lines, "ci_checked_at", now)
        if url:
            set_fm_value(fm_lines, "ci_run_url", url)

        new_text = "---\n" + "\n".join(fm_lines) + "\n---" + body
        if new_text != text:
            md_file.write_text(new_text, encoding="utf-8")
            changed = True

    print(f"changed={'true' if changed else 'false'}")
    with Path(os.environ.get("GITHUB_OUTPUT", "/dev/stdout")).open("a", encoding="utf-8") as output:
        output.write(f"changed={'true' if changed else 'false'}\n")


if __name__ == "__main__":
    main()
