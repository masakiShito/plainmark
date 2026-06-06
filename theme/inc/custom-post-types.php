<?php
/**
 * Custom Post Types registration
 *
 * @package plainmark
 * @since 0.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Portfolio custom post type
 */
function plainmark_register_portfolio_post_type() {
    $labels = array(
        'name'                  => _x( 'Portfolio', 'Post Type General Name', 'plainmark' ),
        'singular_name'         => _x( 'Portfolio', 'Post Type Singular Name', 'plainmark' ),
        'menu_name'             => __( 'Portfolio', 'plainmark' ),
        'name_admin_bar'        => __( 'Portfolio', 'plainmark' ),
        'archives'              => __( 'ポートフォリオアーカイブ', 'plainmark' ),
        'attributes'            => __( 'ポートフォリオ属性', 'plainmark' ),
        'parent_item_colon'     => __( '親ポートフォリオ:', 'plainmark' ),
        'all_items'             => __( 'すべてのポートフォリオ', 'plainmark' ),
        'add_new_item'          => __( '新規ポートフォリオを追加', 'plainmark' ),
        'add_new'               => __( '新規追加', 'plainmark' ),
        'new_item'              => __( '新規ポートフォリオ', 'plainmark' ),
        'edit_item'             => __( 'ポートフォリオを編集', 'plainmark' ),
        'update_item'           => __( 'ポートフォリオを更新', 'plainmark' ),
        'view_item'             => __( 'ポートフォリオを表示', 'plainmark' ),
        'view_items'            => __( 'ポートフォリオ一覧を表示', 'plainmark' ),
        'search_items'          => __( 'ポートフォリオを検索', 'plainmark' ),
        'not_found'             => __( '見つかりませんでした', 'plainmark' ),
        'not_found_in_trash'    => __( 'ゴミ箱に見つかりませんでした', 'plainmark' ),
        'featured_image'        => __( 'アイキャッチ画像', 'plainmark' ),
        'set_featured_image'    => __( 'アイキャッチ画像を設定', 'plainmark' ),
        'remove_featured_image' => __( 'アイキャッチ画像を削除', 'plainmark' ),
        'use_featured_image'    => __( 'アイキャッチ画像として使用', 'plainmark' ),
        'insert_into_item'      => __( 'ポートフォリオに挿入', 'plainmark' ),
        'uploaded_to_this_item' => __( 'このポートフォリオにアップロード', 'plainmark' ),
        'items_list'            => __( 'ポートフォリオ一覧', 'plainmark' ),
        'items_list_navigation' => __( 'ポートフォリオ一覧ナビゲーション', 'plainmark' ),
        'filter_items_list'     => __( 'ポートフォリオをフィルター', 'plainmark' ),
    );

    $args = array(
        'label'               => __( 'Portfolio', 'plainmark' ),
        'description'         => __( 'Portfolio items', 'plainmark' ),
        'labels'              => $labels,
        'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-portfolio',
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'rewrite'             => array( 'slug' => 'portfolio' ),
        'show_in_rest'        => true,
    );

    register_post_type( 'portfolio', $args );
}
add_action( 'init', 'plainmark_register_portfolio_post_type', 0 );

/**
 * Register Portfolio Category taxonomy
 */
function plainmark_register_portfolio_taxonomy() {
    $labels = array(
        'name'              => _x( 'Portfolio Categories', 'taxonomy general name', 'plainmark' ),
        'singular_name'     => _x( 'Portfolio Category', 'taxonomy singular name', 'plainmark' ),
        'search_items'      => __( 'カテゴリーを検索', 'plainmark' ),
        'all_items'         => __( 'すべてのカテゴリー', 'plainmark' ),
        'parent_item'       => __( '親カテゴリー', 'plainmark' ),
        'parent_item_colon' => __( '親カテゴリー:', 'plainmark' ),
        'edit_item'         => __( 'カテゴリーを編集', 'plainmark' ),
        'update_item'       => __( 'カテゴリーを更新', 'plainmark' ),
        'add_new_item'      => __( '新規カテゴリーを追加', 'plainmark' ),
        'new_item_name'     => __( '新規カテゴリー名', 'plainmark' ),
        'menu_name'         => __( 'カテゴリー', 'plainmark' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'portfolio-category' ),
        'show_in_rest'      => true,
    );

    register_taxonomy( 'portfolio_category', array( 'portfolio' ), $args );
}
add_action( 'init', 'plainmark_register_portfolio_taxonomy', 0 );

/**
 * Register Technology taxonomy for tech stack
 */
function plainmark_register_technology_taxonomy() {
	$labels = array(
		'name'                       => _x( '技術スタック', 'taxonomy general name', 'plainmark' ),
		'singular_name'              => _x( '技術スタック', 'taxonomy singular name', 'plainmark' ),
		'search_items'               => __( '技術スタックを検索', 'plainmark' ),
		'popular_items'              => __( 'よく使う技術スタック', 'plainmark' ),
		'all_items'                  => __( 'すべての技術スタック', 'plainmark' ),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __( '技術スタックを編集', 'plainmark' ),
		'update_item'                => __( '技術スタックを更新', 'plainmark' ),
		'add_new_item'               => __( '新規技術スタックを追加', 'plainmark' ),
		'new_item_name'              => __( '新規技術スタック名', 'plainmark' ),
		'separate_items_with_commas' => __( 'カンマで区切って入力', 'plainmark' ),
		'add_or_remove_items'        => __( '技術スタックの追加または削除', 'plainmark' ),
		'choose_from_most_used'      => __( 'よく使う技術スタックから選択', 'plainmark' ),
		'not_found'                  => __( '技術スタックが見つかりません', 'plainmark' ),
		'menu_name'                  => __( '技術スタック', 'plainmark' ),
	);

	$args = array(
		'hierarchical'      => false,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'technology' ),
		'show_in_rest'      => true,
	);

	register_taxonomy( 'technology', array( 'post', 'portfolio' ), $args );
}
add_action( 'init', 'plainmark_register_technology_taxonomy', 0 );

/**
 * Flush rewrite rules on theme activation
 */
function plainmark_rewrite_flush() {
	plainmark_register_portfolio_post_type();
	plainmark_register_portfolio_taxonomy();
	plainmark_register_technology_taxonomy();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'plainmark_rewrite_flush' );
