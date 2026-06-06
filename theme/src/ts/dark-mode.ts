/**
 * Dark mode toggle functionality
 *
 * @package plainmark
 * @since 0.1.0
 */

const STORAGE_KEY = 'plainmark-color-scheme';
const DARK_CLASS = 'is-dark-mode';

type ColorScheme = 'light' | 'dark' | 'system';

/**
 * Get system color scheme preference
 */
function getSystemPreference(): 'light' | 'dark' {
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

/**
 * Get stored color scheme or default to system
 */
function getStoredScheme(): ColorScheme {
  const stored = localStorage.getItem(STORAGE_KEY);
  if (stored === 'light' || stored === 'dark' || stored === 'system') {
    return stored;
  }
  return 'system';
}

/**
 * Get effective color scheme (resolves 'system' to actual value)
 */
function getEffectiveScheme(): 'light' | 'dark' {
  const scheme = getStoredScheme();
  return scheme === 'system' ? getSystemPreference() : scheme;
}

/**
 * Apply color scheme to document
 */
function applyScheme(scheme: 'light' | 'dark'): void {
  document.documentElement.classList.toggle(DARK_CLASS, scheme === 'dark');
  document.documentElement.setAttribute('data-color-scheme', scheme);

  // Update meta theme-color
  const metaTheme = document.querySelector('meta[name="theme-color"]');
  if (metaTheme) {
    metaTheme.setAttribute('content', scheme === 'dark' ? '#111111' : '#ffffff');
  }
}

/**
 * Update toggle button state
 */
function updateToggleButton(button: HTMLButtonElement, isDark: boolean): void {
  button.setAttribute('aria-pressed', String(isDark));
  button.setAttribute(
    'aria-label',
    isDark ? 'Switch to light mode' : 'Switch to dark mode'
  );
}

/**
 * Initialize dark mode
 */
function initDarkMode(): void {
  const toggleButtons = document.querySelectorAll<HTMLButtonElement>('[data-dark-mode-toggle]');

  if (toggleButtons.length === 0) return;

  // Apply initial scheme
  const effectiveScheme = getEffectiveScheme();
  applyScheme(effectiveScheme);

  // Set up toggle buttons
  toggleButtons.forEach((button) => {
    updateToggleButton(button, effectiveScheme === 'dark');

    button.addEventListener('click', () => {
      const current = getEffectiveScheme();
      const next = current === 'dark' ? 'light' : 'dark';

      localStorage.setItem(STORAGE_KEY, next);
      applyScheme(next);
      updateToggleButton(button, next === 'dark');

      // Update all other toggle buttons
      toggleButtons.forEach((btn) => {
        if (btn !== button) {
          updateToggleButton(btn, next === 'dark');
        }
      });
    });
  });

  // Listen for system preference changes
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    const stored = getStoredScheme();
    if (stored === 'system') {
      const scheme = e.matches ? 'dark' : 'light';
      applyScheme(scheme);
      toggleButtons.forEach((button) => {
        updateToggleButton(button, scheme === 'dark');
      });
    }
  });
}

// Apply scheme immediately to prevent flash
(function () {
  const scheme = getEffectiveScheme();
  document.documentElement.classList.toggle(DARK_CLASS, scheme === 'dark');
  document.documentElement.setAttribute('data-color-scheme', scheme);
})();

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDarkMode);
} else {
  initDarkMode();
}
