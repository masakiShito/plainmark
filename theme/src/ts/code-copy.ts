/**
 * Code block language label and copy button
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
  go: 'Go',
  html: 'HTML',
  java: 'Java',
  javascript: 'JavaScript',
  json: 'JSON',
  jsx: 'JSX',
  php: 'PHP',
  plaintext: 'Plain text',
  python: 'Python',
  ruby: 'Ruby',
  scss: 'SCSS',
  sql: 'SQL',
  typescript: 'TypeScript',
  tsx: 'TSX',
  xml: 'XML',
  yaml: 'YAML',
};

const LANGUAGE_ALIASES: Record<string, string> = {
  cs: 'csharp',
  html5: 'html',
  js: 'javascript',
  md: 'markdown',
  py: 'python',
  rb: 'ruby',
  sh: 'bash',
  shell: 'bash',
  ts: 'typescript',
  yml: 'yaml',
};

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
  if (/^\s*(#!\/.*\b(sh|bash)|(?:sudo\s+)?(?:npm|yarn|pnpm|git|docker|wp)\s+)/m.test(source)) {
    return 'bash';
  }
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
    if (block.closest('.code-copy')) return;

    const code = block.querySelector<HTMLElement>('code');
    if (!code) return;

    const wrapper = document.createElement('div');
    wrapper.className = 'code-copy';
    block.parentNode?.insertBefore(wrapper, block);

    const header = document.createElement('div');
    header.className = 'code-copy__header';

    const language = document.createElement('span');
    language.className = 'code-copy__language';
    language.textContent = getLanguageLabel(getLanguage(block, code));
    header.appendChild(language);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'code-copy__button';
    setCopyButtonState(button, 'copy', 'Copy code to clipboard', 'Copy');
    header.appendChild(button);

    wrapper.appendChild(header);
    wrapper.appendChild(block);

    let resetTimer: number | undefined;

    button.addEventListener('click', async () => {
      window.clearTimeout(resetTimer);

      try {
        await copyText(code.textContent ?? '');
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
