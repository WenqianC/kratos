<?php
/**
 * Link registered wpDiscuz commenters to their post archives.
 */

if (!defined('ABSPATH')) {
    exit;
}

function dn_comment_author_archive_link($after_author, $comment, $user)
{
    if (!($comment instanceof WP_Comment)
        || !($user instanceof WP_User)
        || (int) $comment->user_id !== (int) $user->ID
        || 'post' !== get_post_type($comment->comment_post_ID)) {
        return $after_author;
    }

    $url = get_author_posts_url($user->ID);
    if (!$url) {
        return $after_author;
    }

    $label = sprintf(__('查看%s的所有文章', 'kratos'), $comment->comment_author);

    return $after_author . '<a class="dn-comment-author-archive-link" href="' . esc_url($url)
        . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr($label) . '"></a>';
}
add_filter('wpdiscuz_after_comment_author', 'dn_comment_author_archive_link', 10, 3);

function dn_enqueue_comment_author_archive_style()
{
    if (!is_singular('post')) {
        return;
    }

    wp_add_inline_style('custom', '
        #wpdcom .wpd-comment-author { position: relative; }
        #wpdcom .wpd-comment-author .dn-comment-author-archive-link {
            position: absolute;
            inset: 0;
            z-index: 1;
            cursor: pointer;
        }
        #wpdcom .wpd-comment-author .dn-comment-author-archive-link:focus-visible {
            outline: 2px solid currentColor;
            outline-offset: 2px;
        }
    ');
}
add_action('wp_enqueue_scripts', 'dn_enqueue_comment_author_archive_style', 20);
