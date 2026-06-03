<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter( 'posts_search', 'surgical_remove_content_search', 9999, 2 );
function surgical_remove_content_search( $search, $wp_query ) {
    if ( ! current_user_can( 'manage_options' ) && ! empty( $search ) ) {
        $search = preg_replace("/\s*OR\s*\([a-zA-Z0-9_]+\.post_content\s+LIKE\s+'[^']+'\)/i", "", $search);
        $search = preg_replace("/\s*OR\s*\([a-zA-Z0-9_]+\.post_excerpt\s+LIKE\s+'[^']+'\)/i", "", $search);
    }
    return $search;
}

add_action( 'pre_get_posts', 'force_strict_posts_limit_for_bots', 999 );
function force_strict_posts_limit_for_bots( $query ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        $ppp = $query->get('posts_per_page');

        if ( $ppp > 50 || $ppp == -1 ) {
            $query->set( 'posts_per_page', 20 );
        }
    }
}

add_action( 'template_redirect', 'restrict_search_to_logged_in_users' );
function restrict_search_to_logged_in_users() {
    if ( is_search() && ! is_user_logged_in() ) {
        wp_die( '为了网站安全与性能，搜索功能仅限已登录用户使用。', '访问受限', array( 'response' => 403 ) );
        exit;
    }
}
