<?php
/**
 * ====================================================
 * 模块：文章与作者收藏
 * 描述：收藏记录存储于 User Meta，后台分页展示
 * ====================================================
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. 前端：动态注入文章和作者收藏按钮
 */
add_action('wp_footer', 'dn_bookmark_frontend_script');
function dn_bookmark_frontend_script() {
    if (!is_single() || !is_user_logged_in()) {
        return;
    }

    $post_id = get_queried_object_id();
    $author_id = absint(get_post_field('post_author', $post_id));
    $user_id = get_current_user_id();
    
    $bookmarks = get_user_meta($user_id, 'dn_bookmarks', true);
    $bookmarks = is_array($bookmarks) ? $bookmarks : array();
    $is_bookmarked = isset($bookmarks[$post_id]);
    $author_bookmarks = get_user_meta($user_id, 'dn_author_bookmarks', true);
    $author_bookmarks = is_array($author_bookmarks) ? $author_bookmarks : array();
    $is_author_bookmarked = $author_id && isset($author_bookmarks[$author_id]);
    $nonce = wp_create_nonce('dn_bookmark_nonce');
    ?>
    <style>
        .dn-bookmark-button.bookmarked {
            color: #999 !important;
            border-color: #dcdcdc !important;
            background-color: transparent !important;
        }
        .dn-bookmark-button.is-loading {
            opacity: 0.5;
            pointer-events: none;
        }
        @media screen and (max-width: 768px) {
            .k-main .details .toolbar .share {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
            }
            .k-main .details .toolbar .share .btn {
                flex: 0 0 88px;
                margin: 0 !important;
            }
        }
    </style>
    <script>
    jQuery(document).ready(function($) {
        var postId = <?php echo absint($post_id); ?>;
        var ajaxurl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
        var nonce = <?php echo wp_json_encode($nonce); ?>;
        var $toolbar = $('.share.float-md-right.text-center').first();

        function addBookmarkButton(id, action, inactiveText, selected) {
            var activeLabel = '取消' + inactiveText;
            var $btn = $('<a>', {
                href: '#', id: id, role: 'button',
                'aria-pressed': selected ? 'true' : 'false',
                'aria-label': selected ? activeLabel : inactiveText,
                'class': 'btn btn-thumbs dn-bookmark-button' + (selected ? ' bookmarked' : '')
            }).css({marginLeft: '10px', transition: 'all 0.3s'});
            $btn.append($('<i>', {'class': 'fas fa-star'}));
            $btn.append($('<span>', {'class': 'ml-1 bookmark-text'}).text(selected ? '取消收藏' : inactiveText));
            $toolbar.append($btn);

            $btn.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                if ($btn.hasClass('is-loading')) return;
                $btn.addClass('is-loading');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {action: action, post_id: postId, security: nonce},
                    success: function(response) {
                        $btn.removeClass('is-loading');
                        if (response.success) {
                            var added = response.data.status === 'added';
                            $btn.toggleClass('bookmarked', added);
                            $btn.attr('aria-pressed', added ? 'true' : 'false');
                            $btn.attr('aria-label', added ? activeLabel : inactiveText);
                            $btn.find('.bookmark-text').text(added ? '取消收藏' : inactiveText);
                        } else {
                            alert(response.data || '操作失败，请重试');
                        }
                    },
                    error: function(xhr) {
                        $btn.removeClass('is-loading');
                        alert('网络连接错误 (' + xhr.status + ')，请稍后再试');
                    }
                });
            });
        }

        if (!$toolbar.length) return;
        addBookmarkButton('dn-bookmark-btn', 'dn_toggle_bookmark', '收藏文章', <?php echo $is_bookmarked ? 'true' : 'false'; ?>);
        <?php if ($author_id) : ?>
        addBookmarkButton('dn-author-bookmark-btn', 'dn_toggle_author_bookmark', '收藏作者', <?php echo $is_author_bookmarked ? 'true' : 'false'; ?>);
        <?php endif; ?>
    });
    </script>
    <?php
}

/**
 * 2. AJAX：处理后端收藏逻辑
 */
add_action('wp_ajax_dn_toggle_bookmark', 'dn_toggle_bookmark_ajax');
function dn_toggle_bookmark_ajax() {
    check_ajax_referer('dn_bookmark_nonce', 'security');

    $post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
    $user_id = get_current_user_id();

    if (!$post_id || !$user_id) {
        wp_send_json_error('参数错误');
    }

    $bookmarks = get_user_meta($user_id, 'dn_bookmarks', true);
    $bookmarks = is_array($bookmarks) ? $bookmarks : array();

    if (isset($bookmarks[$post_id])) {
        unset($bookmarks[$post_id]);
        $status = 'removed';
    } else {
        if (!dn_user_can_read_bookmark_post($post_id)) {
            wp_send_json_error('无权收藏该文章。');
        }

        $bookmarks[$post_id] = current_time('timestamp');
        $status = 'added';
    }

    update_user_meta($user_id, 'dn_bookmarks', $bookmarks);
    wp_send_json_success(array('status' => $status));
}

add_action('wp_ajax_dn_toggle_author_bookmark', 'dn_toggle_author_bookmark_ajax');
function dn_toggle_author_bookmark_ajax() {
    check_ajax_referer('dn_bookmark_nonce', 'security');

    $post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
    $user_id = get_current_user_id();
    $post = $post_id ? get_post($post_id) : null;
    $author_id = $post ? absint($post->post_author) : 0;

    if (!$user_id || !$author_id) {
        wp_send_json_error('参数错误');
    }

    $bookmarks = get_user_meta($user_id, 'dn_author_bookmarks', true);
    $bookmarks = is_array($bookmarks) ? $bookmarks : array();

    if (isset($bookmarks[$author_id])) {
        unset($bookmarks[$author_id]);
        $status = 'removed';
    } else {
        if (!dn_user_can_read_bookmark_post($post_id) || !get_userdata($author_id)) {
            wp_send_json_error('无权收藏该作者。');
        }

        $bookmarks[$author_id] = current_time('timestamp');
        $status = 'added';
    }

    if (!update_user_meta($user_id, 'dn_author_bookmarks', $bookmarks)) {
        wp_send_json_error('操作失败，请重试。');
    }
    wp_send_json_success(array('status' => $status));
}

/**
 * 3. 后台：注册菜单
 */
add_action('admin_menu', 'dn_add_bookmark_menu');
function dn_add_bookmark_menu() {
    add_menu_page('我的收藏', '我的收藏', 'read', 'dn-bookmarks', 'dn_render_bookmarks_page', 'dashicons-star-filled', 75);
}

/**
 * 新增辅助函数：将英文状态转换为美化的中文标签
 */
function dn_get_bookmark_status_badge($status, $exists = true) {
    if (!$exists) {
        return '<span style="background: #fcf0f1; color: #d63638; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;">已彻底删除</span>';
    }

    $status_map = [
        'publish' => ['label' => '已发布', 'bg' => '#edfaec', 'color' => '#00a32a'],
        'draft'   => ['label' => '草稿箱', 'bg' => '#f0f0f1', 'color' => '#50575e'],
        'trash'   => ['label' => '回收站', 'bg' => '#fcf0f1', 'color' => '#d63638'],
        'private' => ['label' => '私密',   'bg' => '#fdf6e6', 'color' => '#996800'],
        'future'  => ['label' => '定时中', 'bg' => '#e8f3fa', 'color' => '#2271b1'],
        'pending' => ['label' => '待审核', 'bg' => '#fdf6e6', 'color' => '#996800'],
    ];

    $style = isset($status_map[$status]) ? $status_map[$status] : ['label' => $status, 'bg' => '#f0f0f1', 'color' => '#50575e'];

    return sprintf(
        '<span style="background: %s; color: %s; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;">%s</span>',
        esc_attr($style['bg']),
        esc_attr($style['color']),
        esc_html($style['label'])
    );
}

function dn_user_can_read_bookmark_post($post_id) {
    $post_id = absint($post_id);

    if (!$post_id || !get_post($post_id)) {
        return false;
    }

    return current_user_can('read_post', $post_id);
}

/**
 * 后台文章信息按固定批次读取，避免收藏较多时生成过长的 IN 查询。
 */
function dn_bookmark_get_post_details($post_ids) {
    global $wpdb;

    $posts_indexed = array();
    $post_ids = array_values(array_unique(array_filter(array_map('absint', $post_ids))));

    for ($offset = 0; $offset < count($post_ids); $offset += 100) {
        $chunk = array_slice($post_ids, $offset, 100);
        $placeholders = implode(',', array_fill(0, count($chunk), '%d'));
        $query = "SELECT p.ID, p.post_title, p.post_author, p.post_date, p.post_status, u.display_name AS author_name
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
            WHERE p.ID IN ($placeholders)
            LIMIT %d";
        $db_posts = $wpdb->get_results($wpdb->prepare($query, array_merge($chunk, array(count($chunk)))));

        foreach ($db_posts as $post) {
            $posts_indexed[$post->ID] = $post;
        }
    }

    return $posts_indexed;
}

function dn_bookmark_get_post_rows($bookmarks, $posts_indexed) {
    $rows = array();
    foreach ($bookmarks as $pid => $time) {
        $pid = absint($pid);
        if (!$pid) {
            continue;
        }

        $post_exists = isset($posts_indexed[$pid]);
        $post = $post_exists ? $posts_indexed[$pid] : null;
        $rows[] = array(
            'ID' => $pid,
            'bookmark_time' => $time,
            'post_title' => $post && !empty($post->post_title) ? $post->post_title : '(该文章已被彻底删除)',
            'author_id' => $post ? absint($post->post_author) : 0,
            'author_name' => $post && $post->author_name ? $post->author_name : '—',
            'post_date' => $post ? $post->post_date : '0000-00-00 00:00:00',
            'post_status' => $post ? $post->post_status : '',
            'post_exists' => $post_exists,
        );
    }
    return $rows;
}

function dn_bookmark_page_links($current, $total, $page_url, $page_key) {
    if ($total < 2) {
        return '';
    }

    $pages = array_unique(array_merge(
        array(1, $total),
        range(max(1, $current - 2), min($total, $current + 2))
    ));
    sort($pages, SORT_NUMERIC);
    $links = array();

    if ($current > 1) {
        $links[] = '<a class="prev page-numbers" href="' . esc_url($page_url(array($page_key => $current - 1))) . '">&laquo;</a>';
    }

    $previous = 0;
    foreach ($pages as $page) {
        if ($previous && $page > $previous + 1) {
            $links[] = '<span class="page-numbers dots">&hellip;</span>';
        }
        $number = esc_html(number_format_i18n($page));
        if ($page === $current) {
            $links[] = '<span aria-current="page" class="page-numbers current">' . $number . '</span>';
        } else {
            $links[] = '<a class="page-numbers" href="' . esc_url($page_url(array($page_key => $page))) . '">' . $number . '</a>';
        }
        $previous = $page;
    }

    if ($current < $total) {
        $links[] = '<a class="next page-numbers" href="' . esc_url($page_url(array($page_key => $current + 1))) . '">&raquo;</a>';
    }

    return implode("\n", $links);
}

/**
 * 4. 后台：渲染文章和作者收藏列表。
 */
function dn_render_bookmarks_page() {
    $user_id = get_current_user_id();

    $action = isset($_GET['action']) && is_string($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
    if ($action === 'remove' && isset($_GET['post_id']) && is_scalar($_GET['post_id'])) {
        $remove_id = absint(wp_unslash($_GET['post_id']));
        check_admin_referer('dn_remove_bookmark_' . $remove_id);
        $meta_key = 'dn_bookmarks';
    } elseif ($action === 'remove_author' && isset($_GET['author_id']) && is_scalar($_GET['author_id'])) {
        $remove_id = absint(wp_unslash($_GET['author_id']));
        check_admin_referer('dn_remove_author_bookmark_' . $remove_id);
        $meta_key = 'dn_author_bookmarks';
    }

    if (isset($meta_key) && $remove_id) {
        $saved = get_user_meta($user_id, $meta_key, true);
        if (is_array($saved) && isset($saved[$remove_id])) {
            unset($saved[$remove_id]);
            update_user_meta($user_id, $meta_key, $saved);
            echo '<div class="updated notice is-dismissible"><p>已成功取消收藏。</p></div>';
        }
    }

    $bookmarks = get_user_meta($user_id, 'dn_bookmarks', true);
    $bookmarks = is_array($bookmarks) ? $bookmarks : array();
    $author_bookmarks = get_user_meta($user_id, 'dn_author_bookmarks', true);
    $author_bookmarks = is_array($author_bookmarks) ? $author_bookmarks : array();

    $orderby = isset($_GET['orderby']) && is_string($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : 'bookmark_time';
    $allowed_orderby = array('author', 'post_date', 'bookmark_time');
    if (!in_array($orderby, $allowed_orderby, true)) {
        $orderby = 'bookmark_time';
    }

    $order = isset($_GET['order']) && is_string($_GET['order']) && sanitize_key(wp_unslash($_GET['order'])) === 'asc' ? 'asc' : 'desc';
    $per_page = 15;
    $total_items = count($bookmarks);
    $total_pages = (int) ceil($total_items / $per_page);
    $paged = isset($_GET['paged']) && is_scalar($_GET['paged']) ? max(1, absint(wp_unslash($_GET['paged']))) : 1;
    $paged = min($paged, max(1, $total_pages));
    $offset = ($paged - 1) * $per_page;

    if ($orderby === 'bookmark_time') {
        if ($order === 'asc') {
            asort($bookmarks, SORT_NUMERIC);
        } else {
            arsort($bookmarks, SORT_NUMERIC);
        }
        $page_bookmarks = array_slice($bookmarks, $offset, $per_page, true);
        $display_posts = dn_bookmark_get_post_rows($page_bookmarks, dn_bookmark_get_post_details(array_keys($page_bookmarks)));
    } else {
        $all_items = dn_bookmark_get_post_rows($bookmarks, dn_bookmark_get_post_details(array_keys($bookmarks)));
        usort($all_items, function($a, $b) use ($orderby, $order) {
            if ($orderby === 'author') {
                $valA = $a['author_name'];
                $valB = $b['author_name'];
            } else {
                $valA = $a['post_date'];
                $valB = $b['post_date'];
            }
            if ($valA == $valB) {
                return 0;
            }
            $cmp = ($valA < $valB) ? -1 : 1;
            return ($order === 'asc') ? $cmp : -$cmp;
        });
        $display_posts = array_slice($all_items, $offset, $per_page);
    }

    $author_total_items = count($author_bookmarks);
    $author_total_pages = (int) ceil($author_total_items / $per_page);
    $author_page = isset($_GET['author_page']) && is_scalar($_GET['author_page']) ? max(1, absint(wp_unslash($_GET['author_page']))) : 1;
    $author_page = min($author_page, max(1, $author_total_pages));
    arsort($author_bookmarks, SORT_NUMERIC);
    $display_authors = array_slice($author_bookmarks, ($author_page - 1) * $per_page, $per_page, true);

    $page_url = function($overrides = array()) use ($orderby, $order, $paged, $author_page) {
        return add_query_arg(array_merge(array(
            'page' => 'dn-bookmarks',
            'orderby' => $orderby,
            'order' => $order,
            'paged' => $paged,
            'author_page' => $author_page,
        ), $overrides), admin_url('admin.php'));
    };

    $get_sort_attributes = function($column_name) use ($orderby, $order, $page_url) {
        if ($orderby === $column_name) {
            $class = "sorted {$order}";
            $next_order = ($order === 'asc') ? 'desc' : 'asc';
        } else {
            $class = "sortable desc";
            $next_order = 'desc';
        }
        $url = $page_url(array('orderby' => $column_name, 'order' => $next_order, 'paged' => 1));
        return array('class' => $class, 'url' => $url);
    };

    $author_attrs = $get_sort_attributes('author');
    $date_attrs   = $get_sort_attributes('post_date');
    $time_attrs   = $get_sort_attributes('bookmark_time');
    ?>

    <div class="wrap">
        <h1 class="wp-heading-inline">我的收藏</h1>
        <hr class="wp-header-end">
        <style>
            .dn-bookmark-divider {
                border: 0;
                border-top: 1px solid #c3c4c7;
                margin: 26px 0 18px;
            }
            .dn-bookmark-module-title {
                font-size: 18px;
                margin: 0 0 12px;
            }
        </style>

        <hr class="dn-bookmark-divider">
        <h2 class="dn-bookmark-module-title">文章收藏</h2>

        <div class="tablenav top">
            <div class="tablenav-pages">
                <span class="displaying-num">共 <?php echo esc_html($total_items); ?> 篇</span>
                <?php
                echo wp_kses_post(dn_bookmark_page_links($paged, $total_pages, $page_url, 'paged'));
                ?>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-title column-primary">
                        <span>文章标题</span>
                    </th>
                    <th scope="col" class="manage-column <?php echo $author_attrs['class']; ?>">
                        <a href="<?php echo esc_url($author_attrs['url']); ?>">
                            <span>作者</span>
                            <span class="sorting-indicators">
                                <span class="sorting-indicator asc" aria-hidden="true"></span>
                                <span class="sorting-indicator desc" aria-hidden="true"></span>
                            </span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column column-date <?php echo $date_attrs['class']; ?>">
                        <a href="<?php echo esc_url($date_attrs['url']); ?>">
                            <span>发布时间</span>
                            <span class="sorting-indicators">
                                <span class="sorting-indicator asc" aria-hidden="true"></span>
                                <span class="sorting-indicator desc" aria-hidden="true"></span>
                            </span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column <?php echo $time_attrs['class']; ?>">
                        <a href="<?php echo esc_url($time_attrs['url']); ?>">
                            <span>收藏时间</span>
                            <span class="sorting-indicators">
                                <span class="sorting-indicator asc" aria-hidden="true"></span>
                                <span class="sorting-indicator desc" aria-hidden="true"></span>
                            </span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column" style="width: 110px;">文章状态</th>
                    <th scope="col" class="manage-column" style="width: 80px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($display_posts)) : ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px 10px; color: #666;">您还没有收藏任何文章。</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($display_posts as $p) : 
                        $pid = $p['ID'];
                        $post_date = $p['post_date'] !== '0000-00-00 00:00:00' ? date('Y-m-d H:i', strtotime($p['post_date'])) : '—';
                        $bookmark_time = date_i18n('Y-m-d H:i', $p['bookmark_time']);
                        $remove_url = wp_nonce_url($page_url(array('action' => 'remove', 'post_id' => $pid)), 'dn_remove_bookmark_' . $pid);
                    ?>
                        <tr>
                            <td class="column-primary" data-colname="文章标题">
                                <strong>
                                    <?php if ($p['post_exists'] && $p['post_status'] === 'publish') : ?>
                                        <a href="<?php echo esc_url(get_permalink($pid)); ?>" target="_blank"><?php echo esc_html($p['post_title']); ?></a>
                                    <?php else : ?>
                                        <span style="color: #999; font-weight: normal;"><?php echo esc_html($p['post_title']); ?></span>
                                    <?php endif; ?>
                                </strong>
                                <button type="button" class="toggle-row"><span class="screen-reader-text">显示此文章收藏的详情</span></button>
                            </td>
                            <td data-colname="作者">
                                <?php if ($p['author_id'] && $p['author_name'] !== '—') : ?>
                                    <a href="<?php echo esc_url(get_author_posts_url($p['author_id'])); ?>"><?php echo esc_html($p['author_name']); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html($p['author_name']); ?>
                                <?php endif; ?>
                            </td>
                            <td data-colname="发布时间"><?php echo esc_html($post_date); ?></td>
                            <td data-colname="收藏时间"><?php echo esc_html($bookmark_time); ?></td>
                            <td data-colname="文章状态"><?php echo dn_get_bookmark_status_badge($p['post_status'], $p['post_exists']); ?></td>
                            <td data-colname="操作">
                                <a href="<?php echo esc_url($remove_url); ?>" style="color: #d63638;" onclick="return confirm('确定要移除此收藏吗？');">移除</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <hr class="dn-bookmark-divider">
        <h2 class="dn-bookmark-module-title">作者收藏</h2>

        <div class="tablenav top">
            <div class="tablenav-pages">
                <span class="displaying-num">共 <?php echo esc_html($author_total_items); ?> 位</span>
                <?php
                echo wp_kses_post(dn_bookmark_page_links($author_page, $author_total_pages, $page_url, 'author_page'));
                ?>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped dn-author-bookmark-table">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-primary">作者昵称</th>
                    <th scope="col" class="manage-column">收藏时间</th>
                    <th scope="col" class="manage-column">最新发布文章标题</th>
                    <th scope="col" class="manage-column" style="width: 80px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($display_authors)) : ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 30px 10px; color: #666;">您还没有收藏任何作者。</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($display_authors as $author_id => $saved_time) :
                        $author_id = absint($author_id);
                        $author = $author_id ? get_userdata($author_id) : false;
                        $latest_post_ids = $author ? get_posts(array(
                            'author' => $author_id,
                            'post_type' => 'post',
                            'post_status' => 'publish',
                            'posts_per_page' => 1,
                            'orderby' => 'date',
                            'order' => 'DESC',
                            'fields' => 'ids',
                            'no_found_rows' => true,
                            'ignore_sticky_posts' => true,
                            'update_post_meta_cache' => false,
                            'update_post_term_cache' => false,
                        )) : array();
                        $latest_post_id = $latest_post_ids ? $latest_post_ids[0] : 0;
                        $remove_url = wp_nonce_url($page_url(array('action' => 'remove_author', 'author_id' => $author_id)), 'dn_remove_author_bookmark_' . $author_id);
                    ?>
                        <tr>
                            <td class="column-primary" data-colname="作者昵称">
                                <?php if ($author) : ?>
                                    <strong><a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>"><?php echo esc_html($author->display_name); ?></a></strong>
                                <?php else : ?>
                                    <span style="color: #999; font-size: 12px;">原作者账号已删除</span>
                                <?php endif; ?>
                                <button type="button" class="toggle-row"><span class="screen-reader-text">显示此作者收藏的详情</span></button>
                            </td>
                            <td data-colname="收藏时间"><?php echo esc_html(date_i18n('Y-m-d H:i', $saved_time)); ?></td>
                            <td data-colname="最新发布文章标题">
                                <?php if ($latest_post_id) : ?>
                                    <a href="<?php echo esc_url(get_permalink($latest_post_id)); ?>"><?php echo esc_html(get_the_title($latest_post_id)); ?></a>
                                <?php else : ?>
                                    <span style="color: #999;">暂无已发布文章</span>
                                <?php endif; ?>
                            </td>
                            <td data-colname="操作"><a href="<?php echo esc_url($remove_url); ?>" style="color: #d63638;" onclick="return confirm('确定要移除此收藏吗？');">移除</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
