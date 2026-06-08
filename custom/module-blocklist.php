<?php
/**
 * 用户专属屏蔽设置：Tag 屏蔽与作者屏蔽。
 */

function dn_blocklist_protected_author_ids() {
    return array(48008);
}

function dn_blocked_author_limit() {
    return 50;
}

function dn_is_protected_author($author_id) {
    return in_array(absint($author_id), dn_blocklist_protected_author_ids(), true);
}

function dn_get_blocked_tags($user_id = 0) {
    $user_id = $user_id ? absint($user_id) : get_current_user_id();
    $tags = get_user_meta($user_id, 'dn_blocked_tags', true);

    if (!is_array($tags)) {
        return array();
    }

    $clean_tags = array();
    foreach ($tags as $tag) {
        $tag = is_scalar($tag) ? (string) $tag : '';
        if ($tag !== '') {
            $clean_tags[] = $tag;
        }
    }

    return array_values($clean_tags);
}

function dn_normalize_blocked_author_ids($author_ids, $include_protected = false) {
    if (!is_array($author_ids)) {
        return array();
    }

    $normalized = array();
    foreach ($author_ids as $author_id) {
        $author_id = absint($author_id);
        if ($author_id <= 0) {
            continue;
        }
        if (!$include_protected && dn_is_protected_author($author_id)) {
            continue;
        }
        $normalized[$author_id] = $author_id;
    }

    return array_values($normalized);
}

function dn_get_blocked_author_ids($user_id = 0, $include_protected = false) {
    $user_id = $user_id ? absint($user_id) : get_current_user_id();
    $author_ids = get_user_meta($user_id, 'dn_blocked_authors', true);

    return dn_normalize_blocked_author_ids($author_ids, $include_protected);
}

function dn_is_author_blocked($author_id, $user_id = 0) {
    $author_id = absint($author_id);
    if ($author_id <= 0 || dn_is_protected_author($author_id)) {
        return false;
    }

    return in_array($author_id, dn_get_blocked_author_ids($user_id), true);
}

// 后台管理界面与设置。
add_action('admin_menu', 'dn_add_blocklist_menu');
function dn_add_blocklist_menu() {
    add_menu_page('屏蔽设置', '屏蔽设置', 'read', 'dn-blocklist', 'dn_render_blocklist_page', 'dashicons-filter', 80);
}

function dn_render_blocklist_page() {
    $user_id = get_current_user_id();
    $current_tags = dn_get_blocked_tags($user_id);
    $current_author_ids = dn_get_blocked_author_ids($user_id);
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">屏蔽设置</h1>
        <p class="description">这些设置只影响您自己的浏览体验。文章仍会正常发布和展示给其他用户。</p>

        <style>
            .dn-author-blocklist-section {
                max-width: 600px;
                margin-top: 24px;
            }
            .dn-blocklist-module-title {
                margin-top: 0;
                font-size: 18px;
                line-height: 1.4;
            }
            .dn-blocklist-list-title {
                margin-top: 30px;
                font-size: 15px;
                line-height: 1.4;
            }
            .dn-blocklist-divider {
                max-width: 600px;
                margin: 30px 0 24px;
                border: 0;
                border-top: 1px solid #ccd0d4;
            }
            .dn-blocklist-divider-first {
                margin-top: 24px;
            }
            .dn-author-blocklist-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 5px;
                padding: 8px 12px;
                border-radius: 4px;
                background: #f0f0f1;
            }
            .dn-blocklist-delete {
                color: #d63638;
                text-decoration: none;
            }
        </style>

        <hr class="dn-blocklist-divider dn-blocklist-divider-first">

        <div>
            <h2 class="dn-blocklist-module-title">Tag 屏蔽词设置</h2>
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

            <h3 class="dn-blocklist-list-title">当前已屏蔽tag (<span id="dn-tag-count"><?php echo esc_html(count($current_tags)); ?></span>/10)</h3>
            <ul id="dn-tag-list" style="max-width: 400px;">
                <?php foreach ($current_tags as $tag): ?>
                    <li style="background: #f0f0f1; padding: 8px 12px; margin-bottom: 5px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                        <code><?php echo esc_html($tag); ?></code>
                        <a href="#" class="dn-delete-tag" data-tag="<?php echo esc_attr($tag); ?>" style="color: #d63638; text-decoration: none;">❌ 删除</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <hr class="dn-blocklist-divider">

        <div class="dn-author-blocklist-section">
            <h2 class="dn-blocklist-module-title">作者屏蔽</h2>
            <p class="description">从文章页点击“屏蔽作者”后，作者会出现在这里。您最多可以屏蔽50位作者。该作者将在列表中对您隐藏。</p>

            <h3 class="dn-blocklist-list-title">当前已屏蔽作者 (<span id="dn-author-count"><?php echo esc_html(count($current_author_ids)); ?></span>/<?php echo esc_html(dn_blocked_author_limit()); ?>)</h3>
            <?php if (empty($current_author_ids)): ?>
                <p style="color: #666;">暂无已屏蔽作者。</p>
            <?php else: ?>
                <ul id="dn-author-list" style="max-width: 400px;">
                    <?php foreach ($current_author_ids as $author_id): ?>
                        <?php
                        $author = get_userdata($author_id);
                        $author_name = $author ? $author->display_name : '原作者账号已删除';
                        ?>
                        <li class="dn-author-blocklist-item">
                            <?php if ($author): ?>
                                <strong><?php echo esc_html($author_name); ?></strong>
                            <?php else: ?>
                                <span style="color: #999; font-size: 12px;">原作者账号已删除</span>
                            <?php endif; ?>
                            <a href="#" class="dn-delete-author dn-blocklist-delete" data-author-id="<?php echo esc_attr($author_id); ?>" data-author-name="<?php echo esc_attr($author_name); ?>" data-author-exists="<?php echo $author ? '1' : '0'; ?>">删除</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        function addTag() {
            var newTag = $.trim($('#dn-new-tag-input').val());
            if (newTag === '') return;
            try {
                new RegExp(newTag, 'i');
            } catch (err) {
                $('#dn-tag-feedback').text('屏蔽词不是有效正则，请检查括号、方括号等符号。');
                return;
            }
            $('#dn-tag-feedback').text('处理中...');
            $.post(ajaxurl, {
                action: 'dn_add_blocked_tag',
                tag: newTag,
                nonce: '<?php echo esc_js(wp_create_nonce("dn_tag_nonce")); ?>'
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
                    nonce: '<?php echo esc_js(wp_create_nonce("dn_tag_nonce")); ?>'
                }, function(response) { if (response.success) location.reload(); });
            }
        });

        $('.dn-delete-author').on('click', function(e) {
            e.preventDefault();
            var authorId = $(this).data('author-id');
            var authorName = $(this).data('author-name');
            var authorExists = String($(this).data('author-exists')) !== '0';
            var confirmText = authorExists ? '确定要解除对作者「' + authorName + '」的屏蔽吗？' : '确定要删除这条已失效的作者屏蔽记录吗？';
            if(confirm(confirmText)) {
                $.post(ajaxurl, {
                    action: 'dn_delete_blocked_author',
                    author_id: authorId,
                    nonce: '<?php echo esc_js(wp_create_nonce("dn_author_nonce")); ?>'
                }, function(response) { if (response.success) location.reload(); });
            }
        });
    });
    </script>
    <?php
}

// 处理 AJAX 增删请求。
add_action('wp_ajax_dn_add_blocked_tag', 'dn_add_blocked_tag_ajax');
function dn_add_blocked_tag_ajax() {
    check_ajax_referer('dn_tag_nonce', 'nonce');
    $user_id = get_current_user_id();
    $new_tag = isset($_POST['tag']) ? sanitize_text_field(wp_unslash($_POST['tag'])) : '';
    $current_tags = dn_get_blocked_tags($user_id);

    if ($new_tag === '') wp_send_json_error('屏蔽词不能为空。');
    if (mb_strlen($new_tag, 'UTF-8') > 50) wp_send_json_error('屏蔽词不能超过 50 个字。');
    if (count($current_tags) >= 10) wp_send_json_error('额度已满：最多只能添加 10 个屏蔽词。');
    if (in_array($new_tag, $current_tags, true)) wp_send_json_error('该屏蔽词已存在。');

    $current_tags[] = $new_tag;
    update_user_meta($user_id, 'dn_blocked_tags', $current_tags);
    wp_send_json_success();
}

add_action('wp_ajax_dn_delete_blocked_tag', 'dn_delete_blocked_tag_ajax');
function dn_delete_blocked_tag_ajax() {
    check_ajax_referer('dn_tag_nonce', 'nonce');
    $user_id = get_current_user_id();
    $tag_to_delete = isset($_POST['tag']) ? sanitize_text_field(wp_unslash($_POST['tag'])) : '';
    $current_tags = dn_get_blocked_tags($user_id);

    $current_tags = array_filter($current_tags, function($v) use ($tag_to_delete) { return $v !== $tag_to_delete; });
    update_user_meta($user_id, 'dn_blocked_tags', array_values($current_tags));
    wp_send_json_success();
}

add_action('wp_ajax_dn_add_blocked_author', 'dn_add_blocked_author_ajax');
function dn_add_blocked_author_ajax() {
    check_ajax_referer('dn_author_nonce', 'nonce');
    $user_id = get_current_user_id();
    $author_id = isset($_POST['author_id']) ? absint($_POST['author_id']) : 0;

    if ($author_id <= 0) {
        wp_send_json_error('作者信息无效。');
    }
    if (dn_is_protected_author($author_id)) {
        wp_send_json_error('该作者不可屏蔽。');
    }
    if (!get_userdata($author_id)) {
        wp_send_json_error('该作者账号不存在。');
    }

    $current_author_ids = dn_get_blocked_author_ids($user_id);
    if (in_array($author_id, $current_author_ids, true)) {
        wp_send_json_success('该作者已在屏蔽列表中。');
    }
    if (count($current_author_ids) >= dn_blocked_author_limit()) {
        wp_send_json_error('已达到作者屏蔽上限：最多只能屏蔽50位作者。');
    }

    $current_author_ids[] = $author_id;
    update_user_meta($user_id, 'dn_blocked_authors', dn_normalize_blocked_author_ids($current_author_ids));
    wp_send_json_success('已屏蔽该作者。');
}

add_action('wp_ajax_dn_delete_blocked_author', 'dn_delete_blocked_author_ajax');
function dn_delete_blocked_author_ajax() {
    check_ajax_referer('dn_author_nonce', 'nonce');
    $user_id = get_current_user_id();
    $author_id = isset($_POST['author_id']) ? absint($_POST['author_id']) : 0;
    $current_author_ids = dn_get_blocked_author_ids($user_id, true);

    $current_author_ids = array_filter($current_author_ids, function($v) use ($author_id) { return $v !== $author_id; });
    update_user_meta($user_id, 'dn_blocked_authors', array_values($current_author_ids));
    wp_send_json_success();
}

// 前台拦截逻辑，仅限登录用户。
add_action('wp_head', function() {
    if (!is_admin() && is_user_logged_in()) {
        $user_id = get_current_user_id();
        $tags = dn_get_blocked_tags($user_id);
        $authors = dn_get_blocked_author_ids($user_id);
        $config = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'author_nonce' => wp_create_nonce('dn_author_nonce'),
        );

        echo '<script type="text/javascript">';
        echo 'window.dn_blocked_tags = ' . wp_json_encode($tags) . ';';
        echo 'window.dn_blocked_authors = ' . wp_json_encode($authors) . ';';
        echo 'window.dn_blocklist_config = ' . wp_json_encode($config) . ';';
        echo '</script>';
    }
});

add_action('wp_footer', function() {
    if (!is_admin() && is_user_logged_in()) {
        ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var blockedTags = Array.isArray(window.dn_blocked_tags) ? window.dn_blocked_tags : [];
            var blockedAuthors = Array.isArray(window.dn_blocked_authors) ? window.dn_blocked_authors.map(String) : [];
            var config = window.dn_blocklist_config || {};

            setupAuthorBlockButtons();
            applyBlocklist();

            function setupAuthorBlockButtons() {
                var buttons = document.querySelectorAll('[data-dn-block-author-btn]');
                if (!buttons.length || !config.ajax_url || !config.author_nonce) return;

                buttons.forEach(function(button) {
                    button.addEventListener('click', function(event) {
                        event.preventDefault();
                        var authorId = button.getAttribute('data-author-id');
                        var authorName = button.getAttribute('data-author-name') || '';
                        if (!authorId) return;
                        if (!window.confirm('确定要屏蔽作者「' + authorName + '」的文章吗？')) return;

                        button.textContent = '处理中...';
                        button.style.pointerEvents = 'none';

                        var body = 'action=dn_add_blocked_author'
                            + '&author_id=' + encodeURIComponent(authorId)
                            + '&nonce=' + encodeURIComponent(config.author_nonce);

                        fetch(config.ajax_url, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                            body: body
                        }).then(function(response) {
                            return response.json();
                        }).then(function(response) {
                            if (response && response.success) {
                                button.textContent = '已屏蔽';
                                button.classList.add('dn-author-blocked-label');
                                if (blockedAuthors.indexOf(String(authorId)) === -1) {
                                    blockedAuthors.push(String(authorId));
                                }
                            } else {
                                button.textContent = '屏蔽作者';
                                button.style.pointerEvents = '';
                                window.alert(response && response.data ? response.data : '屏蔽失败，请稍后重试。');
                            }
                        }).catch(function() {
                            button.textContent = '屏蔽作者';
                            button.style.pointerEvents = '';
                            window.alert('屏蔽失败，请稍后重试。');
                        });
                    });
                });
            }

            function applyBlocklist() {
                if (blockedTags.length === 0 && blockedAuthors.length === 0) return;

                var regexList = [];
                blockedTags.forEach(function(tagStr) {
                    try {
                        regexList.push(new RegExp(tagStr, 'i'));
                    } catch (err) {}
                });

                var articles = document.querySelectorAll('.article-panel');
                if (!articles.length) return;

                var blockedCount = 0;
                articles.forEach(function(article) {
                    var hiddenByTag = shouldHideByTag(article, regexList);
                    var hiddenByAuthor = shouldHideByAuthor(article, blockedAuthors);

                    if (hiddenByTag || hiddenByAuthor) {
                        article.style.display = 'none';
                        if (hiddenByTag) article.classList.add('dn-hidden-by-tag');
                        if (hiddenByAuthor) article.classList.add('dn-hidden-by-author');
                        blockedCount++;
                    }
                });

                if (blockedCount > 0) {
                    renderBlocklistNotice(blockedCount);
                }
            }

            function shouldHideByTag(article, regexList) {
                if (regexList.length === 0) return false;

                var tagElements = article.querySelectorAll('.tags a');
                for (var i = 0; i < tagElements.length; i++) {
                    var tagText = tagElements[i].textContent.trim();
                    for (var j = 0; j < regexList.length; j++) {
                        if (regexList[j].test(tagText)) {
                            return true;
                        }
                    }
                }
                return false;
            }

            function shouldHideByAuthor(article, authorIds) {
                if (authorIds.length === 0) return false;

                var authorId = article.getAttribute('data-dn-author-id');
                return authorId && authorIds.indexOf(String(authorId)) !== -1;
            }

            function renderBlocklistNotice(blockedCount) {
                var notice = document.createElement('div');
                notice.style.cssText = 'text-align: center; font-size: 13px; color: #999; margin-bottom: 15px; padding: 10px; background: rgba(0,0,0,0.02); border-radius: 4px;';
                notice.innerHTML = '🛡️ <b>Tag/作者屏蔽功能已启用。</b> 本页有 <b>' + blockedCount + '</b> 篇文章因包含您的屏蔽规则已被自动隐藏，导致每页显示数量不同为正常现象。<br>'
                    + '如部分历史文章未填写tag，请邮件站长修改。请勿留言打扰作者。<br>'
                    + '<a href="#" id="dn-toggle-blocked-btn" style="color: #0073aa; text-decoration: underline; display: inline-block;">👀 点击临时查看本页被屏蔽的文章</a>';

                var paginationBox = document.querySelector('.paginations');
                if (paginationBox && paginationBox.parentNode) {
                    paginationBox.parentNode.insertBefore(notice, paginationBox);
                } else {
                    var board = document.querySelector('.board');
                    if (board) {
                        board.appendChild(notice);
                    }
                }

                var toggleBtn = document.getElementById('dn-toggle-blocked-btn');
                var isShowing = false;
                if (!toggleBtn) return;

                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    isShowing = !isShowing;
                    var hiddenArticles = document.querySelectorAll('.dn-hidden-by-tag, .dn-hidden-by-author');

                    hiddenArticles.forEach(function(article) {
                        if (isShowing) {
                            article.style.display = '';
                            article.style.opacity = '0.5';
                        } else {
                            article.style.display = 'none';
                            article.style.opacity = '1';
                        }
                    });

                    toggleBtn.innerHTML = isShowing ? '🙈 恢复屏蔽状态' : '👀 点击临时查看本页被屏蔽的文章';
                    toggleBtn.style.color = isShowing ? '#d63638' : '#0073aa';
                });
            }
        });
        </script>
        <?php
    }
}, 99);
