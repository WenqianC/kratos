<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ====================================================
 * Disabled custom snippets
 * ====================================================
 *
 * This file keeps old or occasional-use snippets that are intentionally not
 * loaded by custom/custom.php.
 *
 * To enable any snippet:
 * 1. Ask the site owner first.
 * 2. Move the snippet into the appropriate module, or explicitly require this
 *    file from custom/custom.php after confirming the runtime impact.
 * 3. Run the checks described in AGENTS.md.
 */

/**
 * Optional: increase WordPress memory limit.
 *
 * Original disabled snippet:
 */
// define( 'WP_MEMORY_LIMIT', '256M' );

/**
 * Optional: include password-protected posts in search results.
 *
 * Approximate behavior:
 * - Removes WordPress's default search condition that hides password-protected
 *   posts from non-logged-in users.
 * - This may conflict with the current policy that blocks guest search.
 *
 * Original disabled snippet:
 */
// add_filter( 'posts_search', 'include_password_posts_in_search' );
// function include_password_posts_in_search( $search ) {
//     global $wpdb;
//     if( !is_user_logged_in() ) {
//         $pattern = " AND ({$wpdb->prefix}posts.post_password = '')";
//         $search = str_replace( $pattern, '', $search );
//     }
//     return $search;
// }
