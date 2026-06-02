<?php
/**
 * ====================================================
 * 功能模块：用户专属 Tag 屏蔽词系统 (All-in-One 完整版)
 * ====================================================
 */

// ==========================================
// 模块 1：后台管理界面与设置
// ==========================================
add_action('admin_menu', 'dn_add_blocklist_menu');
function dn_add_blocklist_menu() {
    add_menu_page('Tag屏蔽设置', '屏蔽词设置', 'read', 'dn-tag-blocklist', 'dn_render_blocklist_page', 'dashicons-filter', 80);
}

function dn_render_blocklist_page() {
    $user_id = get_current_user_id();
    $current_tags = get_user_meta($user_id, 'dn_blocked_tags', true);
    if (!is_array($current_tags)) $current_tags = [];
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">专属 Tag 屏蔽词设置</h1>
        <p class="description">您最多可以添加 10 个屏蔽词。一旦文章的标签触发匹配，该文章将在列表中对您隐藏。</p>
        
        <div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); max-width: 600px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;">📖 屏蔽规则与高级用法范例</h3>
            <ul style="list-style: disc; padding-left: 20px; color: #555;">
                <li><strong>基础匹配（推荐）：</strong>输入 <code>A B</code>，将屏蔽含有该词的标签（如 <code>a b</code>、<code>A B C</code>），不区分大小写。</li>
                <li><strong>精确匹配（防止误伤）：</strong>输入 <code>^A B$</code>，只会屏蔽标签正好是 <code>A B</code> 的文章。</li>
                <li><strong>开头匹配：</strong>输入 <code>^A B</code>，会屏蔽以其开头的标签（如 <code>A B C</code>）。</li>
                <li><strong>进阶指南：</strong>获取更多规则请<a href="https://www.dnforlife.com/regex" target="_blank" style="color: #0073aa; text-decoration: underline;">点我查看</a>。</li>
                <li><strong>未成年用户：</strong>请遵守所在国家的法律法规，自觉输入 <code>R18</code>，屏蔽不适合未成年人阅读的标签。</li>
            </ul>
        </div>

        <div>
            <input type="text" id="dn-new-tag-input" placeholder="输入屏蔽词..." style="width: 250px; padding: 5px;" maxlength="50">
            <button id="dn-add-tag-btn" class="button button-primary">添加屏蔽词</button>
            <span id="dn-tag-feedback" style="margin-left:10px; color: #d63638;"></span>
        </div>

        <h3 style="margin-top: 30px;">当前已添加 (<span id="dn-tag-count"><?php echo count($current_tags); ?></span>/10)</h3>
        <ul id="dn-tag-list" style="max-width: 400px;">
            <?php foreach ($current_tags as $tag): ?>
                <li style="background: #f0f0f1; padding: 8px 12px; margin-bottom: 5px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                    <code><?php echo esc_html($tag); ?></code>
                    <a href="#" class="dn-delete-tag" data-tag="<?php echo esc_attr($tag); ?>" style="color: #d63638; text-decoration: none;">❌ 删除</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        function addTag() {
            var newTag = $.trim($('#dn-new-tag-input').val());
            if (newTag === '') return;
            $('#dn-tag-feedback').text('处理中...');
            $.post(ajaxurl, {
                action: 'dn_add_blocked_tag',
                tag: newTag,
                nonce: '<?php echo wp_create_nonce("dn_tag_nonce"); ?>'
            }, function(response) {
                if (response.success) { location.reload(); } else { $('#dn-tag-feedback').text(response.data); }
            });
        }
        $('#dn-add-tag-btn').on('click', addTag);
        $('#dn-new-tag-input').on('keypress', function(e) { if (e.which === 13) addTag(); });

        $('.dn-delete-tag').on('click', function(e) {
            e.preventDefault();
            var tagToDelete = $(this).data('tag');
            if(confirm('确定要删除屏蔽词: ' + tagToDelete + ' 吗？')) {
                $.post(ajaxurl, {
                    action: 'dn_delete_blocked_tag',
                    tag: tagToDelete,
                    nonce: '<?php echo wp_create_nonce("dn_tag_nonce"); ?>'
                }, function(response) { if (response.success) location.reload(); });
            }
        });
    });
    </script>
    <?php
}

// ==========================================
// 模块 2：处理 AJAX 增删请求
// ==========================================
add_action('wp_ajax_dn_add_blocked_tag', function() {
    check_ajax_referer('dn_tag_nonce', 'nonce');
    $user_id = get_current_user_id();
    $new_tag = sanitize_text_field($_POST['tag']);
    $current_tags = get_user_meta($user_id, 'dn_blocked_tags', true) ?: [];
    
    if (count($current_tags) >= 10) wp_send_json_error('额度已满：最多只能添加 10 个屏蔽词。');
    if (in_array($new_tag, $current_tags)) wp_send_json_error('该屏蔽词已存在。');
    
    $current_tags[] = $new_tag;
    update_user_meta($user_id, 'dn_blocked_tags', $current_tags);
    wp_send_json_success();
});

add_action('wp_ajax_dn_delete_blocked_tag', function() {
    check_ajax_referer('dn_tag_nonce', 'nonce');
    $user_id = get_current_user_id();
    $tag_to_delete = $_POST['tag'];
    $current_tags = get_user_meta($user_id, 'dn_blocked_tags', true) ?: [];
    
    $current_tags = array_filter($current_tags, function($v) use ($tag_to_delete) { return $v !== $tag_to_delete; });
    update_user_meta($user_id, 'dn_blocked_tags', array_values($current_tags));
    wp_send_json_success();
});

// ==========================================
// 模块 3：前台拦截逻辑 (仅限登录用户)
// ==========================================
add_action('wp_head', function() {
    if ( ! is_admin() && is_user_logged_in() ) {
        $tags = get_user_meta(get_current_user_id(), 'dn_blocked_tags', true) ?: [];
        echo '<script type="text/javascript">window.dn_blocked_tags = ' . json_encode($tags) . ';</script>';
    }
});

add_action('wp_footer', function() {
    if ( ! is_admin() && is_user_logged_in() ) {
        ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.dn_blocked_tags === 'undefined' || window.dn_blocked_tags.length === 0) return;

            var blockedCount = 0;
            var regexList = window.dn_blocked_tags.map(function(tagStr) {
                return new RegExp(tagStr, 'i');
            });

            var articles = document.querySelectorAll('.article-panel');
            
            articles.forEach(function(article) {
                var tagElements = article.querySelectorAll('.tags a');
                var articleTagsText = [];
                tagElements.forEach(function(el) {
                    articleTagsText.push(el.textContent.trim());
                });

                var shouldHide = false;
                for (var i = 0; i < articleTagsText.length; i++) {
                    for (var j = 0; j < regexList.length; j++) {
                        if (regexList[j].test(articleTagsText[i])) {
                            shouldHide = true;
                            break;
                        }
                    }
                    if (shouldHide) break;
                }

                if (shouldHide) {
                    article.style.display = 'none';
                    article.classList.add('dn-hidden-by-tag'); // 打上标记
                    blockedCount++;
                }
            });

            if (blockedCount > 0) {
                var paginationBox = document.querySelector('.paginations');
                if (paginationBox) {
                    var notice = document.createElement('div');
                    notice.style.cssText = 'text-align: center; font-size: 13px; color: #999; margin-bottom: 15px; padding: 10px; background: rgba(0,0,0,0.02); border-radius: 4px;';
                    
                    // 构建 HTML 内容，包含换行和按钮
                    notice.innerHTML = '🛡️ <b>Tag 屏蔽功能已启用。</b> 本页有 <b>' + blockedCount + '</b> 篇文章因包含您的雷区 Tag 已被自动隐藏，导致每页显示数量不同为正常现象。<br>' + 
                   '如部分历史文章未填写tag，请邮件站长修改。请勿留言打扰作者。<br>' +
                   '<a href="#" id="dn-toggle-blocked-btn" style="color: #0073aa; text-decoration: underline; display: inline-block;">👀 点击临时查看本页被屏蔽的文章</a>';
                    
                    paginationBox.parentNode.insertBefore(notice, paginationBox);

                    // 绑定切换逻辑
                    var toggleBtn = document.getElementById('dn-toggle-blocked-btn');
                    var isShowing = false;

                    toggleBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        isShowing = !isShowing;
                        var hiddenArticles = document.querySelectorAll('.dn-hidden-by-tag');
                        
                        hiddenArticles.forEach(function(art) {
                            if (isShowing) {
                                art.style.display = ''; // 恢复显示
                                art.style.opacity = '0.5'; // 半透明提示
                            } else {
                                art.style.display = 'none'; // 重新隐藏
                                art.style.opacity = '1';
                            }
                        });

                        toggleBtn.innerHTML = isShowing ? '🙈 恢复屏蔽状态' : '👀 点击临时查看本页被屏蔽的文章';
                        toggleBtn.style.color = isShowing ? '#d63638' : '#0073aa';
                    });
                }
            }
        });
        </script>
        <?php
    }
}, 99);