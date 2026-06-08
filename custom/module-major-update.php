<?php
if (!defined('ABSPATH')) {
    exit;
}

function add_major_update_meta_box()
{
    add_meta_box(
        'major_update_box',
        '🔥 重大更新设置',
        'render_major_update_box',
        'post',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'add_major_update_meta_box');

function render_major_update_box($post)
{
    if ($post->post_status != 'publish') {
        echo '<p style="color:#666;font-size:12px;">文章发布后才可见此选项。</p>';
        return;
    }

    wp_nonce_field('major_update_nonce_action', 'major_update_nonce');
?>
    <div style="background-color: #fff8e5; padding: 10px; border: 1px solid #e5e5e5; border-radius: 4px;">
        <label style="font-weight: bold; color: #d63638; display: block; margin-bottom: 8px; cursor: pointer;">
            <input type="checkbox" name="is_major_update" id="is_major_update_checkbox" value="1" />
            标记为重大更新
        </label>

        <div id="major_update_note_area" style="display:none; margin-top: 10px; border-top: 1px dashed #ccc; padding-top: 10px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <label style="font-size:12px; font-weight:bold; color:#333;">更新说明 (4-15字):</label>
                <span id="char_counter" style="font-size:11px; color:#999;">0/15</span>
            </div>

            <input type="text" name="major_update_note" id="major_update_note"
                maxlength="15"
                placeholder="例：5.7更新第三章"
                style="width:100%; margin-top:5px; font-size:12px;" />

            <p style="font-size:11px; color:#666; margin-top:5px;">
                标题预览：<span style="color:#0073aa;">原标题</span> <span id="note_preview" style="color:#d63638; font-weight:bold;"></span>
            </p>
        </div>

        <p class="description" style="font-size:12px; line-height: 1.4; color: #444; margin-top: 8px;">
            <strong>提示：</strong> 说明过短或过长将无法提交（4-15字）。
        </p>
    </div>

    <script>
        jQuery(document).ready(function($) {
            var $checkbox = $('#is_major_update_checkbox');
            var $noteArea = $('#major_update_note_area');
            var $noteInput = $('#major_update_note');
            var $notePreview = $('#note_preview');
            var $counter = $('#char_counter');

            $checkbox.change(function() {
                if ($(this).is(':checked')) {
                    $noteArea.slideDown();
                    $noteInput.focus();
                } else {
                    $noteArea.slideUp();
                    $noteInput.val('');
                    $notePreview.text('');
                    $counter.text('0/15').css('color', '#999');
                }
            });

            $noteInput.on('input', function() {
                var val = $(this).val();
                var len = val.length;

                if (val) {
                    $notePreview.text('[' + val + ']');
                } else {
                    $notePreview.text('');
                }

                $counter.text(len + '/15');
                if (len > 0 && len < 4) {
                    $counter.css('color', 'red');
                } else {
                    $counter.css('color', 'green');
                }
            });

            var submitButtons = '#publish, .editor-post-publish-button__button, .editor-post-publish-button';

            $(document).on('click', submitButtons, function(e) {
                if ($checkbox.is(':checked')) {
                    var note = $.trim($noteInput.val());
                    var len = note.length;

                    if (len < 4) {
                        alert('【提交失败】\n\n更新说明字数不符合要求！\n\n当前字数：' + len + '\n要求范围：4 - 15 个字\n\n请修改后再提交。');

                        $noteInput.focus().css('border', '1px solid red');

                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }

                    $noteInput.css('border', '');
                }
            });
        });
    </script>
<?php
}

function reset_publish_date_and_title_on_major_update($data, $postarr)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $data;

    if (! isset($_POST['major_update_nonce']) || ! wp_verify_nonce($_POST['major_update_nonce'], 'major_update_nonce_action')) {
        return $data;
    }

    if (! current_user_can('edit_post', $postarr['ID'])) return $data;

    if (isset($_POST['is_major_update']) && $_POST['is_major_update'] == '1') {
        if (! isset($_POST['major_update_note'])) {
            return $data;
        }

        $note = sanitize_text_field($_POST['major_update_note']);
        $note_length = mb_strlen($note, 'UTF-8');

        if ($note_length < 4 || $note_length > 15) {
            return $data;
        }

        $current_time = current_time('mysql');
        $current_time_gmt = current_time('mysql', 1);
        $data['post_date']     = $current_time;
        $data['post_date_gmt'] = $current_time_gmt;

        $data['post_title'] = $data['post_title'] . ' [' . $note . ']';
    }

    return $data;
}
add_filter('wp_insert_post_data', 'reset_publish_date_and_title_on_major_update', 10, 2);
