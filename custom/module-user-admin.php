<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('pre_user_search', 'change_user_order');
function change_user_order($user_query)
{
    $user_query = new WP_User_Query(array('orderby' => 'registered', 'order' => 'DESC'));
}

function custom_admin_css()
{
    if (
        ! isset($_GET['page']) ||
        ! in_array($_GET['page'], array('apto_edit-phppost_typecustom_post', 'apto_edit-phppost_typemy_hierarchical', 'posts_page_apto_edit-php', 'apto_edit-php'))
    )
        return;

    $user = wp_get_current_user();
    $check_roles = array('editor', 'author');

    if (! array_intersect($check_roles, $user->roles))
        return;

    echo '<style>
        #sort_options {
            display: none !important;
        }
    </style>';
    echo '<style>
        #hint_arrow {
            display: none !important;
        }
    </style>';
}
add_action('admin_head', 'custom_admin_css');

function lltracker_get_client_ip()
{
    $ip = '';

    $xff = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? trim($_SERVER['HTTP_X_FORWARDED_FOR']) : '';
    if ($xff !== '') {
        $parts = array_map('trim', explode(',', $xff));
        foreach ($parts as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $candidate;
            }
        }
        foreach ($parts as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
    }

    if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip !== '' ? $ip : '未知';
}

function lltracker_record_login($user_login, $user)
{
    $ip = lltracker_get_client_ip();
    $ts = time();
    update_user_meta($user->ID, 'lltracker_last_login_ip', $ip);
    update_user_meta($user->ID, 'lltracker_last_login_at', $ts);
}
add_action('wp_login', 'lltracker_record_login', 10, 2);

function lltracker_format_beijing_time($timestamp)
{
    if (!$timestamp || !is_numeric($timestamp)) {
        return '—';
    }
    try {
        $dt = new DateTime('@' . intval($timestamp));
        $dt->setTimezone(new DateTimeZone('Asia/Shanghai'));
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return '—';
    }
}

function lltracker_add_user_columns($columns)
{
    $columns['lltracker_last_login_at'] = '最后登录日期';
    $columns['lltracker_last_login_ip'] = '最后登录IP';
    return $columns;
}
add_filter('manage_users_columns', 'lltracker_add_user_columns');

function lltracker_render_user_columns($value, $column_name, $user_id)
{
    if ($column_name === 'lltracker_last_login_at') {
        $ts = get_user_meta($user_id, 'lltracker_last_login_at', true);
        return esc_html(lltracker_format_beijing_time($ts));
    }
    if ($column_name === 'lltracker_last_login_ip') {
        $ip = get_user_meta($user_id, 'lltracker_last_login_ip', true);
        return $ip ? esc_html($ip) : '—';
    }
    return $value;
}
add_filter('manage_users_custom_column', 'lltracker_render_user_columns', 10, 3);

function lltracker_support_ip_search_in_users(WP_User_Query $query)
{
    if (!is_admin()) {
        return;
    }

    global $pagenow, $wpdb;
    if ($pagenow !== 'users.php') {
        return;
    }

    $raw = '';
    $search = $query->get('search');
    if (is_string($search) && $search !== '') {
        $raw = trim($search, '*');
    } elseif (isset($_REQUEST['s'])) {
        $raw = sanitize_text_field(wp_unslash($_REQUEST['s']));
    }
    if ($raw === '') {
        return;
    }

    $force_ip = false;
    if (stripos($raw, 'ip:') === 0) {
        $force_ip = true;
        $raw = trim(substr($raw, 3));
    }

    $is_ip = filter_var($raw, FILTER_VALIDATE_IP) !== false;
    if (!$is_ip && !$force_ip) {
        return;
    }

    $compare = $is_ip ? '=' : 'LIKE';
    $value = ($compare === 'LIKE') ? ('%' . $wpdb->esc_like($raw) . '%') : $raw;

    $query->set('search', '');
    $query->set('search_columns', array());

    $meta = $query->get('meta_query');
    if (!is_array($meta)) {
        $meta = array();
    }
    $meta[] = array(
        'key'     => 'lltracker_last_login_ip',
        'value'   => $value,
        'compare' => $compare,
    );
    $query->set('meta_query', $meta);
}
add_action('pre_get_users', 'lltracker_support_ip_search_in_users');

add_filter('manage_users_columns', 'add_register_date_column_independent');
function add_register_date_column_independent($columns)
{
    $columns['user_registered'] = '注册时间';
    return $columns;
}

add_filter('manage_users_custom_column', 'show_register_date_column_content', 10, 3);
function show_register_date_column_content($value, $column_name, $user_id)
{
    if ('user_registered' == $column_name) {
        $user = get_userdata($user_id);

        if (function_exists('lltracker_format_beijing_time')) {
            return lltracker_format_beijing_time(strtotime($user->user_registered));
        } else {
            return date('Y-m-d H:i:s', strtotime($user->user_registered) + 8 * 3600);
        }
    }
    return $value;
}

add_filter('manage_users_sortable_columns', 'make_register_date_sortable_independent');
function make_register_date_sortable_independent($columns)
{
    $columns['user_registered'] = array( 'registered', true );
    return $columns;
}

add_filter('wp_dropdown_users', 'replace_reassign_user_dropdown_with_input');
function replace_reassign_user_dropdown_with_input($output) {
    if (strpos($output, 'name="reassign_user"') !== false || strpos($output, 'name=\'reassign_user\'') !== false) {
        $new_input = '<input type="number" name="reassign_user" id="reassign_user" placeholder="在此输入接收者的 用户 ID" style="width: 220px; padding: 5px;" min="1" />';
        $new_input .= '<p class="description" style="color: #0073aa;">💡 为避免海量用户导致页面卡死，系统已关闭下拉菜单。请直接输入要接收这些内容的<strong>目标用户 ID</strong>（纯数字）。</p>';

        return $new_input;
    }

    return $output;
}
