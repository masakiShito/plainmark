<?php
/**
 * Custom Navigation Walker
 *
 * @package plainmark
 * @since 0.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Custom Walker for main navigation
 */
class Plainmark_Nav_Walker extends Walker_Nav_Menu {

    /**
     * Start the element output
     */
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
            $t = '';
            $n = '';
        } else {
            $t = "\t";
            $n = "\n";
        }
        $indent = ( $depth ) ? str_repeat( $t, $depth ) : '';

        $classes   = empty( $item->classes ) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;

        // Add active class
        if ( in_array( 'current-menu-item', $classes, true ) ) {
            $classes[] = 'is-active';
        }

        // Add has-children class
        if ( in_array( 'menu-item-has-children', $classes, true ) ) {
            $classes[] = 'has-submenu';
        }

        $args = apply_filters( 'nav_menu_item_args', $args, $item, $depth );

        $class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        $id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth );
        $id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

        $output .= $indent . '<li' . $id . $class_names . '>';

        $atts           = array();
        $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
        $atts['target'] = ! empty( $item->target ) ? $item->target : '';
        if ( '_blank' === $item->target && empty( $item->xfn ) ) {
            $atts['rel'] = 'noopener';
        } else {
            $atts['rel'] = $item->xfn;
        }
        $atts['href']         = ! empty( $item->url ) ? $item->url : '';
        $atts['aria-current'] = $item->current ? 'page' : '';

        $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( is_scalar( $value ) && '' !== $value && false !== $value ) {
                $value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $title = apply_filters( 'the_title', $item->title, $item->ID );
        $title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

        $item_output  = $args->before;
        $item_output .= '<a' . $attributes . '>';
        $item_output .= $args->link_before . $title . $args->link_after;
        $item_output .= '</a>';

        // Add dropdown toggle for items with children
        if ( in_array( 'menu-item-has-children', $classes, true ) && $depth === 0 ) {
            $item_output .= '<button class="submenu-toggle" aria-expanded="false">';
            $item_output .= '<span class="screen-reader-text">' . esc_html__( 'Toggle submenu', 'plainmark' ) . '</span>';
            $item_output .= '<svg class="submenu-icon" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true">';
            $item_output .= '<path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="2"/>';
            $item_output .= '</svg>';
            $item_output .= '</button>';
        }

        $item_output .= $args->after;

        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
    }

    /**
     * Start the sub-menu output
     */
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
            $t = '';
            $n = '';
        } else {
            $t = "\t";
            $n = "\n";
        }
        $indent = str_repeat( $t, $depth );

        $classes = array( 'sub-menu' );
        if ( $depth > 0 ) {
            $classes[] = 'sub-menu--nested';
        }

        $class_names = implode( ' ', apply_filters( 'nav_menu_submenu_css_class', $classes, $args, $depth ) );
        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        $output .= "{$n}{$indent}<ul{$class_names}>{$n}";
    }
}

/**
 * Fallback function if no menu is assigned
 */
function plainmark_nav_fallback() {
    echo '<ul class="primary-menu">';
    echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'plainmark' ) . '</a></li>';
    if ( current_user_can( 'edit_theme_options' ) ) {
        echo '<li><a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">' . esc_html__( 'Add a Menu', 'plainmark' ) . '</a></li>';
    }
    echo '</ul>';
}
