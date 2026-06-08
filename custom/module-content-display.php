<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('the_content', 'make_clickable');

function dn_is_show_post_stats( $post_id = null ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    if ( ! $post_id ) {
        return false;
    }

    return current_user_can( 'edit_post', $post_id );
}
