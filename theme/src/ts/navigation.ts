/**
 * Navigation module
 *
 * @package plainmark
 * @since 0.1.0
 */

const SCROLL_THRESHOLD = 80;

/**
 * Initialize navigation functionality
 */
export function initNavigation(): void {
  initHeaderScroll();
  initMobileMenu();
}

/**
 * Header scroll behavior - shrink and add background on scroll
 */
function initHeaderScroll(): void {
  const header = document.getElementById('site-header');

  if (!header) return;

  const handleScroll = (): void => {
    if (window.scrollY > SCROLL_THRESHOLD) {
      header.classList.add('is-scrolled');
    } else {
      header.classList.remove('is-scrolled');
    }
  };

  // Check initial scroll position
  handleScroll();

  // Listen for scroll events with passive option for better performance
  window.addEventListener('scroll', handleScroll, { passive: true });
}

/**
 * Mobile menu toggle functionality
 */
function initMobileMenu(): void {
  const burger = document.querySelector<HTMLButtonElement>('.site-header__burger');
  const mobileMenu = document.getElementById('mobile-menu');
  const overlay = document.getElementById('mobile-menu-overlay');

  if (!burger || !mobileMenu || !overlay) return;

  /**
   * Open mobile menu
   */
  const openMenu = (): void => {
    burger.classList.add('is-open');
    burger.setAttribute('aria-expanded', 'true');
    burger.setAttribute('aria-label', 'メニューを閉じる');
    mobileMenu.classList.add('is-open');
    mobileMenu.setAttribute('aria-hidden', 'false');
    overlay.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  };

  /**
   * Close mobile menu
   */
  const closeMenu = (): void => {
    burger.classList.remove('is-open');
    burger.setAttribute('aria-expanded', 'false');
    burger.setAttribute('aria-label', 'メニューを開く');
    mobileMenu.classList.remove('is-open');
    mobileMenu.setAttribute('aria-hidden', 'true');
    overlay.classList.remove('is-open');
    document.body.style.overflow = '';
  };

  /**
   * Toggle mobile menu
   */
  const toggleMenu = (): void => {
    const isOpen = burger.classList.contains('is-open');

    if (isOpen) {
      closeMenu();
    } else {
      openMenu();
    }
  };

  // Burger button click
  burger.addEventListener('click', toggleMenu);

  // Overlay click to close
  overlay.addEventListener('click', closeMenu);

  // Escape key to close
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Escape' && burger.classList.contains('is-open')) {
      closeMenu();
      burger.focus();
    }
  });

  // Handle resize - close mobile menu if window becomes larger
  const mediaQuery = window.matchMedia('(min-width: 768px)');

  const handleResize = (e: MediaQueryListEvent | MediaQueryList): void => {
    if (e.matches && burger.classList.contains('is-open')) {
      closeMenu();
    }
  };

  // Initial check
  handleResize(mediaQuery);

  // Listen for changes
  mediaQuery.addEventListener('change', handleResize);
}
