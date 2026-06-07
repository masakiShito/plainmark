/**
 * Article enhancements for tech blog
 * - Heading anchor links
 * - Share buttons
 * - Feedback buttons
 *
 * @package plainmark
 * @since 0.1.0
 */

/**
 * Add anchor links to headings
 */
function initHeadingAnchors(): void {
  const headings = document.querySelectorAll<HTMLHeadingElement>(
    '.single-post__content h2[id], .single-post__content h3[id]'
  );

  headings.forEach((heading) => {
    const id = heading.id;
    if (!id) return;

    // Create anchor link
    const anchor = document.createElement('a');
    anchor.href = `#${id}`;
    anchor.className = 'heading-anchor';
    anchor.setAttribute('aria-label', 'この見出しへのリンクをコピー');
    anchor.innerHTML = `
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
      </svg>
    `;

    // Click to copy URL
    anchor.addEventListener('click', async (e) => {
      e.preventDefault();
      const url = `${window.location.origin}${window.location.pathname}#${id}`;

      try {
        await navigator.clipboard.writeText(url);
        anchor.classList.add('is-copied');
        setTimeout(() => anchor.classList.remove('is-copied'), 2000);
      } catch {
        // Fallback: just navigate to the anchor
        window.location.hash = id;
      }
    });

    heading.appendChild(anchor);
  });
}

/**
 * Initialize share buttons
 */
function initShareButtons(): void {
  const shareButtons = document.querySelectorAll<HTMLButtonElement>('[data-share]');

  shareButtons.forEach((button) => {
    button.addEventListener('click', async () => {
      const shareType = button.dataset.share;
      const url = encodeURIComponent(window.location.href);
      const title = encodeURIComponent(document.title);

      switch (shareType) {
        case 'twitter':
          window.open(
            `https://twitter.com/intent/tweet?url=${url}&text=${title}`,
            '_blank',
            'width=550,height=420'
          );
          break;

        case 'hatena':
          window.open(
            `https://b.hatena.ne.jp/entry/s/${window.location.host}${window.location.pathname}`,
            '_blank',
            'width=550,height=420'
          );
          break;

        case 'copy':
          try {
            await navigator.clipboard.writeText(window.location.href);
            button.classList.add('is-copied');
            const originalText = button.querySelector('.share-button__text');
            if (originalText) {
              const original = originalText.textContent;
              originalText.textContent = 'コピーしました';
              setTimeout(() => {
                originalText.textContent = original;
                button.classList.remove('is-copied');
              }, 2000);
            }
          } catch {
            // Fallback
            prompt('URLをコピーしてください:', window.location.href);
          }
          break;
      }
    });
  });
}

/**
 * Initialize feedback buttons
 */
function initFeedbackButtons(): void {
  const feedbackButtons = document.querySelectorAll<HTMLButtonElement>('[data-feedback]');
  const feedbackContainer = document.querySelector('.article-feedback');

  if (!feedbackContainer) return;

  feedbackButtons.forEach((button) => {
    button.addEventListener('click', async () => {
      const feedbackType = button.dataset.feedback;
      const postId = feedbackContainer.getAttribute('data-post-id');

      if (!postId || !feedbackType) return;

      // Disable all buttons
      feedbackButtons.forEach((btn) => btn.setAttribute('disabled', 'true'));

      try {
        const formData = new FormData();
        formData.append('action', 'plainmark_article_feedback');
        formData.append('post_id', postId);
        formData.append('feedback', feedbackType);
        formData.append('nonce', (window as any).plainmarkData?.nonce || '');

        const response = await fetch((window as any).plainmarkData?.ajaxUrl || '/wp-admin/admin-ajax.php', {
          method: 'POST',
          body: formData,
        });

        if (response.ok) {
          feedbackContainer.innerHTML = `
            <div class="article-feedback__thanks">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
              </svg>
              フィードバックありがとうございます
            </div>
          `;
        }
      } catch {
        // Re-enable buttons on error
        feedbackButtons.forEach((btn) => btn.removeAttribute('disabled'));
      }
    });
  });
}

/**
 * Initialize all article enhancements
 */
function initArticleEnhancements(): void {
  initHeadingAnchors();
  initShareButtons();
  initFeedbackButtons();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initArticleEnhancements);
} else {
  initArticleEnhancements();
}
