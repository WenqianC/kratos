<?php
/**
 * Admin post-list author scope filter.
 */

if (!defined('ABSPATH')) {
    exit;
}

function dn_get_admin_post_list_request_value($key, $default = '')
{
    if (!isset($_REQUEST[$key]) || !is_scalar($_REQUEST[$key])) {
        return $default;
    }

    return sanitize_key(wp_unslash($_REQUEST[$key]));
}

function dn_is_admin_post_scope_screen($post_type, $post_status)
{
    global $pagenow;

    return is_admin()
        && 'edit.php' === $pagenow
        && 'post' === $post_type
        && in_array($post_status, array('publish', 'draft', 'trash'), true);
}

function dn_get_admin_post_scope()
{
    if ('' !== dn_get_admin_post_list_request_value('author') || '' !== dn_get_admin_post_list_request_value('author_name')) {
        return 'all';
    }

    return 'all' === dn_get_admin_post_list_request_value('dn_post_scope') ? 'all' : 'mine';
}

function dn_filter_admin_post_list_query($query)
{
    if (!$query->is_main_query()) {
        return;
    }

    $post_type = $query->get('post_type');
    if (!$post_type) {
        $post_type = dn_get_admin_post_list_request_value('post_type', 'post');
    }

    $post_status = $query->get('post_status');
    if (!$post_status) {
        $post_status = dn_get_admin_post_list_request_value('post_status');
    }

    if (!dn_is_admin_post_scope_screen($post_type, $post_status)) {
        return;
    }

    if ($query->get('author') || $query->get('author_name') || 'all' === dn_get_admin_post_scope()) {
        return;
    }

    $query->set('author', get_current_user_id());
}
add_action('pre_get_posts', 'dn_filter_admin_post_list_query');

function dn_render_admin_post_scope_filter($post_type = 'post', $which = 'top')
{
    $post_status = dn_get_admin_post_list_request_value('post_status');

    if ('top' !== $which || !dn_is_admin_post_scope_screen($post_type, $post_status)) {
        return;
    }

    $scope = dn_get_admin_post_scope();
    ?>
    <label class="screen-reader-text" for="dn-post-scope"><?php echo esc_html__('按作者范围筛选', 'kratos'); ?></label>
    <select name="dn_post_scope" id="dn-post-scope">
        <option value="mine"<?php selected($scope, 'mine'); ?>><?php echo esc_html__('我的文章', 'kratos'); ?></option>
        <option value="all"<?php selected($scope, 'all'); ?>><?php echo esc_html__('全部文章', 'kratos'); ?></option>
    </select>
    <?php
}
add_action('restrict_manage_posts', 'dn_render_admin_post_scope_filter', 10, 2);

function dn_render_admin_post_mobile_toolbar_style()
{
    $post_type = dn_get_admin_post_list_request_value('post_type', 'post');
    $post_status = dn_get_admin_post_list_request_value('post_status');

    if (!dn_is_admin_post_scope_screen($post_type, $post_status)) {
        return;
    }
    ?>
    <style>
    @media screen and (max-width: 782px) {
        .tablenav.top {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            overflow-x: auto;
            overflow-y: hidden;
            height: auto;
            padding-bottom: 8px;
            -webkit-overflow-scrolling: touch;
        }

        .tablenav.top .actions {
            display: flex !important;
            flex: 0 0 auto;
            align-items: center;
            float: none;
            white-space: nowrap;
        }

        .tablenav.top .actions > *,
        .tablenav.top .tablenav-pages {
            flex: 0 0 auto;
        }

        .tablenav.top .actions select {
            width: auto;
            max-width: none;
        }

        .tablenav.top .tablenav-pages {
            float: none;
            margin: 0;
        }

        .tablenav.top > br.clear {
            display: none;
        }
    }
    </style>
    <?php
}
add_action('admin_head-edit.php', 'dn_render_admin_post_mobile_toolbar_style');

function dn_render_admin_author_status_link_script()
{
    $post_type = dn_get_admin_post_list_request_value('post_type', 'post');
    $post_status = dn_get_admin_post_list_request_value('post_status');

    if (!dn_is_admin_post_scope_screen($post_type, $post_status)) {
        return;
    }
    ?>
    <script>
    document.querySelectorAll('.wp-list-table .column-author a').forEach(function (link) {
        var url = new URL(link.href, window.location.href);
        var postStatus = <?php echo wp_json_encode($post_status); ?>;

        url.searchParams.set('post_status', postStatus);
        link.href = url.toString();
    });
    </script>
    <?php
}
add_action('admin_footer-edit.php', 'dn_render_admin_author_status_link_script');
