<?php
/**
 * OGP (Open Graph Protocol) and Twitter Card meta tags.
 *
 * Features:
 *   - Automatic og:title / og:description / og:image for all pages
 *   - Twitter Card (summary_large_image)
 *   - Dynamic OGP image endpoint: ?plainmark_ogp_image=1&post_id=123
 *     Generates an SVG-based image with title, category, and difficulty badge
 *     so that posts without a featured image still look good on social media.
 *
 * @package plainmark
 * @since   0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -------------------------------------------------------------------------
// Meta tag output
// -------------------------------------------------------------------------

/**
 * Output OGP and Twitter Card meta tags in <head>.
 */
function plainmark_output_ogp_meta() {
    // Skip if a popular SEO plugin is active.
    if ( plainmark_has_seo_plugin() ) {
        return;
    }

    $meta = plainmark_get_ogp_data();
    if ( empty( $meta ) ) {
        return;
    }

    echo "\n<!-- plainmark OGP -->\n";

    // Open Graph.
    plainmark_meta_tag( 'og:title', $meta['title'] );
    plainmark_meta_tag( 'og:description', $meta['description'] );
    plainmark_meta_tag( 'og:url', $meta['url'] );
    plainmark_meta_tag( 'og:type', $meta['type'] );
    plainmark_meta_tag( 'og:site_name', get_bloginfo( 'name' ) );
    plainmark_meta_tag( 'og:locale', get_locale() );

    if ( ! empty( $meta['image'] ) ) {
        plainmark_meta_tag( 'og:image', $meta['image'] );
        if ( ! empty( $meta['image_width'] ) ) {
            plainmark_meta_tag( 'og:image:width', $meta['image_width'] );
            plainmark_meta_tag( 'og:image:height', $meta['image_height'] );
        }
    }

    if ( ! empty( $meta['published'] ) ) {
        plainmark_meta_tag( 'article:published_time', $meta['published'] );
        plainmark_meta_tag( 'article:modified_time', $meta['modified'] );
    }

    // Twitter Card.
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $meta['title'] ) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr( $meta['description'] ) . '">' . "\n";

    if ( ! empty( $meta['image'] ) ) {
        echo '<meta name="twitter:image" content="' . esc_url( $meta['image'] ) . '">' . "\n";
    }

    $twitter_url = get_theme_mod( 'plainmark_twitter_url', '' );
    if ( $twitter_url ) {
        $handle = plainmark_extract_twitter_handle( $twitter_url );
        if ( $handle ) {
            echo '<meta name="twitter:site" content="' . esc_attr( $handle ) . '">' . "\n";
        }
    }

    echo "<!-- /plainmark OGP -->\n\n";
}
add_action( 'wp_head', 'plainmark_output_ogp_meta', 2 );

// -------------------------------------------------------------------------
// Data gathering
// -------------------------------------------------------------------------

/**
 * Get OGP data for the current page.
 *
 * @return array{title:string,description:string,url:string,type:string,image:string,image_width:int,image_height:int,published:string,modified:string}
 */
function plainmark_get_ogp_data() {
    $data = array(
        'title'        => '',
        'description'  => '',
        'url'          => '',
        'type'         => 'website',
        'image'        => '',
        'image_width'  => 0,
        'image_height' => 0,
        'published'    => '',
        'modified'     => '',
    );

    if ( is_singular() ) {
        $post_id = get_the_ID();

        $data['title'] = get_the_title( $post_id );
        $data['url']   = get_permalink( $post_id );
        $data['type']  = 'article';

        // Description: excerpt or trimmed content.
        $excerpt = get_the_excerpt( $post_id );
        $data['description'] = $excerpt
            ? wp_strip_all_tags( $excerpt )
            : wp_trim_words( wp_strip_all_tags( get_the_content( null, false, $post_id ) ), 80, '…' );

        // Dates.
        $data['published'] = get_the_date( 'c', $post_id );
        $data['modified']  = get_the_modified_date( 'c', $post_id );

        // Image: featured image or dynamic OGP image.
        if ( has_post_thumbnail( $post_id ) ) {
            $img = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'plainmark-featured' );
            if ( $img ) {
                $data['image']        = $img[0];
                $data['image_width']  = $img[1];
                $data['image_height'] = $img[2];
            }
        } else {
            // Fall back to dynamic OGP image.
            $data['image']        = plainmark_get_ogp_image_url( $post_id );
            $data['image_width']  = 1200;
            $data['image_height'] = 630;
        }
    } elseif ( is_front_page() || is_home() ) {
        $data['title']       = get_bloginfo( 'name' );
        $data['description'] = get_bloginfo( 'description' );
        $data['url']         = home_url( '/' );
        $data['type']        = 'website';

        $custom_logo_id = get_theme_mod( 'custom_logo' );
        if ( $custom_logo_id ) {
            $logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
            if ( $logo_url ) {
                $data['image'] = $logo_url;
            }
        }
    } elseif ( is_category() || is_tag() || is_tax() ) {
        $term = get_queried_object();
        if ( $term ) {
            $data['title']       = $term->name . ' — ' . get_bloginfo( 'name' );
            $data['description'] = $term->description ?: get_bloginfo( 'description' );
            $data['url']         = get_term_link( $term );
            if ( is_wp_error( $data['url'] ) ) {
                $data['url'] = home_url( '/' );
            }
        }
    } elseif ( is_author() ) {
        $author = get_queried_object();
        if ( $author ) {
            $data['title']       = $author->display_name . ' — ' . get_bloginfo( 'name' );
            $data['description'] = $author->description ?: get_bloginfo( 'description' );
            $data['url']         = get_author_posts_url( $author->ID );
        }
    } elseif ( is_search() ) {
        $data['title']       = sprintf( '%s — %s', get_search_query(), get_bloginfo( 'name' ) );
        $data['description'] = get_bloginfo( 'description' );
        $data['url']         = get_search_link();
    } else {
        $data['title']       = wp_get_document_title();
        $data['description'] = get_bloginfo( 'description' );
        $data['url']         = home_url( $_SERVER['REQUEST_URI'] ?? '/' );
    }

    // Truncate description.
    if ( mb_strlen( $data['description'] ) > 160 ) {
        $data['description'] = mb_substr( $data['description'], 0, 157 ) . '…';
    }

    return $data;
}

// -------------------------------------------------------------------------
// Dynamic OGP image
// -------------------------------------------------------------------------

/**
 * Get the URL for the dynamic OGP image endpoint.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function plainmark_get_ogp_image_url( $post_id ) {
    return add_query_arg(
        array(
            'plainmark_ogp_image' => '1',
            'post_id'             => $post_id,
        ),
        home_url( '/' )
    );
}

/**
 * Register OGP image query var.
 *
 * @param array $vars Query vars.
 * @return array
 */
function plainmark_ogp_image_query_var( $vars ) {
    $vars[] = 'plainmark_ogp_image';
    return $vars;
}
add_filter( 'query_vars', 'plainmark_ogp_image_query_var' );

/**
 * Handle OGP image request.
 */
function plainmark_handle_ogp_image_request() {
    if ( ! get_query_var( 'plainmark_ogp_image' ) ) {
        return;
    }

    $post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ( ! $post_id || 'publish' !== get_post_status( $post_id ) ) {
        status_header( 404 );
        exit;
    }

    // Cache headers (1 week).
    header( 'Content-Type: image/svg+xml; charset=utf-8' );
    header( 'Cache-Control: public, max-age=604800, immutable' );

    echo plainmark_generate_ogp_svg( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit;
}
add_action( 'template_redirect', 'plainmark_handle_ogp_image_request' );

/**
 * Generate an OGP image as SVG.
 *
 * Layout:
 *   1200×630 card with site name, category badge, title, and optional
 *   difficulty / article-type badges. Grid pattern background.
 *
 * @param int $post_id Post ID.
 * @return string SVG markup.
 */
function plainmark_generate_ogp_svg( $post_id ) {
    $title     = get_the_title( $post_id );
    $site_name = get_bloginfo( 'name' );
    $post_type = get_post_type( $post_id );

    // Category / taxonomy label.
    $category_label = '';
    if ( 'portfolio' === $post_type ) {
        $category_label = 'WORKS';
    } else {
        $categories = get_the_category( $post_id );
        if ( $categories ) {
            $category_label = $categories[0]->name;
        }
    }

    // Article meta badges.
    $article_meta = function_exists( 'plainmark_get_article_meta' )
        ? plainmark_get_article_meta( $post_id )
        : array();

    $type_label       = $article_meta['article_type_label'] ?? '';
    $difficulty_label = $article_meta['difficulty_label'] ?? '';

    // Escape for SVG/XML context.
    $title_escaped     = esc_html( $title );
    $site_escaped      = esc_html( $site_name );
    $cat_escaped       = esc_html( $category_label );
    $type_escaped      = esc_html( $type_label );
    $diff_escaped      = esc_html( $difficulty_label );

    // Split long titles into multiple lines (roughly 20 chars per line for Japanese).
    $max_chars_per_line = 22;
    $title_lines        = plainmark_split_title( $title_escaped, $max_chars_per_line, 3 );

    // Calculate title font size and position.
    $line_count = count( $title_lines );
    $font_size  = $line_count <= 2 ? 52 : 44;
    $line_gap   = $font_size * 1.35;
    $title_y    = 280 - ( ( $line_count - 1 ) * $line_gap / 2 );

    // Build title tspans.
    $title_tspans = '';
    foreach ( $title_lines as $i => $line ) {
        $y = $title_y + ( $i * $line_gap );
        $title_tspans .= sprintf(
            '<tspan x="80" y="%s">%s</tspan>',
            esc_attr( $y ),
            $line
        );
    }

    // Badge row.
    $badges_svg = '';
    $badge_x    = 80;
    if ( $cat_escaped ) {
        $cat_width   = mb_strlen( $category_label ) * 14 + 32;
        $badges_svg .= sprintf(
            '<rect x="%d" y="420" width="%d" height="36" rx="18" fill="#111"/>
             <text x="%d" y="444" font-family="sans-serif" font-size="14" font-weight="700" fill="#fff" letter-spacing="0.08em">%s</text>',
            $badge_x,
            $cat_width,
            $badge_x + 16,
            $cat_escaped
        );
        $badge_x += $cat_width + 12;
    }
    if ( $type_escaped ) {
        $type_width  = mb_strlen( $type_label ) * 14 + 32;
        $badges_svg .= sprintf(
            '<rect x="%d" y="420" width="%d" height="36" rx="18" fill="none" stroke="#999" stroke-width="1"/>
             <text x="%d" y="444" font-family="sans-serif" font-size="14" font-weight="600" fill="#666">%s</text>',
            $badge_x,
            $type_width,
            $badge_x + 16,
            $type_escaped
        );
        $badge_x += $type_width + 12;
    }
    if ( $diff_escaped ) {
        $diff_width  = mb_strlen( $difficulty_label ) * 10 + 32;
        $badges_svg .= sprintf(
            '<rect x="%d" y="420" width="%d" height="36" rx="18" fill="none" stroke="#ccc" stroke-width="1"/>
             <text x="%d" y="444" font-family="sans-serif" font-size="14" font-weight="600" fill="#999">%s</text>',
            $badge_x,
            $diff_width,
            $badge_x + 16,
            $diff_escaped
        );
    }

    // Generate SVG.
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <defs>
    <pattern id="grid" width="48" height="48" patternUnits="userSpaceOnUse">
      <path d="M 48 0 L 0 0 0 48" fill="none" stroke="#e8e8e8" stroke-width="0.5"/>
    </pattern>
  </defs>

  <!-- Background -->
  <rect width="1200" height="630" fill="#ffffff"/>
  <rect width="1200" height="630" fill="url(#grid)" opacity="0.6"/>

  <!-- Decorative corner -->
  <circle cx="1100" cy="80" r="120" fill="#f4f4f4" opacity="0.7"/>

  <!-- Site name -->
  <rect x="80" y="60" width="44" height="44" rx="12" fill="#111"/>
  <text x="82" y="93" font-family="monospace" font-size="16" font-weight="700" fill="#fff" text-anchor="start" dominant-baseline="auto">
    <tspan dx="10">M</tspan>
  </text>
  <text x="138" y="90" font-family="sans-serif" font-size="16" font-weight="700" fill="#111" letter-spacing="-0.02em">{$site_escaped}</text>

  <!-- Title -->
  <text font-family="sans-serif" font-size="{$font_size}" font-weight="700" fill="#111" letter-spacing="-0.04em">
    {$title_tspans}
  </text>

  <!-- Badges -->
  {$badges_svg}

  <!-- Bottom bar -->
  <rect x="0" y="560" width="1200" height="70" fill="#111"/>
  <text x="80" y="602" font-family="monospace" font-size="13" font-weight="700" fill="#999" letter-spacing="0.12em">TECH BLOG &amp; PORTFOLIO</text>
  <text x="1120" y="602" font-family="monospace" font-size="13" font-weight="700" fill="#666" text-anchor="end" letter-spacing="0.08em">{$site_escaped}</text>
</svg>
SVG;

    return $svg;
}

/**
 * Split a title string into lines.
 *
 * @param string $title          Title text.
 * @param int    $max_per_line   Max characters per line.
 * @param int    $max_lines      Max number of lines.
 * @return string[]
 */
function plainmark_split_title( $title, $max_per_line, $max_lines ) {
    $length = mb_strlen( $title );

    if ( $length <= $max_per_line ) {
        return array( $title );
    }

    $lines      = array();
    $remaining  = $title;
    $line_count = 0;

    while ( mb_strlen( $remaining ) > 0 && $line_count < $max_lines ) {
        $line_count++;

        if ( $line_count === $max_lines && mb_strlen( $remaining ) > $max_per_line ) {
            // Last line: truncate with ellipsis.
            $lines[] = mb_substr( $remaining, 0, $max_per_line - 1 ) . '…';
            break;
        }

        if ( mb_strlen( $remaining ) <= $max_per_line ) {
            $lines[] = $remaining;
            break;
        }

        // Try to break at a natural point (punctuation, space, particle).
        $chunk        = mb_substr( $remaining, 0, $max_per_line );
        $break_chars  = array( '。', '、', '）', '」', ' ', '　', 'の', 'を', 'に', 'は', 'で', 'と', 'が' );
        $best_break   = $max_per_line;

        for ( $i = $max_per_line - 1; $i >= $max_per_line - 6 && $i > 0; $i-- ) {
            $char = mb_substr( $remaining, $i, 1 );
            if ( in_array( $char, $break_chars, true ) ) {
                $best_break = $i + 1;
                break;
            }
        }

        $lines[]   = mb_substr( $remaining, 0, $best_break );
        $remaining = mb_substr( $remaining, $best_break );
    }

    return $lines;
}

// -------------------------------------------------------------------------
// Utilities
// -------------------------------------------------------------------------

/**
 * Output a single OGP meta tag.
 *
 * @param string $property Property name.
 * @param string $content  Content value.
 */
function plainmark_meta_tag( $property, $content ) {
    if ( '' === $content ) {
        return;
    }

    printf(
        '<meta property="%s" content="%s">' . "\n",
        esc_attr( $property ),
        esc_attr( $content )
    );
}

/**
 * Extract @handle from a Twitter/X URL.
 *
 * @param string $url Twitter URL.
 * @return string Handle with @ prefix, or empty string.
 */
function plainmark_extract_twitter_handle( $url ) {
    if ( preg_match( '#(?:twitter\.com|x\.com)/([A-Za-z0-9_]+)#', $url, $matches ) ) {
        return '@' . $matches[1];
    }

    return '';
}

/**
 * Check if a popular SEO plugin is handling OGP.
 *
 * @return bool
 */
function plainmark_has_seo_plugin() {
    // Yoast SEO.
    if ( defined( 'WPSEO_VERSION' ) ) {
        return true;
    }

    // All in One SEO.
    if ( defined( 'AIOSEO_VERSION' ) ) {
        return true;
    }

    // Rank Math.
    if ( class_exists( 'RankMath' ) ) {
        return true;
    }

    // SEOPress.
    if ( defined( 'SEOPRESS_VERSION' ) ) {
        return true;
    }

    return false;
}
