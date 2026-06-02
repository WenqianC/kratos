<?php
/**
 * ====================================================
 * 功能模块：评论区增强插件 (精修版)
 * 包含功能：回复我的、权限隔离、顶部气泡重构、侧边栏清爽化、浏览器标题修复
 * ====================================================
 */

// ----------------------------------------------------
// 1. 助手函数：获取“回复我的”评论总数 (带静态缓存)
// ----------------------------------------------------
function dn_get_user_replies_count() {
    static $count = null;
    if ( $count !== null ) return $count;

    global $wpdb;
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        $count = 0; return $count;
    }

    $count = $wpdb->get_var( $wpdb->prepare( "
        SELECT COUNT(c.comment_ID)
        FROM {$wpdb->comments} c
        JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
        WHERE c.user_id != %d
        AND c.comment_approved IN ('0', '1')
        AND (
            p.post_author = %d
            OR c.comment_parent IN (SELECT comment_ID FROM {$wpdb->comments} WHERE user_id = %d)
        )
    ", $user_id, $user_id, $user_id ) );

    return (int) $count;
}

// ----------------------------------------------------
// 2. 助手函数：获取用户自己特定状态的评论数
// ----------------------------------------------------
function dn_get_user_status_count($status) {
    global $wpdb;
    $user_id = get_current_user_id();
    if (!$user_id) return 0;

    $map = ['moderated' => '0', 'spam' => 'spam', 'trash' => 'trash'];
    $db_status = isset($map[$status]) ? $map[$status] : '0';

    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->comments} WHERE user_id = %d AND comment_approved = %s",
        $user_id, $db_status
    ));
}

// ----------------------------------------------------
// 3. 拦截视图标签：合并“回复我的”并实施权限隔离
// ----------------------------------------------------
add_filter( 'views_edit-comments', 'dn_custom_integrated_comment_views', 999 );

function dn_custom_integrated_comment_views( $views ) {
    $user_id = get_current_user_id();
    if ( ! $user_id ) return $views;

    $new_views = array();

    // 【回复我的】标签
    $replies_count = dn_get_user_replies_count();
    $class_me = ( isset( $_GET['reply_view'] ) && $_GET['reply_view'] === 'me' ) ? 'current' : '';
    $url_me = admin_url( 'edit-comments.php?reply_view=me' );
    
    $new_views['replies_to_me'] = sprintf(
        '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
        esc_url( $url_me ), $class_me, '回复我的', number_format_i18n( $replies_count )
    );

    if ( $class_me === 'current' && isset($views['all']) ) {
        $views['all'] = str_replace( 'current', '', $views['all'] );
    }

    if ( ! current_user_can( 'moderate_comments' ) ) {
        $statuses = ['moderated' => '待审', 'spam' => '垃圾', 'trash' => '回收站'];
        foreach ( $statuses as $key => $label ) {
            $count = dn_get_user_status_count($key);
            if ( $count > 0 ) {
                $class = (isset($_GET['comment_status']) && $_GET['comment_status'] === $key) ? 'current' : '';
                $url = admin_url("edit-comments.php?comment_status=$key");
                $views[$key] = sprintf(
                    '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                    esc_url($url), $class, $label, number_format_i18n($count)
                );
            } else {
                unset($views[$key]); 
            }
        }
    }

    return array_merge( $new_views, $views );
}

// ----------------------------------------------------
// 4. 拦截数据库查询
// ----------------------------------------------------
add_filter( 'comments_clauses', 'dn_integrated_comment_queries', 10, 2 );

function dn_integrated_comment_queries( $pieces, $query ) {
    global $wpdb;
    $user_id = get_current_user_id();
    if ( ! is_admin() || ! $user_id ) return $pieces;

    if ( isset( $_GET['reply_view'] ) && $_GET['reply_view'] === 'me' ) {
        if ( strpos( $pieces['join'], $wpdb->posts ) === false ) {
            $pieces['join'] .= " JOIN {$wpdb->posts} ON {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID";
        }
        $pieces['where'] .= $wpdb->prepare( " 
            AND {$wpdb->comments}.user_id != %d 
            AND ( 
                {$wpdb->posts}.post_author = %d 
                OR {$wpdb->comments}.comment_parent IN (SELECT comment_ID FROM {$wpdb->comments} WHERE user_id = %d) 
            )
        ", $user_id, $user_id, $user_id );
    }

    if ( ! current_user_can( 'moderate_comments' ) ) {
        $status = isset($_REQUEST['comment_status']) ? $_REQUEST['comment_status'] : '';
        if ( in_array( $status, ['moderated', 'spam', 'trash', '0'] ) ) {
            $pieces['where'] .= $wpdb->prepare(" AND {$wpdb->comments}.user_id = %d", $user_id);
        }
    }

    return $pieces;
}

// ----------------------------------------------------
// 5. 侧边栏：彻底隐藏任何评论气泡 
// ----------------------------------------------------
add_action( 'admin_menu', 'dn_hide_comments_sidebar_bubble', 999 );
function dn_hide_comments_sidebar_bubble() {
    global $menu;
    foreach ( $menu as $key => $item ) {
        if ( $item[2] === 'edit-comments.php' ) {
            // 直接重置为“评论”，不拼接任何气泡 HTML
            $menu[$key][0] = __( 'Comments' );
            break;
        }
    }
}

// ----------------------------------------------------
// 6. 顶部黑条：仅保留快捷入口，彻底移除数字显示 (根除 CPU 飙升)
// ----------------------------------------------------
add_action( 'admin_bar_menu', 'dn_replace_admin_bar_comments_bubble', 999 );
function dn_replace_admin_bar_comments_bubble( $wp_admin_bar ) {
    if ( ! is_user_logged_in() ) return;

    // 仅保留原生评论图标，彻底移除 $replies_count 查询和数字气泡
    $icon  = '<span class="ab-icon" aria-hidden="true"></span>';
    $title = $icon . '<span class="screen-reader-text">查看回复我的评论</span>';
    
    $wp_admin_bar->add_node( array(
        'id'    => 'comments',
        'title' => $title,
        // 点击图标依然可以直接跳转到“回复我的”列表
        'href'  => admin_url( 'edit-comments.php?reply_view=me' ),
        'meta'  => array( 'title' => '查看回复我的评论' )
    ) );
}

// ----------------------------------------------------
// 7. 浏览器标签页：彻底隐藏标题中的数字提示
// ----------------------------------------------------
add_filter('admin_title', 'dn_fix_browser_tab_comment_count', 10, 2);
function dn_fix_browser_tab_comment_count($admin_title, $title) {
    global $pagenow;
    // 仅在评论管理页面生效
    if ($pagenow === 'edit-comments.php') {
        // 使用正则彻底移除原生标题中类似 " (1)" 或 "(10)" 的数字提示
        // 并且不再向标题中拼接任何新的数字
        $admin_title = preg_replace('/\s?\(\d+\)/', '', $admin_title);
    }
    return $admin_title;
}

// ----------------------------------------------------
// 8. 仪表盘小工具：最新回复
// ----------------------------------------------------
add_action('wp_dashboard_setup', 'add_replies_to_me_dashboard_widget');
function add_replies_to_me_dashboard_widget() {
    if (current_user_can('edit_posts')) {
        wp_add_dashboard_widget('dashboard_replies_to_me', '💬 回复我的', 'render_replies_to_me_dashboard_widget');
    }
}

function render_replies_to_me_dashboard_widget() {
    global $wpdb;
    $user_id = get_current_user_id();
    $comments = $wpdb->get_results( $wpdb->prepare( "
        SELECT c.* FROM {$wpdb->comments} c
        JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
        WHERE c.user_id != %d AND c.comment_approved IN ('0', '1')
        AND ( p.post_author = %d OR c.comment_parent IN (SELECT comment_ID FROM {$wpdb->comments} WHERE user_id = %d) )
        ORDER BY c.comment_date_gmt DESC LIMIT 5
    ", $user_id, $user_id, $user_id ) );

    if (empty($comments)) {
        echo '<div style="padding: 12px; color: #646970; text-align: center;">暂无新回复。</div>';
        return;
    }

    echo '<div id="activity-widget"><div id="latest-comments"><ul>';
    foreach ($comments as $comment) {
        $avatar_url = get_avatar_url($comment->comment_author_email, array('size' => 50));
        $author = esc_html($comment->comment_author);
        $post_title = esc_html(get_the_title($comment->comment_post_ID));
        $content = wp_html_excerpt(strip_tags($comment->comment_content), 50, '...');
        $edit_url = admin_url("comment.php?action=editcomment&c={$comment->comment_ID}");
        $view_url = esc_url(get_comment_link($comment));
        $status_style = ($comment->comment_approved == '0') ? 'background: #fcf0f1; border-left: 4px solid #d63638;' : '';

        echo "<li class='comment' style='margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f1; {$status_style}'>";
        echo "<img src='{$avatar_url}' class='avatar' style='float: left; margin-right: 15px; border-radius: 50%;' width='50' height='50'>";
        echo "<div class='dashboard-comment-wrap' style='overflow: hidden;'>";
        echo "<p class='comment-meta' style='margin: 0 0 5px; color: #646970;'>由 <strong>{$author}</strong> 发表于 <a href='".get_permalink($comment->comment_post_ID)."'>{$post_title}</a></p>";
        echo "<blockquote style='margin: 0 0 8px; font-size: 13px; color: #3c434a; line-height: 1.5;'><p>{$content}</p></blockquote>";
        echo "<p class='row-actions' style='margin: 0; font-size: 13px;'><a href='{$edit_url}'>编辑</a> | <a href='{$view_url}'>查看</a></p>";
        echo "</div></li>";
    }
    echo '</ul></div></div><div style="margin-top: 10px; text-align: right;"><a class="button button-primary" href="' . admin_url('edit-comments.php?reply_view=me') . '">查看所有回复</a></div>';
}

// ----------------------------------------------------
// 9. 仪表盘“概览”：为非管理员隐藏全局评论统计
// ----------------------------------------------------
add_action('admin_head', 'dn_hide_dashboard_glance_comments');
function dn_hide_dashboard_glance_comments() {
    // 只有非管理员（无法审核评论的用户）才隐藏
    if (!current_user_can('moderate_comments')) {
        echo '<style>
            #dashboard_right_now .comment-count, 
            #dashboard_right_now .comment-mod-count { 
                display: none !important; 
            }
        </style>';
    }
}