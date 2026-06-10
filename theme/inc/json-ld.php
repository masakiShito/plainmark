<?php
/**
 * JSON-LD structured data output.
 *
 * Automatically generates schema based on article type:
 *   - tech_note / learning_log / review  → TechArticle
 *   - tutorial                           → TechArticle + HowTo (if h2 headings exist)
 *   - error_solution                     → TechArticle + FAQPage
 *   - portfolio (CPT)                    → CreativeWork
 *   - Default post                       → Article
 *
 * @package plainmark
 * @since   0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Output JSON-LD in <head>.
 */
function plainmark_output_jsonld() {
    if ( is_singular( 'post' ) ) {
        plainmark_jsonld_single_post();
    } elseif ( is_singular( 'portfolio' ) ) {
        plainmark_jsonld_single_portfolio();
    } elseif ( is_front_page() ) {
        plainmark_jsonld_website();
    }
}
add_action( 'wp_head', 'plainmark_output_jsonld', 5 );

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------

/**
 * Print a JSON-LD script tag.
 *
 * @param array $data Schema data.
 */
function plainmark_print_jsonld( array $data ) {
    echo '<script type="application/ld+json">' . "\n";
    // JSON_UNESCAPED_UNICODE keeps Japanese readable in source.
    echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
    echo "\n</script>\n";
}

/**
 * Get the site publisher (Organization) object.
 *
 * @return array
 */
function plainmark_jsonld_publisher() {
    $publisher = array(
        '@type' => 'Organization',
        'name'  => get_bloginfo( 'name' ),
        'url'   => home_url( '/' ),
    );

    $custom_logo_id = get_theme_mod( 'custom_logo' );
    if ( $custom_logo_id ) {
        $logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
        if ( $logo_url ) {
            $publisher['logo'] = array(
                '@type' => 'ImageObject',
                'url'   => $logo_url,
            );
        }
    }

    return $publisher;
}

/**
 * Get thumbnail image object for the current post.
 *
 * @param int|null $post_id Post ID.
 * @return array|null
 */
function plainmark_jsonld_image( $post_id = null ) {
    $post_id = $post_id ? absint( $post_id ) : get_the_ID();

    if ( ! has_post_thumbnail( $post_id ) ) {
        return null;
    }

    $image_id  = get_post_thumbnail_id( $post_id );
    $image_src = wp_get_attachment_image_src( $image_id, 'plainmark-featured' );

    if ( ! $image_src ) {
        return null;
    }

    return array(
        '@type'  => 'ImageObject',
        'url'    => $image_src[0],
        'width'  => $image_src[1],
        'height' => $image_src[2],
    );
}

/**
 * Get author Person object.
 *
 * @param int|null $post_id Post ID.
 * @return array
 */
function plainmark_jsonld_author( $post_id = null ) {
    $post_id = $post_id ? absint( $post_id ) : get_the_ID();
    $post    = get_post( $post_id );

    return array(
        '@type' => 'Person',
        'name'  => get_the_author_meta( 'display_name', $post->post_author ),
        'url'   => get_author_posts_url( $post->post_author ),
    );
}

/**
 * Extract h2 headings from content.
 *
 * @param string $content Post content.
 * @return string[] Array of heading texts.
 */
function plainmark_jsonld_extract_h2( $content ) {
    if ( ! preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/is', $content, $matches ) ) {
        return array();
    }

    return array_map( 'wp_strip_all_tags', $matches[1] );
}

// -------------------------------------------------------------------------
// Schema builders
// -------------------------------------------------------------------------

/**
 * JSON-LD for single post.
 */
function plainmark_jsonld_single_post() {
    $post_id = get_the_ID();
    if ( ! $post_id ) {
        return;
    }

    $article_meta = function_exists( 'plainmark_get_article_meta' )
        ? plainmark_get_article_meta( $post_id )
        : array();

    $article_type = $article_meta['article_type'] ?? '';
    $content      = get_the_content( null, false, $post_id );
    $content      = apply_filters( 'the_content', $content );

    // Determine @type.
    $tech_types = array( 'tech_note', 'tutorial', 'error_solution', 'learning_log', 'review' );
    $schema_type = in_array( $article_type, $tech_types, true ) ? 'TechArticle' : 'Article';

    // Base article schema.
    $article = array(
        '@context'         => 'https://schema.org',
        '@type'            => $schema_type,
        'headline'         => get_the_title( $post_id ),
        'url'              => get_permalink( $post_id ),
        'datePublished'    => get_the_date( 'c', $post_id ),
        'dateModified'     => get_the_modified_date( 'c', $post_id ),
        'author'           => plainmark_jsonld_author( $post_id ),
        'publisher'        => plainmark_jsonld_publisher(),
        'mainEntityOfPage' => array(
            '@type' => 'WebPage',
            '@id'   => get_permalink( $post_id ),
        ),
    );

    // Description.
    $excerpt = get_the_excerpt( $post_id );
    if ( $excerpt ) {
        $article['description'] = wp_strip_all_tags( $excerpt );
    }

    // Image.
    $image = plainmark_jsonld_image( $post_id );
    if ( $image ) {
        $article['image'] = $image;
    }

    // Word count.
    $word_count = mb_strlen( wp_strip_all_tags( $content ) );
    if ( $word_count > 0 ) {
        $article['wordCount'] = $word_count;
    }

    // Technologies (keywords).
    $technologies = get_the_terms( $post_id, 'technology' );
    if ( $technologies && ! is_wp_error( $technologies ) ) {
        $article['keywords'] = implode( ', ', wp_list_pluck( $technologies, 'name' ) );
    }

    // TechArticle specific fields.
    if ( 'TechArticle' === $schema_type ) {
        $difficulty = $article_meta['difficulty'] ?? '';
        if ( $difficulty ) {
            $proficiency_map = array(
                'beginner'     => 'Beginner',
                'intermediate' => 'Intermediate',
                'advanced'     => 'Expert',
            );
            $article['proficiencyLevel'] = $proficiency_map[ $difficulty ] ?? $difficulty;
        }

        $prerequisites = $article_meta['prerequisites'] ?? '';
        if ( $prerequisites ) {
            $article['dependencies'] = $prerequisites;
        }
    }

    // Print main article schema.
    plainmark_print_jsonld( $article );

    // Additional schemas based on article type.
    if ( 'tutorial' === $article_type ) {
        plainmark_jsonld_howto( $post_id, $content );
    }

    if ( 'error_solution' === $article_type ) {
        plainmark_jsonld_faqpage( $post_id, $content );
    }
}

/**
 * HowTo schema for tutorial articles.
 *
 * Uses h2 headings as steps.
 *
 * @param int    $post_id Post ID.
 * @param string $content Rendered content.
 */
function plainmark_jsonld_howto( $post_id, $content ) {
    $headings = plainmark_jsonld_extract_h2( $content );

    if ( count( $headings ) < 2 ) {
        return;
    }

    $steps = array();
    foreach ( $headings as $index => $heading ) {
        $steps[] = array(
            '@type'    => 'HowToStep',
            'position' => $index + 1,
            'name'     => $heading,
            'url'      => get_permalink( $post_id ) . '#' . sanitize_title( $heading ),
        );
    }

    $howto = array(
        '@context' => 'https://schema.org',
        '@type'    => 'HowTo',
        'name'     => get_the_title( $post_id ),
        'step'     => $steps,
    );

    $excerpt = get_the_excerpt( $post_id );
    if ( $excerpt ) {
        $howto['description'] = wp_strip_all_tags( $excerpt );
    }

    $image = plainmark_jsonld_image( $post_id );
    if ( $image ) {
        $howto['image'] = $image;
    }

    plainmark_print_jsonld( $howto );
}

/**
 * FAQPage schema for error_solution articles.
 *
 * Treats each h2 as a question and the following content until the next h2
 * as the answer (first 200 chars).
 *
 * @param int    $post_id Post ID.
 * @param string $content Rendered content.
 */
function plainmark_jsonld_faqpage( $post_id, $content ) {
    // Split content by h2.
    $parts = preg_split( '/<h2[^>]*>/i', $content );

    if ( count( $parts ) < 2 ) {
        return;
    }

    $qa_items = array();

    // Skip the first part (before the first h2).
    for ( $i = 1, $count = count( $parts ); $i < $count && $i <= 10; $i++ ) {
        // Extract question (h2 text).
        $part = $parts[ $i ];
        if ( ! preg_match( '/^(.*?)<\/h2>/is', $part, $h2_match ) ) {
            continue;
        }

        $question = wp_strip_all_tags( $h2_match[1] );
        $answer   = wp_strip_all_tags( substr( $part, strlen( $h2_match[0] ) ) );
        $answer   = mb_substr( trim( $answer ), 0, 300 );

        if ( empty( $question ) || empty( $answer ) ) {
            continue;
        }

        $qa_items[] = array(
            '@type'          => 'Question',
            'name'           => $question,
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => $answer,
            ),
        );
    }

    if ( empty( $qa_items ) ) {
        return;
    }

    plainmark_print_jsonld(
        array(
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $qa_items,
        )
    );
}

/**
 * JSON-LD for single portfolio.
 */
function plainmark_jsonld_single_portfolio() {
    $post_id = get_the_ID();
    if ( ! $post_id ) {
        return;
    }

    $summary    = get_post_meta( $post_id, 'work_summary', true );
    $role       = get_post_meta( $post_id, 'work_role', true );
    $period     = get_post_meta( $post_id, 'work_period', true );
    $github_url = get_post_meta( $post_id, 'work_github_url', true );
    $demo_url   = get_post_meta( $post_id, 'work_demo_url', true );
    $tech_terms = get_the_terms( $post_id, 'technology' );

    $schema = array(
        '@context'      => 'https://schema.org',
        '@type'         => 'CreativeWork',
        'name'          => get_the_title( $post_id ),
        'url'           => get_permalink( $post_id ),
        'datePublished' => get_the_date( 'c', $post_id ),
        'dateModified'  => get_the_modified_date( 'c', $post_id ),
        'author'        => plainmark_jsonld_author( $post_id ),
    );

    if ( $summary ) {
        $schema['description'] = $summary;
    }

    $image = plainmark_jsonld_image( $post_id );
    if ( $image ) {
        $schema['image'] = $image;
    }

    if ( $tech_terms && ! is_wp_error( $tech_terms ) ) {
        $schema['keywords'] = implode( ', ', wp_list_pluck( $tech_terms, 'name' ) );
    }

    if ( $role ) {
        $schema['contributor'] = array(
            '@type'   => 'Person',
            'name'    => get_the_author(),
            'jobTitle' => $role,
        );
    }

    if ( $period ) {
        $schema['temporalCoverage'] = $period;
    }

    // Code repository.
    if ( $github_url ) {
        $schema['codeRepository'] = $github_url;
    }

    if ( $demo_url ) {
        $schema['isBasedOnUrl'] = $demo_url;
    }

    plainmark_print_jsonld( $schema );
}

/**
 * WebSite schema for front page (enables sitelinks search box).
 */
function plainmark_jsonld_website() {
    $schema = array(
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        'name'            => get_bloginfo( 'name' ),
        'url'             => home_url( '/' ),
        'potentialAction' => array(
            '@type'       => 'SearchAction',
            'target'      => array(
                '@type'       => 'EntryPoint',
                'urlTemplate' => home_url( '/?s={search_term_string}' ),
            ),
            'query-input' => 'required name=search_term_string',
        ),
    );

    $description = get_bloginfo( 'description' );
    if ( $description ) {
        $schema['description'] = $description;
    }

    plainmark_print_jsonld( $schema );
}
