/**
 * Main TypeScript entry point
 *
 * @package plainmark
 * @since 0.1.0
 */

// Import modules
import { initNavigation } from './navigation';
import { initSearch } from './search';

// Type declarations for WordPress data
declare global {
  interface Window {
    plainmarkData: {
      ajaxUrl: string;
      nonce: string;
      homeUrl: string;
      themeUrl: string;
      i18n: {
        menu: string;
        search: string;
        close: string;
      };
    };
  }
}

/**
 * DOM Ready handler
 */
function domReady(callback: () => void): void {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback);
  } else {
    callback();
  }
}

/**
 * Initialize all modules
 */
function init(): void {
  // Initialize navigation
  initNavigation();

  // Initialize search
  initSearch();

  // Back to top button
  initBackToTop();

  // Smooth scroll for anchor links
  initSmoothScroll();

  // External link handling
  initExternalLinks();
}

/**
 * Back to top button functionality
 */
function initBackToTop(): void {
  const backToTopButton = document.querySelector<HTMLButtonElement>('.back-to-top');

  if (!backToTopButton) return;

  // Show/hide based on scroll position
  const toggleBackToTop = (): void => {
    if (window.scrollY > 300) {
      backToTopButton.classList.add('is-visible');
    } else {
      backToTopButton.classList.remove('is-visible');
    }
  };

  // Scroll to top on click
  backToTopButton.addEventListener('click', () => {
    window.scrollTo({
      top: 0,
      behavior: 'smooth',
    });
  });

  // Listen for scroll with passive event
  window.addEventListener('scroll', toggleBackToTop, { passive: true });

  // Initial check
  toggleBackToTop();
}

/**
 * Smooth scroll for anchor links
 */
function initSmoothScroll(): void {
  document.querySelectorAll<HTMLAnchorElement>('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', (e: Event) => {
      const href = anchor.getAttribute('href');

      if (!href || href === '#') return;

      const target = document.querySelector(href);

      if (target) {
        e.preventDefault();

        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start',
        });

        // Update URL without jumping
        history.pushState(null, '', href);
      }
    });
  });
}

/**
 * Add appropriate attributes to external links
 */
function initExternalLinks(): void {
  const links = document.querySelectorAll<HTMLAnchorElement>(
    'a[href^="http"]:not([href*="' + window.location.hostname + '"])'
  );

  links.forEach((link) => {
    // Add security attributes
    link.setAttribute('rel', 'noopener noreferrer');
    link.setAttribute('target', '_blank');
  });
}

/**
 * Debounce utility function
 */
export function debounce<T extends (...args: unknown[]) => void>(
  func: T,
  wait: number
): (...args: Parameters<T>) => void {
  let timeout: ReturnType<typeof setTimeout> | null = null;

  return (...args: Parameters<T>): void => {
    if (timeout) {
      clearTimeout(timeout);
    }

    timeout = setTimeout(() => {
      func(...args);
    }, wait);
  };
}

/**
 * Throttle utility function
 */
export function throttle<T extends (...args: unknown[]) => void>(
  func: T,
  limit: number
): (...args: Parameters<T>) => void {
  let inThrottle = false;

  return (...args: Parameters<T>): void => {
    if (!inThrottle) {
      func(...args);
      inThrottle = true;
      setTimeout(() => {
        inThrottle = false;
      }, limit);
    }
  };
}

// Initialize on DOM ready
domReady(init);
