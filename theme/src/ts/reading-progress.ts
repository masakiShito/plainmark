/**
 * Reading progress indicator and estimated reading time
 *
 * @package plainmark
 * @since 0.1.0
 */

const WORDS_PER_MINUTE = 400; // Japanese reading speed
const CHARS_PER_WORD = 1.5; // For Japanese text

/**
 * Calculate estimated reading time in minutes
 */
function calculateReadingTime(content: string): number {
  // Remove HTML tags
  const text = content.replace(/<[^>]*>/g, '');

  // Count characters (works better for Japanese)
  const charCount = text.replace(/\s/g, '').length;

  // Estimate words (Japanese has no spaces, so use character count)
  const wordCount = charCount / CHARS_PER_WORD;

  // Calculate minutes
  const minutes = Math.ceil(wordCount / WORDS_PER_MINUTE);

  return Math.max(1, minutes);
}

/**
 * Initialize reading time display
 */
function initReadingTime(): void {
  const content = document.querySelector('.single-post__content');
  const readingTimeEl = document.querySelector('.reading-time__value');

  if (!content || !readingTimeEl) return;

  const minutes = calculateReadingTime(content.innerHTML);
  readingTimeEl.textContent = `${minutes}`;
}

/**
 * Initialize scroll progress bar
 */
function initScrollProgress(): void {
  const progressBar = document.querySelector<HTMLElement>('.reading-progress__bar');
  const article = document.querySelector('.single-post__content');

  if (!progressBar || !article) return;

  const updateProgress = (): void => {
    const articleRect = article.getBoundingClientRect();
    const articleTop = window.scrollY + articleRect.top;
    const articleHeight = articleRect.height;
    const windowHeight = window.innerHeight;
    const scrollY = window.scrollY;

    // Calculate progress (0 to 100)
    let progress = 0;

    if (scrollY >= articleTop) {
      const scrolledInArticle = scrollY - articleTop + windowHeight;
      progress = (scrolledInArticle / (articleHeight + windowHeight)) * 100;
    }

    progress = Math.min(100, Math.max(0, progress));
    progressBar.style.width = `${progress}%`;

    // Add completed state
    progressBar.parentElement?.classList.toggle('is-complete', progress >= 100);
  };

  // Use requestAnimationFrame for smooth updates
  let ticking = false;
  window.addEventListener(
    'scroll',
    () => {
      if (!ticking) {
        window.requestAnimationFrame(() => {
          updateProgress();
          ticking = false;
        });
        ticking = true;
      }
    },
    { passive: true }
  );

  // Initial update
  updateProgress();
}

/**
 * Initialize all reading progress features
 */
function initReadingProgress(): void {
  initReadingTime();
  initScrollProgress();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initReadingProgress);
} else {
  initReadingProgress();
}
