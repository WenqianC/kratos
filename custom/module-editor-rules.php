<?php
if (!defined('ABSPATH')) {
    exit;
}

function remove_author_metabox()
{
    remove_meta_box('authordiv', 'post', 'normal');
}
add_action('admin_menu', 'remove_author_metabox');

add_action( 'edit_form_after_title', 'add_custom_notice_after_title' );
function add_custom_notice_after_title( $post ) {
    if ( 'post' === $post->post_type ) {
        echo '<div style="color: #646970; font-size: 13px; margin-top: 5px; padding-left: 10px;"><strong>标题格式：</strong>标题里请带上CP名。如果是无cp，也请带上主要角色名。发帖规约（必读）<a href="https://www.dnforlife.com/200261" target="_blank" style="color: #0073aa; text-decoration: underline;">请点我查看</a>。</div>';
    }
}

add_action( 'admin_footer-post-new.php', 'enforce_tag_rules_js' );
add_action( 'admin_footer-post.php', 'enforce_tag_rules_js' );
function enforce_tag_rules_js() {
    global $post_type;
    if ( 'post' !== $post_type ) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var tagHint = $('#new-tag-post_tag-desc');
        if (tagHint.length) {
            tagHint.text('请每次输入一个标签，并点击“添加”按钮（或按回车）。请注意，含成人内容必须打“R18”标签；cp向文章请输入cp名作为标签。');
        }

        $('#post').on('submit', function(e) {
            var savedTags = $('#tax-input-post_tag').val();
            var typingTag = $('#new-tag-post_tag').val();

            if ( (!savedTags || savedTags.trim() === '') && (!typingTag || typingTag.trim() === '') ) {
                e.preventDefault();

                var alertMsg = "⚠️发布失败：请添加文章标签！\n" +
                               "为读者便于阅读，请遵守以下Tag规范：\n" +
                               "【固定CP】：只打 CP 标签，无需打该 CP 的单人标签。（配角需打单人标签）\n" +
                               "【无差/互攻】：无差请同时打正逆双向标签，并加打“无差”；互攻请加打“互攻”。\n" +
                               "【分级预警】：含成人内容必须打“R18”标签！\n" +
                               "【避免冗余】：请尽量选择下拉列表已有的标签，非必要请勿新增。";

                alert(alertMsg);

                $('#publish').removeClass('button-primary-disabled');
                $('.spinner').removeClass('is-active');
                $('#new-tag-post_tag').focus();

                return false;
            }
        });
    });
    </script>
    <?php
}
