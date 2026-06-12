<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function() {
    remove_action('comment_post', 'comment_notify', 10);
    remove_action('comment_post', 'comment_approved', 10);
}, 99);

add_filter('comment_row_actions', 'dn_remove_unwanted_comment_actions', 999);
function dn_remove_unwanted_comment_actions($actions) {
    unset($actions['unapprove'], $actions['spam']);

    return $actions;
}

add_filter('bulk_actions-edit-comments', 'dn_remove_unwanted_comment_bulk_actions', 999);
function dn_remove_unwanted_comment_bulk_actions($actions) {
    unset($actions['unapprove'], $actions['spam']);

    return $actions;
}

add_action('admin_footer-index.php', 'dn_confirm_comment_trash');
add_action('admin_footer-edit-comments.php', 'dn_confirm_comment_trash');
function dn_confirm_comment_trash() {
    ?>
    <script type="text/javascript">
    document.addEventListener('click', function(event) {
        if (!event.target.closest) return;

        var trashLink = event.target.closest('a[href*="action=trashcomment"]');
        if (!trashLink) return;

        if (!window.confirm('确定要将这条评论移至回收站吗？')) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);
    </script>
    <?php
}

add_action('wp_footer', 'dn_force_plain_text_paste_in_comments', 99);
function dn_force_plain_text_paste_in_comments() {
    if ( ! is_singular() ) return;
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        document.body.addEventListener('paste', function(e) {
            var target = e.target;
            var isInWpDiscuz = target.closest('#wpdcom') && (target.closest('.ql-editor') || target.matches('textarea[name="wc_comment"]'));

            if (isInWpDiscuz) {
                var clipboardData = e.clipboardData || window.clipboardData;
                if (!clipboardData) return;

                var plainText = clipboardData.getData('text/plain');
                var hasHtml = clipboardData.getData('text/html');

                if (plainText && hasHtml) {
                    e.preventDefault();

                    if (document.queryCommandSupported('insertText')) {
                        document.execCommand('insertText', false, plainText);
                    } else {
                        document.execCommand('paste', false, plainText);
                    }
                }
            }
        });
    });
    </script>
    <?php
}

add_action('admin_head', 'dn_hide_email_ip_for_non_admins');
function dn_hide_email_ip_for_non_admins() {
    if ( current_user_can('manage_options') ) {
        return;
    }
    ?>
    <style>
        table.comments td.column-author a[href^="mailto:"],
        table.comments td.column-author a[href*="edit-comments.php?s="],
        table.comments td.column-author br {
            display: none !important;
        }

        .comment-author a[href^="mailto:"],
        .comment-author a[href*="edit-comments.php?s="],
        .comment-author br {
             display: none !important;
        }

        #the-comment-list .hidden .author-email,
        #the-comment-list .inline-edit-row .author-email {
            display: none !important;
        }
    </style>
    <?php
}
