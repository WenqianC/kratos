<?php
/**
 * ====================================================
 * 模块：文章收藏功能 (极致性能优化 + 状态显示增强版)
 * 描述：基于 User Meta 存储，内存级哈希匹配与排序分页
 * 完美匹配原生 WordPress 后台排序三角图标 UI
 * ====================================================
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. 前端：动态注入【收藏文章】按钮及样式
 */
add_action('wp_footer', 'dn_bookmark_frontend_script');
function dn_bookmark_frontend_script() {
    if (!is_single() || !is_user_logged_in()) {
        return;
    }

    $post_id = get_the_ID();
    $user_id = get_current_user_id();
    
    $bookmarks = get_user_meta($user_id, 'dn_bookmarks', true);
    $bookmarks = is_array($bookmarks) ? $bookmarks : array();
    $is_bookmarked = isset($bookmarks[$post_id]);
    $nonce = wp_create_nonce('dn_bookmark_nonce');
    ?>
    <style>
        #dn-bookmark-btn.bookmarked {
            color: #999 !important;
            border-color: #dcdcdc !important;
            background-color: transparent !important;
        }
        #dn-bookmark-btn.is-loading {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
    <script>
    jQuery(document).ready(function($) {
        var isBookmarked = <?php echo $is_bookmarked ? 'true' : 'false'; ?>;
        var postId = <?php echo $post_id; ?>;
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var nonce = '<?php echo $nonce; ?>';

        var iconHtml = '<i class="fas fa-star"></i>';
        var textHtml = '<span class="ml-1 bookmark-text">' + (isBookmarked ? '取消收藏' : '收藏文章') + '</span>';
        var btnClass = isBookmarked ? 'btn btn-thumbs bookmarked' : 'btn btn-thumbs';

        var btnHtml = '<a href="javascript:;" id="dn-bookmark-btn" role="button" class="' + btnClass + '" style="margin-left: 10px; transition: all 0.3s;">' + iconHtml + textHtml + '</a>';
        $('.share.float-md-right.text-center').append(btnHtml);

        $('#dn-bookmark-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            var $btn = $(this);
            
            if ($btn.hasClass('is-loading')) return;
            $btn.addClass('is-loading');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dn_toggle_bookmark',
                    post_id: postId,
                    security: nonce
                },
                success: function(response) {
                    $btn.removeClass('is-loading');
                    if (response.success) {
                        if (response.data.status === 'added') {
                            $btn.addClass('bookmarked');
                            $btn.find('.bookmark-text').text('取消收藏');
                        } else {
                            $btn.removeClass('bookmarked');
                            $btn.find('.bookmark-text').text('收藏文章');
                        }
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

    $post_id = intval($_POST['post_id']);
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
        $bookmarks[$post_id] = current_time('timestamp');
        $status = 'added';
    }

    update_user_meta($user_id, 'dn_bookmarks', $bookmarks);
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

/**
 * 4. 后台：渲染列表（内存级哈希与排序，支持显示状态与彻底删除）
 */
function dn_render_bookmarks_page() {
    $user_id = get_current_user_id();
    
    // 移除操作
    if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['post_id'])) {
        check_admin_referer('dn_remove_bookmark_' . intval($_GET['post_id']));
        $remove_id = intval($_GET['post_id']);
        $bookmarks = get_user_meta($user_id, 'dn_bookmarks', true);
        if (is_array($bookmarks) && isset($bookmarks[$remove_id])) {
            unset($bookmarks[$remove_id]);
            update_user_meta($user_id, 'dn_bookmarks', $bookmarks);
            echo '<div class="updated notice is-dismissible"><p>已成功取消收藏。</p></div>';
        }
    }

    $bookmarks = get_user_meta($user_id, 'dn_bookmarks', true);
    $bookmarks = is_array($bookmarks) ? $bookmarks : array();

    $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'bookmark_time';
    $allowed_orderby = array('author', 'post_date', 'bookmark_time');
    if (!in_array($orderby, $allowed_orderby, true)) {
        $orderby = 'bookmark_time';
    }

    $order = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'asc' : 'desc';
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 15;
    
    $total_items = count($bookmarks);
    $total_pages = ceil($total_items / $per_page);
    $offset = ($paged - 1) * $per_page;

    $display_posts = array();
    $post_ids = array_filter(array_map('intval', array_keys($bookmarks)));

    // 查询逻辑重构：统一查询所有状态的数据，并在 PHP 中组装排序
    if (!empty($post_ids)) {
        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
        
        // 极速提取：一次性拉取涉及到的所有文章数据（不受状态限制）
        $db_posts = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_author, p.post_date, p.post_status, u.display_name AS author_name 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
            WHERE p.ID IN ($placeholders)
        ", $post_ids));

        // 构建哈希字典
        $posts_indexed = array();
        foreach ($db_posts as $post) {
            $posts_indexed[$post->ID] = $post;
        }

        // 构建一个包含“彻底删除”数据在内的完整数组，供自由排序
        $all_items = array();
        foreach ($bookmarks as $pid => $time) {
            $post_exists = isset($posts_indexed[$pid]);
            $p = $post_exists ? $posts_indexed[$pid] : null;

            $all_items[] = array(
                'ID' => $pid,
                'bookmark_time' => $time,
                'post_title' => $p && !empty($p->post_title) ? $p->post_title : '(该文章已被彻底删除)',
                'author_name' => $p && $p->author_name ? $p->author_name : '—',
                'post_date' => $p ? $p->post_date : '0000-00-00 00:00:00',
                'post_status' => $p ? $p->post_status : '',
                'post_exists' => $post_exists
            );
        }

        // 使用 PHP 的 usort 魔法进行多维度精准排序
        usort($all_items, function($a, $b) use ($orderby, $order) {
            if ($orderby === 'author') {
                $valA = $a['author_name'];
                $valB = $b['author_name'];
            } elseif ($orderby === 'post_date') {
                $valA = $a['post_date'];
                $valB = $b['post_date'];
            } else {
                $valA = $a['bookmark_time'];
                $valB = $b['bookmark_time'];
            }

            if ($valA == $valB) return 0;
            $cmp = ($valA < $valB) ? -1 : 1;
            return ($order === 'asc') ? $cmp : -$cmp;
        });

        // 完美分页切割
        $display_posts = array_slice($all_items, $offset, $per_page);
    }

    // --- 动态生成表头链接与图标样式 ---
    $get_sort_attributes = function($column_name) use ($orderby, $order) {
        if ($orderby === $column_name) {
            $class = "sorted {$order}";
            $next_order = ($order === 'asc') ? 'desc' : 'asc';
        } else {
            $class = "sortable desc";
            $next_order = 'desc';
        }
        $url = "?page=dn-bookmarks&orderby={$column_name}&order={$next_order}";
        return array('class' => $class, 'url' => $url);
    };

    $author_attrs = $get_sort_attributes('author');
    $date_attrs   = $get_sort_attributes('post_date');
    $time_attrs   = $get_sort_attributes('bookmark_time');
    ?>

    <div class="wrap">
        <h1 class="wp-heading-inline">我的收藏</h1>
        <hr class="wp-header-end">

        <div class="tablenav top">
            <div class="tablenav-pages">
                <span class="displaying-num">共 <?php echo $total_items; ?> 篇</span>
                <?php
                if ($total_pages > 1) {
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $paged
                    ));
                }
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
                        $remove_url = wp_nonce_url(add_query_arg(array('action' => 'remove', 'post_id' => $pid)), 'dn_remove_bookmark_' . $pid);
                    ?>
                        <tr>
                            <td>
                                <strong>
                                    <?php if ($p['post_exists'] && $p['post_status'] === 'publish') : ?>
                                        <a href="<?php echo esc_url(get_permalink($pid)); ?>" target="_blank"><?php echo esc_html($p['post_title']); ?></a>
                                    <?php else : ?>
                                        <span style="color: #999; font-weight: normal;"><?php echo esc_html($p['post_title']); ?></span>
                                    <?php endif; ?>
                                </strong>
                            </td>
                            <td><?php echo esc_html($p['author_name']); ?></td>
                            <td><?php echo esc_html($post_date); ?></td>
                            <td><?php echo esc_html($bookmark_time); ?></td>
                            <td><?php echo dn_get_bookmark_status_badge($p['post_status'], $p['post_exists']); ?></td>
                            <td>
                                <a href="<?php echo esc_url($remove_url); ?>" style="color: #d63638;" onclick="return confirm('确定要移除此收藏吗？');">移除</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
