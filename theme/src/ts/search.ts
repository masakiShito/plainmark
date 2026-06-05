/**
 * Search module
 *
 * @package plainmark
 * @since 0.1.0
 */

/**
 * Search state
 */
interface SearchState {
  isOpen: boolean;
  query: string;
}

const state: SearchState = {
  isOpen: false,
  query: '',
};

/**
 * Initialize search functionality
 */
export function initSearch(): void {
  initSearchForm();
  initSearchOverlay();
  initLiveSearch();
}

/**
 * Search form enhancements
 */
function initSearchForm(): void {
  const searchForms = document.querySelectorAll<HTMLFormElement>('.search-form');

  searchForms.forEach((form) => {
    const input = form.querySelector<HTMLInputElement>('.search-field');

    if (!input) return;

    // Clear button functionality
    addClearButton(input);

    // Focus styles
    input.addEventListener('focus', () => {
      form.classList.add('is-focused');
    });

    input.addEventListener('blur', () => {
      form.classList.remove('is-focused');
    });

    // Prevent empty form submission
    form.addEventListener('submit', (e: Event) => {
      if (!input.value.trim()) {
        e.preventDefault();
        input.focus();
      }
    });
  });
}

/**
 * Add clear button to search input
 */
function addClearButton(input: HTMLInputElement): void {
  const wrapper = input.parentElement;

  if (!wrapper) return;

  // Create clear button
  const clearButton = document.createElement('button');
  clearButton.type = 'button';
  clearButton.className = 'search-clear';
  clearButton.innerHTML = `
    <span class="screen-reader-text">Clear search</span>
    <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true">
      <path d="M4 4l8 8M4 12l8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
  `;
  clearButton.style.display = 'none';

  // Insert after input
  input.insertAdjacentElement('afterend', clearButton);

  // Show/hide based on input value
  input.addEventListener('input', () => {
    clearButton.style.display = input.value ? 'flex' : 'none';
  });

  // Clear input on click
  clearButton.addEventListener('click', () => {
    input.value = '';
    clearButton.style.display = 'none';
    input.focus();

    // Trigger input event for live search
    input.dispatchEvent(new Event('input', { bubbles: true }));
  });
}

/**
 * Search overlay functionality
 */
function initSearchOverlay(): void {
  const searchTriggers = document.querySelectorAll<HTMLButtonElement>('[data-search-toggle]');
  const searchOverlay = document.querySelector<HTMLElement>('.search-overlay');

  if (!searchOverlay) return;

  const searchInput = searchOverlay.querySelector<HTMLInputElement>('.search-field');
  const closeButton = searchOverlay.querySelector<HTMLButtonElement>('.search-close');

  searchTriggers.forEach((trigger) => {
    trigger.addEventListener('click', () => {
      openSearchOverlay(searchOverlay, searchInput);
    });
  });

  if (closeButton) {
    closeButton.addEventListener('click', () => {
      closeSearchOverlay(searchOverlay);
    });
  }

  // Close on escape
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Escape' && state.isOpen) {
      closeSearchOverlay(searchOverlay);
    }
  });

  // Close on overlay background click
  searchOverlay.addEventListener('click', (e: Event) => {
    if (e.target === searchOverlay) {
      closeSearchOverlay(searchOverlay);
    }
  });

  // Keyboard shortcut to open search (Ctrl/Cmd + K)
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();

      if (state.isOpen) {
        closeSearchOverlay(searchOverlay);
      } else {
        openSearchOverlay(searchOverlay, searchInput);
      }
    }
  });
}

/**
 * Open search overlay
 */
function openSearchOverlay(
  overlay: HTMLElement,
  input: HTMLInputElement | null
): void {
  state.isOpen = true;

  overlay.classList.add('is-open');
  overlay.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';

  // Focus input
  if (input) {
    setTimeout(() => {
      input.focus();
    }, 100);
  }
}

/**
 * Close search overlay
 */
function closeSearchOverlay(overlay: HTMLElement): void {
  state.isOpen = false;

  overlay.classList.remove('is-open');
  overlay.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

/**
 * Live search functionality (AJAX-powered)
 */
function initLiveSearch(): void {
  const liveSearchInputs = document.querySelectorAll<HTMLInputElement>(
    '[data-live-search]'
  );

  liveSearchInputs.forEach((input) => {
    const resultsContainer = document.querySelector<HTMLElement>(
      input.dataset.results || '.search-results'
    );

    if (!resultsContainer) return;

    let debounceTimer: ReturnType<typeof setTimeout>;

    input.addEventListener('input', () => {
      const query = input.value.trim();
      state.query = query;

      // Clear previous timer
      clearTimeout(debounceTimer);

      // Clear results if query is empty
      if (!query || query.length < 3) {
        resultsContainer.innerHTML = '';
        resultsContainer.classList.remove('has-results');
        return;
      }

      // Debounce search
      debounceTimer = setTimeout(() => {
        performSearch(query, resultsContainer);
      }, 300);
    });
  });
}

/**
 * Perform AJAX search
 */
async function performSearch(
  query: string,
  container: HTMLElement
): Promise<void> {
  // Check if we have WordPress AJAX data
  if (!window.plainmarkData) {
    console.warn('plainmarkData not available');
    return;
  }

  const { ajaxUrl, nonce } = window.plainmarkData;

  // Show loading state
  container.innerHTML = '<div class="search-loading">Searching...</div>';
  container.classList.add('is-loading');

  try {
    const formData = new FormData();
    formData.append('action', 'plainmark_live_search');
    formData.append('nonce', nonce);
    formData.append('query', query);

    const response = await fetch(ajaxUrl, {
      method: 'POST',
      body: formData,
    });

    if (!response.ok) {
      throw new Error('Search request failed');
    }

    const data = await response.json();

    if (data.success && data.data.results) {
      renderSearchResults(data.data.results, container);
    } else {
      container.innerHTML = '<div class="search-no-results">No results found</div>';
    }
  } catch (error) {
    console.error('Search error:', error);
    container.innerHTML = '<div class="search-error">Search failed. Please try again.</div>';
  } finally {
    container.classList.remove('is-loading');
  }
}

/**
 * Render search results
 */
interface SearchResult {
  id: number;
  title: string;
  url: string;
  excerpt: string;
  type: string;
}

function renderSearchResults(
  results: SearchResult[],
  container: HTMLElement
): void {
  if (results.length === 0) {
    container.innerHTML = '<div class="search-no-results">No results found</div>';
    container.classList.remove('has-results');
    return;
  }

  const html = results
    .map(
      (result) => `
      <article class="search-result">
        <a href="${escapeHtml(result.url)}">
          <h3 class="search-result__title">${escapeHtml(result.title)}</h3>
          <span class="search-result__type">${escapeHtml(result.type)}</span>
          ${result.excerpt ? `<p class="search-result__excerpt">${escapeHtml(result.excerpt)}</p>` : ''}
        </a>
      </article>
    `
    )
    .join('');

  container.innerHTML = html;
  container.classList.add('has-results');
}

/**
 * Escape HTML entities
 */
function escapeHtml(text: string): string {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Highlight search terms in text
 */
export function highlightSearchTerms(
  text: string,
  query: string
): string {
  if (!query) return text;

  const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const regex = new RegExp(`(${escapedQuery})`, 'gi');

  return text.replace(regex, '<mark>$1</mark>');
}
