/**
 * Enhanced code block with language label, copy button, line numbers, and terminal mode
 *
 * @package plainmark
 * @since 0.1.0
 */

const RESET_DELAY = 2000;

const COPY_ICONS = {
  copy: `
    <svg viewBox="0 0 16 16" aria-hidden="true">
      <rect x="5" y="5" width="8" height="8" rx="1.5"></rect>
      <path d="M3 11H2.5A1.5 1.5 0 0 1 1 9.5v-7A1.5 1.5 0 0 1 2.5 1h7A1.5 1.5 0 0 1 11 2.5V3"></path>
    </svg>
  `,
  copied: `
    <svg viewBox="0 0 16 16" aria-hidden="true">
      <path d="m3 8.5 3 3 7-7"></path>
    </svg>
  `,
  failed: `
    <svg viewBox="0 0 16 16" aria-hidden="true">
      <path d="m4 4 8 8M12 4l-8 8"></path>
    </svg>
  `,
};

const LANGUAGE_LABELS: Record<string, string> = {
  bash: 'Shell',
  c: 'C',
  cpp: 'C++',
  csharp: 'C#',
  css: 'CSS',
  diff: 'Diff',
  go: 'Go',
  html: 'HTML',
  java: 'Java',
  javascript: 'JavaScript',
  json: 'JSON',
  jsx: 'JSX',
  kotlin: 'Kotlin',
  markdown: 'Markdown',
  php: 'PHP',
  plaintext: 'Plain text',
  python: 'Python',
  ruby: 'Ruby',
  rust: 'Rust',
  scss: 'SCSS',
  sql: 'SQL',
  swift: 'Swift',
  terminal: 'Terminal',
  typescript: 'TypeScript',
  tsx: 'TSX',
  xml: 'XML',
  yaml: 'YAML',
};

const LANGUAGE_ALIASES: Record<string, string> = {
  cs: 'csharp',
  console: 'terminal',
  html5: 'html',
  js: 'javascript',
  md: 'markdown',
  py: 'python',
  rb: 'ruby',
  sh: 'bash',
  shell: 'bash',
  ts: 'typescript',
  yml: 'yaml',
  zsh: 'bash',
};

// Languages that should display as terminal
const TERMINAL_LANGUAGES = ['bash', 'shell', 'terminal', 'console', 'zsh', 'sh'];

/**
 * Copy text with a fallback for browsers without the Clipboard API.
 */
async function copyText(text: string): Promise<void> {
  if (navigator.clipboard && window.isSecureContext) {
    await navigator.clipboard.writeText(text);
    return;
  }

  const textarea = document.createElement('textarea');
  textarea.value = text;
  textarea.setAttribute('readonly', '');
  textarea.style.position = 'fixed';
  textarea.style.opacity = '0';
  document.body.appendChild(textarea);
  textarea.select();

  const copied = document.execCommand('copy');
  textarea.remove();

  if (!copied) {
    throw new Error('Copy command failed');
  }
}

function normalizeLanguage(language: string): string {
  const normalized = language.trim().toLowerCase();
  return LANGUAGE_ALIASES[normalized] ?? normalized;
}

function getClassLanguage(block: HTMLElement, code: HTMLElement): string {
  const classNames = [...block.classList, ...code.classList];
  const languageClass = classNames.find((className) => className.startsWith('language-'));
  return languageClass ? languageClass.replace('language-', '') : '';
}

/**
 * Lightweight detection for labels only. Manual editor settings take priority.
 */
function detectLanguage(code: string): string {
  const source = code.trim();

  if (!source) return 'plaintext';

  // Diff detection
  if (/^[-+@]{1,3}\s|^diff --git/m.test(source)) return 'diff';

  if (/^<\?php|\$[A-Za-z_]\w*\s*=|->\w+\(/m.test(source)) return 'php';
  if (/^\s*[<{][\s\S]*[>}]\s*$/.test(source)) {
    try {
      JSON.parse(source);
      return 'json';
    } catch {
      if (/<\/?[A-Za-z][^>]*>/.test(source)) return 'html';
    }
  }
  if (/\b(interface|type|enum)\s+\w+|:\s*(string|number|boolean)(\[\])?/m.test(source)) {
    return 'typescript';
  }
  if (/\b(const|let|var|function|import|export)\b|=>|console\.log/m.test(source)) {
    return 'javascript';
  }
  if (/^\s*(@mixin|@include|\$[\w-]+\s*:)|&[:.\[]/m.test(source)) return 'scss';
  if (/[.#]?[\w-]+\s*\{[\s\S]*[\w-]+\s*:\s*[^;]+;/m.test(source)) return 'css';
  if (/\b(SELECT|INSERT INTO|UPDATE|DELETE FROM|CREATE TABLE)\b/i.test(source)) return 'sql';
  if (/^\s*(def|class)\s+\w+.*:|^\s*(from|import)\s+[\w.]+/m.test(source)) return 'python';
  if (/^\s*(package\s+main|func\s+\w+|import\s+\()/m.test(source)) return 'go';
  if (/^\s*(#!\/.*\b(sh|bash)|(?:sudo\s+)?(?:npm|yarn|pnpm|git|docker|wp|curl|wget)\s+)/m.test(source)) {
    return 'bash';
  }
  if (/^\s*\$\s+\w+/m.test(source)) return 'terminal';
  if (/^\s*---|^[\w.-]+:\s+.+$/m.test(source)) return 'yaml';

  return 'plaintext';
}

function getLanguage(block: HTMLElement, code: HTMLElement): string {
  const configured = block.dataset.codeLanguage;

  if (configured && configured !== 'auto') {
    return normalizeLanguage(configured);
  }

  const classLanguage = getClassLanguage(block, code);
  return classLanguage ? normalizeLanguage(classLanguage) : detectLanguage(code.textContent ?? '');
}

function getLanguageLabel(language: string): string {
  return LANGUAGE_LABELS[language] ?? language.toUpperCase();
}

function isTerminalLanguage(language: string): boolean {
  return TERMINAL_LANGUAGES.includes(language);
}

function isDiffLanguage(language: string): boolean {
  return language === 'diff';
}

/**
 * Parse filename from code block attributes or first line comment
 */
function getFilename(block: HTMLElement, code: HTMLElement): string | null {
  // Check data attribute first
  const filename = block.dataset.filename || block.dataset.codeFilename;
  if (filename) return filename;

  // Check first line for filename comment pattern
  const firstLine = (code.textContent ?? '').split('\n')[0];
  const filenameMatch = firstLine.match(/^(?:\/\/|#|\/\*|\<!--)\s*(?:file(?:name)?[:=]?\s*)?([^\s*/>]+\.[a-z0-9]+)/i);
  if (filenameMatch) return filenameMatch[1];

  return null;
}

/**
 * Get highlighted line numbers from data attribute
 */
function getHighlightedLines(block: HTMLElement): Set<number> {
  const highlight = block.dataset.highlight || block.dataset.line || '';
  const lines = new Set<number>();

  if (!highlight) return lines;

  // Parse formats like "1,3,5-7" or "1-3"
  highlight.split(',').forEach((part) => {
    const range = part.trim().match(/^(\d+)(?:-(\d+))?$/);
    if (range) {
      const start = parseInt(range[1], 10);
      const end = range[2] ? parseInt(range[2], 10) : start;
      for (let i = start; i <= end; i++) {
        lines.add(i);
      }
    }
  });

  return lines;
}

/**
 * Add line numbers and highlighting to code
 */
function processCodeLines(code: HTMLElement, language: string, highlightedLines: Set<number>): void {
  const content = code.textContent ?? '';
  const lines = content.split('\n');

  // Remove trailing empty line if exists
  if (lines[lines.length - 1] === '') {
    lines.pop();
  }

  const isDiff = isDiffLanguage(language);
  const isTerminal = isTerminalLanguage(language);

  const processedLines = lines.map((line, index) => {
    const lineNum = index + 1;
    const isHighlighted = highlightedLines.has(lineNum);

    // Diff line coloring
    let diffClass = '';
    if (isDiff) {
      if (line.startsWith('+') && !line.startsWith('+++')) {
        diffClass = ' code-line--added';
      } else if (line.startsWith('-') && !line.startsWith('---')) {
        diffClass = ' code-line--removed';
      } else if (line.startsWith('@')) {
        diffClass = ' code-line--info';
      }
    }

    // Terminal prompt styling
    let lineContent = escapeHtml(line);
    if (isTerminal && line.match(/^\s*\$/)) {
      lineContent = `<span class="code-prompt">$</span>${escapeHtml(line.replace(/^\s*\$\s?/, ''))}`;
    }

    const highlightClass = isHighlighted ? ' code-line--highlight' : '';

    return `<span class="code-line${highlightClass}${diffClass}" data-line="${lineNum}"><span class="code-line-number">${lineNum}</span><span class="code-line-content">${lineContent || ' '}</span></span>`;
  });

  code.innerHTML = processedLines.join('\n');
}

function escapeHtml(text: string): string {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function setCopyButtonState(
  button: HTMLButtonElement,
  state: keyof typeof COPY_ICONS,
  label: string,
  text: string
): void {
  button.innerHTML = `${COPY_ICONS[state]}<span>${text}</span>`;
  button.classList.toggle('is-copied', state === 'copied');
  button.classList.toggle('is-failed', state === 'failed');
  button.setAttribute('aria-label', label);
  button.title = label;
}

/**
 * Add a language header and copy button to each code block in a single post.
 */
function initCodeCopy(): void {
  const blocks = document.querySelectorAll<HTMLElement>(
    '.single-post__content pre, .single-post__content .wp-block-code'
  );

  blocks.forEach((block) => {
    // A pre nested inside a Gutenberg code block is handled by its parent.
    if (block.matches('pre') && block.parentElement?.closest('.wp-block-code')) return;
    if (block.closest('.code-block')) return;

    const code = block.querySelector<HTMLElement>('code');
    if (!code) return;

    const originalText = code.textContent ?? '';
    const language = getLanguage(block, code);
    const filename = getFilename(block, code);
    const highlightedLines = getHighlightedLines(block);
    const isTerminal = isTerminalLanguage(language);
    const isDiff = isDiffLanguage(language);

    // Process lines with numbers and highlighting
    processCodeLines(code, language, highlightedLines);

    // Create wrapper
    const wrapper = document.createElement('div');
    wrapper.className = `code-block${isTerminal ? ' code-block--terminal' : ''}${isDiff ? ' code-block--diff' : ''}`;
    block.parentNode?.insertBefore(wrapper, block);

    // Create header
    const header = document.createElement('div');
    header.className = 'code-block__header';

    // Left side: filename or language
    const labelContainer = document.createElement('div');
    labelContainer.className = 'code-block__label';

    if (filename) {
      const filenameEl = document.createElement('span');
      filenameEl.className = 'code-block__filename';
      filenameEl.textContent = filename;
      labelContainer.appendChild(filenameEl);
    }

    const languageEl = document.createElement('span');
    languageEl.className = 'code-block__language';
    languageEl.textContent = isTerminal ? 'Terminal' : getLanguageLabel(language);
    labelContainer.appendChild(languageEl);

    header.appendChild(labelContainer);

    // Terminal dots decoration
    if (isTerminal) {
      const dots = document.createElement('div');
      dots.className = 'code-block__dots';
      dots.innerHTML = '<span></span><span></span><span></span>';
      header.insertBefore(dots, header.firstChild);
    }

    // Copy button
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'code-block__copy';
    setCopyButtonState(button, 'copy', 'Copy code to clipboard', 'Copy');
    header.appendChild(button);

    wrapper.appendChild(header);
    wrapper.appendChild(block);

    let resetTimer: number | undefined;

    button.addEventListener('click', async () => {
      window.clearTimeout(resetTimer);

      try {
        await copyText(originalText);
        setCopyButtonState(button, 'copied', 'Code copied to clipboard', 'Copied');
      } catch {
        setCopyButtonState(button, 'failed', 'Failed to copy code', 'Failed');
      }

      resetTimer = window.setTimeout(() => {
        setCopyButtonState(button, 'copy', 'Copy code to clipboard', 'Copy');
      }, RESET_DELAY);
    });
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initCodeCopy);
} else {
  initCodeCopy();
}
