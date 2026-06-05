/**
 * Navigation module
 *
 * @package plainmark
 * @since 0.1.0
 */

/**
 * Mobile navigation state
 */
interface NavigationState {
  isOpen: boolean;
  activeSubmenu: HTMLElement | null;
}

const state: NavigationState = {
  isOpen: false,
  activeSubmenu: null,
};

/**
 * Initialize navigation functionality
 */
export function initNavigation(): void {
  initMobileMenu();
  initSubmenus();
  initKeyboardNavigation();
}

/**
 * Mobile menu toggle functionality
 */
function initMobileMenu(): void {
  const menuToggle = document.querySelector<HTMLButtonElement>('.menu-toggle');
  const primaryMenu = document.querySelector<HTMLElement>('.primary-menu');

  if (!menuToggle || !primaryMenu) return;

  menuToggle.addEventListener('click', () => {
    state.isOpen = !state.isOpen;

    menuToggle.setAttribute('aria-expanded', String(state.isOpen));
    primaryMenu.classList.toggle('is-open', state.isOpen);

    // Trap focus within menu when open
    if (state.isOpen) {
      primaryMenu.focus();
    }
  });

  // Close menu on escape key
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Escape' && state.isOpen) {
      closeMobileMenu(menuToggle, primaryMenu);
    }
  });

  // Close menu when clicking outside
  document.addEventListener('click', (e: Event) => {
    const target = e.target as HTMLElement;

    if (
      state.isOpen &&
      !primaryMenu.contains(target) &&
      !menuToggle.contains(target)
    ) {
      closeMobileMenu(menuToggle, primaryMenu);
    }
  });

  // Handle resize - close mobile menu if window becomes larger
  const mediaQuery = window.matchMedia('(min-width: 768px)');

  mediaQuery.addEventListener('change', (e: MediaQueryListEvent) => {
    if (e.matches && state.isOpen) {
      closeMobileMenu(menuToggle, primaryMenu);
    }
  });
}

/**
 * Close mobile menu
 */
function closeMobileMenu(
  toggle: HTMLButtonElement,
  menu: HTMLElement
): void {
  state.isOpen = false;
  toggle.setAttribute('aria-expanded', 'false');
  menu.classList.remove('is-open');
}

/**
 * Submenu toggle functionality
 */
function initSubmenus(): void {
  const submenuToggles = document.querySelectorAll<HTMLButtonElement>('.submenu-toggle');

  submenuToggles.forEach((toggle) => {
    const parentItem = toggle.closest('.has-submenu');
    const submenu = parentItem?.querySelector<HTMLElement>('.sub-menu');

    if (!submenu) return;

    toggle.addEventListener('click', (e: Event) => {
      e.preventDefault();

      const isExpanded = toggle.getAttribute('aria-expanded') === 'true';

      // Close other open submenus
      if (!isExpanded && state.activeSubmenu && state.activeSubmenu !== submenu) {
        closeSubmenu(state.activeSubmenu);
      }

      if (isExpanded) {
        closeSubmenu(submenu);
        state.activeSubmenu = null;
      } else {
        openSubmenu(submenu, toggle);
        state.activeSubmenu = submenu;
      }
    });
  });

  // Close submenus when clicking outside
  document.addEventListener('click', (e: Event) => {
    const target = e.target as HTMLElement;

    if (state.activeSubmenu && !target.closest('.has-submenu')) {
      closeSubmenu(state.activeSubmenu);
      state.activeSubmenu = null;
    }
  });
}

/**
 * Open submenu
 */
function openSubmenu(submenu: HTMLElement, toggle: HTMLButtonElement): void {
  submenu.style.display = 'block';
  toggle.setAttribute('aria-expanded', 'true');

  // Animate in
  requestAnimationFrame(() => {
    submenu.style.opacity = '1';
    submenu.style.transform = 'translateY(0)';
  });
}

/**
 * Close submenu
 */
function closeSubmenu(submenu: HTMLElement): void {
  const toggle = submenu
    .closest('.has-submenu')
    ?.querySelector<HTMLButtonElement>('.submenu-toggle');

  if (toggle) {
    toggle.setAttribute('aria-expanded', 'false');
  }

  submenu.style.opacity = '0';
  submenu.style.transform = 'translateY(-10px)';

  // Hide after animation
  setTimeout(() => {
    submenu.style.display = '';
  }, 200);
}

/**
 * Keyboard navigation for menus
 */
function initKeyboardNavigation(): void {
  const primaryMenu = document.querySelector<HTMLElement>('.primary-menu');

  if (!primaryMenu) return;

  const menuItems = primaryMenu.querySelectorAll<HTMLElement>(
    ':scope > li > a, :scope > li > button'
  );

  menuItems.forEach((item, index) => {
    item.addEventListener('keydown', (e: KeyboardEvent) => {
      const isRTL = document.documentElement.dir === 'rtl';

      switch (e.key) {
        case 'ArrowRight':
          e.preventDefault();
          focusMenuItem(menuItems, isRTL ? index - 1 : index + 1);
          break;

        case 'ArrowLeft':
          e.preventDefault();
          focusMenuItem(menuItems, isRTL ? index + 1 : index - 1);
          break;

        case 'ArrowDown':
          e.preventDefault();
          focusSubmenuItem(item, 'first');
          break;

        case 'ArrowUp':
          e.preventDefault();
          focusSubmenuItem(item, 'last');
          break;

        case 'Home':
          e.preventDefault();
          focusMenuItem(menuItems, 0);
          break;

        case 'End':
          e.preventDefault();
          focusMenuItem(menuItems, menuItems.length - 1);
          break;
      }
    });
  });
}

/**
 * Focus menu item by index
 */
function focusMenuItem(items: NodeListOf<HTMLElement>, index: number): void {
  // Wrap around
  if (index < 0) {
    index = items.length - 1;
  } else if (index >= items.length) {
    index = 0;
  }

  items[index].focus();
}

/**
 * Focus first or last item in submenu
 */
function focusSubmenuItem(
  parentItem: HTMLElement,
  position: 'first' | 'last'
): void {
  const parentLi = parentItem.closest('.has-submenu');

  if (!parentLi) return;

  const submenu = parentLi.querySelector<HTMLElement>('.sub-menu');

  if (!submenu) return;

  const submenuLinks = submenu.querySelectorAll<HTMLAnchorElement>('a');

  if (submenuLinks.length > 0) {
    // Open submenu if needed
    const toggle = parentLi.querySelector<HTMLButtonElement>('.submenu-toggle');

    if (toggle && toggle.getAttribute('aria-expanded') !== 'true') {
      toggle.click();
    }

    // Focus item
    if (position === 'first') {
      submenuLinks[0].focus();
    } else {
      submenuLinks[submenuLinks.length - 1].focus();
    }
  }
}
