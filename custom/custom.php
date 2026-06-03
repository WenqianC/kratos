<?php
// 请在第三行开始编写代码
define('ALLOW_UNFILTERED_UPLOADS', true); // allow users to upload files

add_filter('big_image_size_threshold', '__return_false');
add_filter('wp_image_maybe_exif_rotate', '__return_false');

// define( 'WP_MEMORY_LIMIT', '256M' );

add_action('pre_user_search', 'change_user_order');
function change_user_order($user_query)
{
    $user_query = new WP_User_Query(array('orderby' => 'registered', 'order' => 'DESC'));
}

add_action('the_content', 'make_clickable');

// add_filter( 'posts_search', 'include_password_posts_in_search' );
// function include_password_posts_in_search( $search ) {
//     global $wpdb;
//     if( !is_user_logged_in() ) {
//         $pattern = " AND ({$wpdb->prefix}posts.post_password = '')";
//         $search = str_replace( $pattern, '', $search );
//     }
//     return $search;
// }

function remove_image_srcset($sources)
{
    return false;
}
add_filter('wp_calculate_image_srcset', 'remove_image_srcset');


function custom_admin_css()
{
    // Verify if we are on a specified APTO reorder page.
    if (
        ! isset($_GET['page']) ||
        ! in_array($_GET['page'], array('apto_edit-phppost_typecustom_post', 'apto_edit-phppost_typemy_hierarchical', 'posts_page_apto_edit-php', 'apto_edit-php'))
    )
        return; // Exit if not on a specified page.

    // Retrieve the current user's data to check their role.
    $user = wp_get_current_user();

    // Define a list of roles that should have a restricted view.
    $check_roles = array('editor', 'author');

    // Only apply custom styles if the user has one of the specified roles.
    if (! array_intersect($check_roles, $user->roles))
        return; // Exit if the user role is not in the specified list.

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

/**
 * 获取真实客户端 IP（优先 X-Forwarded-For，其次 CF 头，最后 REMOTE_ADDR）
 *
 * @return string 返回解析到的 IP 地址字符串
 */
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

/**
 * 在用户登录成功后记录最后登录时间与IP
 *
 * @param string  $user_login 登录用户名
 * @param WP_User $user       用户对象
 * @return void
 */
function lltracker_record_login($user_login, $user)
{
    $ip = lltracker_get_client_ip();
    $ts = time();
    update_user_meta($user->ID, 'lltracker_last_login_ip', $ip);
    update_user_meta($user->ID, 'lltracker_last_login_at', $ts);
}
add_action('wp_login', 'lltracker_record_login', 10, 2);

/**
 * 将时间戳格式化为北京时间字符串
 *
 * @param int $timestamp UNIX 时间戳（秒）
 * @return string 返回形如 Y-m-d H:i:s 的北京时间
 */
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

/**
 * 在后台用户列表新增列：最后登录时间、最后登录IP
 *
 * @param array $columns 原有列数组
 * @return array 返回加入新列后的数组
 */
function lltracker_add_user_columns($columns)
{
    $columns['lltracker_last_login_at'] = '最后登录日期';
    $columns['lltracker_last_login_ip'] = '最后登录IP';
    return $columns;
}
add_filter('manage_users_columns', 'lltracker_add_user_columns');

/**
 * 渲染后台用户列表中的自定义列内容
 *
 * @param string $value       原列值
 * @param string $column_name 列名称
 * @param int    $user_id     用户ID
 * @return string 返回要显示的列内容
 */
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


// ======================================================
// 以下是新增代码：在用户列表显示“注册时间”
// 请粘贴在原有代码的最后面
// ======================================================

/**
 * 1. 在用户列表表头增加“注册时间”列
 */
add_filter('manage_users_columns', 'add_register_date_column_independent');
function add_register_date_column_independent($columns)
{
    // 将其插入到最后一列，或者您可以调整位置
    $columns['user_registered'] = '注册时间';
    return $columns;
}

/**
 * 2. 填充“注册时间”这一列的数据
 */
add_filter('manage_users_custom_column', 'show_register_date_column_content', 10, 3);
function show_register_date_column_content($value, $column_name, $user_id)
{
    if ('user_registered' == $column_name) {
        $user = get_userdata($user_id);

        // 复用您上面定义过的 lltracker_format_beijing_time 函数，保持时间格式一致
        // 如果上面那个函数改名了，这里会自动用备用逻辑
        if (function_exists('lltracker_format_beijing_time')) {
            return lltracker_format_beijing_time(strtotime($user->user_registered));
        } else {
            // 备用逻辑：直接 +8 小时显示
            return date('Y-m-d H:i:s', strtotime($user->user_registered) + 8 * 3600);
        }
    }
    return $value;
}

/**
 * 3. 允许点击“注册时间”进行排序，并默认设为倒序（从新到旧）
 */
add_filter('manage_users_sortable_columns', 'make_register_date_sortable_independent');
function make_register_date_sortable_independent($columns)
{
    // 将单一的字符串改为数组，第二个参数 true 代表首次点击默认为 DESC (倒序)
    $columns['user_registered'] = array( 'registered', true );
    return $columns;
}

// ==========================================
// 验证问答（注册 & 找回密码 共用）
// ==========================================

// 1. 在“注册”和“找回密码”表单中显示问题
add_action('register_form', 'add_security_question');
function add_security_question()
{ ?>
    <p>
        <label for="proof">验证问答：本站作品有个暴露m的冷门双马尾人物，她的职业是？英文全小写。2026.6.1起禁止公开在外发布答案，禁止公开发帖提供教学私聊答案。被发现停止新用户注册1个月。<br />
            <input type="text" name="proof" id="proof" class="input" size="25" tabindex="20" /></label>
    </p>
<?php }
add_action('lostpassword_form', 'add_security_question2'); // 新增：挂载到找回密码表单
function add_security_question2()
{ ?>
    <p>
        <label for="proof">验证问答：本站作品主角（两人都可以）的生日是什么？四位数字mmdd<br />
            <input type="text" name="proof" id="proof" class="input" size="25" tabindex="20" /></label>
    </p>
<?php }


// 2. 验证“注册”表单的提交
add_action('register_post', 'add_security_question_validate', 10, 3);
function add_security_question_validate($sanitized_user_login, $user_email, $errors)
{
    // 这里设置正确答案，支持多个答案
    if (! isset($_POST['proof']) || empty($_POST['proof'])) {
        $errors->add('proofempty', '<strong>错误</strong>: 您必须回答验证问题。');
    } elseif (strtolower($_POST['proof']) != 'artist' && strtolower($_POST['proof']) != '画家') {
        $errors->add('prooffail', '<strong>错误</strong>: 验证问题回答错误。');
    }
}


// 3. 新增：验证“找回密码”表单的提交
add_action('lostpassword_post', 'add_lostpassword_security_question_validate');
function add_lostpassword_security_question_validate($errors)
{
    // 【关键通行证】：如果是管理员在 WordPress 后台发起的重置请求，直接放行，跳过问题验证！
    if ( is_admin() ) {
        return;
    }

    // 找回密码的验证逻辑与注册相同
    if (! isset($_POST['proof']) || empty($_POST['proof'])) {
        $errors->add('proofempty', '<strong>错误</strong>: 您必须回答验证问题。');
    } elseif (strtolower($_POST['proof']) != '0228' && strtolower($_POST['proof']) != '1031') {
        $errors->add('prooffail', '<strong>错误</strong>: 验证问题回答错误。');
    }
}


/**
 * 1. 添加“重大更新”侧边栏面板
 */
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

/**
 * 2. 渲染面板内容（含字数限制和验证逻辑）
 */
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

            // 1. 勾选切换显示
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

            // 2. 输入监听：实时预览 + 字数统计
            $noteInput.on('input', function() {
                var val = $(this).val();
                var len = val.length;

                // 更新预览
                if (val) {
                    $notePreview.text('[' + val + ']');
                } else {
                    $notePreview.text('');
                }

                // 更新计数器颜色
                $counter.text(len + '/15');
                if (len > 0 && len < 4) {
                    $counter.css('color', 'red'); // 字数太少显红
                } else {
                    $counter.css('color', 'green'); // 正常显绿
                }
            });

            // 3. 拦截提交：核心验证逻辑
            var submitButtons = '#publish, .editor-post-publish-button__button, .editor-post-publish-button';

            $(document).on('click', submitButtons, function(e) {
                // 只有勾选了才验证
                if ($checkbox.is(':checked')) {
                    var note = $.trim($noteInput.val());
                    var len = note.length;

                    // 验证条件：为空 OR 小于4字
                    // (大于15字已经被 input 的 maxlength 属性拦截了，这里主要防过短)
                    if (len < 4) {
                        alert('【提交失败】\n\n更新说明字数不符合要求！\n\n当前字数：' + len + '\n要求范围：4 - 15 个字\n\n请修改后再提交。');

                        // 高亮输入框
                        $noteInput.focus().css('border', '1px solid red');

                        // 阻止 WP 提交
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }

                    // 验证通过，恢复样式
                    $noteInput.css('border', '');
                }
            });
        });
    </script>
<?php
}

/**
 * 3. 后端处理：保存数据
 */
function reset_publish_date_and_title_on_major_update($data, $postarr)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $data;

    // 安全检查
    if (! isset($_POST['major_update_nonce']) || ! wp_verify_nonce($_POST['major_update_nonce'], 'major_update_nonce_action')) {
        return $data;
    }

    if (! current_user_can('edit_post', $postarr['ID'])) return $data;

    // 只有勾选了复选框才执行
    if (isset($_POST['is_major_update']) && $_POST['is_major_update'] == '1') {
        if (! isset($_POST['major_update_note'])) {
            return $data;
        }

        $note = sanitize_text_field($_POST['major_update_note']);
        $note_length = mb_strlen($note, 'UTF-8');

        // 后端同样执行 4-15 字规则，避免前端脚本未触发时误改发布时间/标题。
        if ($note_length < 4 || $note_length > 15) {
            return $data;
        }

        // 1. 修改时间
        $current_time = current_time('mysql');
        $current_time_gmt = current_time('mysql', 1);
        $data['post_date']     = $current_time;
        $data['post_date_gmt'] = $current_time_gmt;

        // 2. 修改标题
        $data['post_title'] = $data['post_title'] . ' [' . $note . ']';
    }

    return $data;
}
add_filter('wp_insert_post_data', 'reset_publish_date_and_title_on_major_update', 10, 2);

// 移除文章编辑页面的“作者” metabox
function remove_author_metabox()
{
    remove_meta_box('authordiv', 'post', 'normal');
}
add_action('admin_menu', 'remove_author_metabox');




// ==========================================
// 🛡️ 智能防 CC 采集与恶意搜索防御代码 (允许登录用户极速搜索)
// ==========================================

/**
 * 1. 无死角 SQL 过滤：强行剔除对“正文”和“摘要”的搜索
 * 只要不是管理员，全站任何角落（前台/后台/API）都不允许发生全表扫描！
 */
add_filter( 'posts_search', 'surgical_remove_content_search', 9999, 2 );
function surgical_remove_content_search( $search, $wp_query ) {
    // 只要没有管理员权限，并且 search 语句不为空
    if ( ! current_user_can( 'manage_options' ) && ! empty( $search ) ) {
        // 无情抹除对 post_content (正文) 和 post_excerpt (摘要) 的查询
        $search = preg_replace("/\s*OR\s*\([a-zA-Z0-9_]+\.post_content\s+LIKE\s+'[^']+'\)/i", "", $search);
        $search = preg_replace("/\s*OR\s*\([a-zA-Z0-9_]+\.post_excerpt\s+LIKE\s+'[^']+'\)/i", "", $search);
    }
    return $search;
}

/**
 * 2. 升级版：强制限制单次查询的最大文章数 (防恶意扒站)
 * 无论前台还是后台，只要不是管理员，统统限制！
 */
add_action( 'pre_get_posts', 'force_strict_posts_limit_for_bots', 999 );
function force_strict_posts_limit_for_bots( $query ) {
    // 核心改变：不判断 is_admin() 了，只判断权限
    // 只要当前操作的人不是“管理员 (manage_options)”
    if ( ! current_user_can( 'manage_options' ) ) {
        
        $ppp = $query->get('posts_per_page');
        
        // 如果他敢请求超过 50 篇，或者请求全部 (-1)，强制压回 20 篇
        if ( $ppp > 50 || $ppp == -1 ) {
            $query->set( 'posts_per_page', 20 );
        }
    }
}

/**
 * 3. 限制：只允许登录用户使用搜索功能
 * 游客搜不到，逼迫恶意爬虫必须使用账号，方便后期封号
 */
add_action( 'template_redirect', 'restrict_search_to_logged_in_users' );
function restrict_search_to_logged_in_users() {
    if ( is_search() && ! is_user_logged_in() ) {
        // 如果没登录，直接返回 403 错误并提示
        wp_die( '为了网站安全与性能，搜索功能仅限已登录用户使用。', '访问受限', array( 'response' => 403 ) );
        exit;
    }
}





/**
 * 在所有由 WordPress 发出的邮件末尾全局追加提示语
 */
add_filter( 'wp_mail', 'append_custom_notice_to_all_emails', 9999 );
function append_custom_notice_to_all_emails( $args ) {
    
    // 纯文本格式的提示语 (\n 代表换行)
    $custom_text = "\n\n---------------------------------\n温馨提示：请将链接【完整】复制进浏览器打开。不要在邮箱app中打开。";
    
    // HTML 格式的提示语 (带一点基础的排版和颜色，显得更专业)
    $custom_html = '<br><br><hr style="border:none; border-top:1px dashed #ccc; margin-top:20px;"><p style="color:#888; font-size:13px;"><strong>温馨提示：</strong>请将链接【完整】复制进浏览器打开。不要在邮箱app中打开。</p>';

    // 1. 判断当前这封邮件是纯文本还是 HTML 格式
    $is_html = false;
    if ( isset( $args['headers'] ) ) {
        $headers = is_array( $args['headers'] ) ? implode( "\n", $args['headers'] ) : $args['headers'];
        if ( stripos( $headers, 'text/html' ) !== false ) {
            $is_html = true;
        }
    }

    // 2. 根据不同的邮件格式，安全地把提示语拼接到正文末尾
    if ( $is_html ) {
        // 如果是 HTML 邮件，尽量把文字插在 </body> 标签结束之前，确保排版不乱
        if ( stripos( $args['message'], '</body>' ) !== false ) {
            $args['message'] = str_ireplace( '</body>', $custom_html . "\n</body>", $args['message'] );
        } else {
            // 如果没有标准的 body 标签，直接硬拼接到最后
            $args['message'] .= $custom_html;
        }
    } else {
        // 如果是纯文本邮件（WordPress 默认的大多是这种），直接追加
        $args['message'] .= $custom_text;
    }

    // 返回修改后的邮件数据给服务器发送
    return $args;
}


/**
 * 强行在找回密码邮件末尾加上 IP 地址追踪记录
 */
add_filter( 'retrieve_password_message', 'force_add_ip_to_reset_email', 9999, 4 );
function force_add_ip_to_reset_email( $message, $key, $user_login, $user_data ) {
    
    // 获取发起请求的真实 IP 地址
    $user_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '未知 IP';
    
    // 准备要追加的提示语
    $ip_notice = "\n\n此密码重置请求来自 IP 地址 " . $user_ip . "。";

    // 检查一下邮件正文里是不是已经有这句话了，如果没有，我们再追加，防止重复
    if ( strpos( $message, 'IP 地址' ) === false ) {
        $message .= $ip_notice;
    }
    
    return $message;
}

/**
 * ====================================================
 * 2. 拦截恶意关键词注册，并弹出自定义报错信息
 * ====================================================
 */
add_filter('registration_errors', 'restrict_sensitive_usernames_with_msg', 999, 3);

function restrict_sensitive_usernames_with_msg($errors, $sanitized_user_login, $user_email) {
    
    // 黑名单关键词，只要包含这些词就会被拒绝注册
    $forbidden_keywords = array('admin', 'user', 'root', 'system', 'support', 'manager', 'webmaster', 'dnforlife', 'allfordn');
    
    // 将用户填写的用户名全部转为小写，防止大小写绕过
    $username_lower = strtolower($sanitized_user_login);

    // 循环比对黑名单
    foreach ($forbidden_keywords as $keyword) {
        if (strpos($username_lower, $keyword) !== false) {
            // 核心魔法：向系统的错误对象中塞入我们自定义的中文提示
            $errors->add(
                'username_forbidden', // 错误代码（自己随便起，系统内部用的）
                '<strong>错误</strong>：您输入的用户名包含系统保留或不支持的关键词，请更换其他名称。' // 展示给用户的提示语
            );
            break; // 只要命中一个黑名单词，就立刻停止比对，直接报错
        }
    }
    
    // 返回包含了（或者未包含）自定义错误的 $errors 对象
    return $errors;
}

/**
 * ====================================================
 * 3. 禁止管理员账号通过前台找回密码 (防邮件轰炸)
 * ====================================================
 */
add_filter('allow_password_reset', 'disable_admin_password_reset', 999, 2);
function disable_admin_password_reset($allow, $user_id) {
    $user = get_userdata($user_id);
    
    // 如果该用户是管理员，或者是你的特定账号 admin000
    if ($user && (in_array('administrator', (array) $user->roles) || $user->user_login === 'admin000')) {
        // 直接返回错误，掐断发送邮件的流程
        return new WP_Error('no_password_reset', '出于安全考虑，管理员账号已禁用特定账号的前台密码重置功能。');
    }
    return $allow; // 普通访客依然可以正常找回密码
}


/**
 * ====================================================
 * 解决大体量网站转移用户数据时的页面卡死问题：
 * 拦截下拉菜单 HTML，替换为轻量级的【用户 ID 输入框】
 * ====================================================
 */
add_filter('wp_dropdown_users', 'replace_reassign_user_dropdown_with_input');
function replace_reassign_user_dropdown_with_input($output) {
    // 精准拦截系统中 name 属性为 "reassign_user" 的下拉框（即删除确认页的交接框）
    if (strpos($output, 'name="reassign_user"') !== false || strpos($output, 'name=\'reassign_user\'') !== false) {
        
        // 直接用一段干净的 HTML 文本框将其替换
        $new_input = '<input type="number" name="reassign_user" id="reassign_user" placeholder="在此输入接收者的 用户 ID" style="width: 220px; padding: 5px;" min="1" />';
        $new_input .= '<p class="description" style="color: #0073aa;">💡 为避免海量用户导致页面卡死，系统已关闭下拉菜单。请直接输入要接收这些内容的<strong>目标用户 ID</strong>（纯数字）。</p>';
        
        return $new_input;
    }
    
    // 如果是网站其他地方的普通下拉框，原样放行，不干预
    return $output;
}


/**
 * ====================================================
 * 禁止上传可能携带脚本的 SVG/HTML/XML 文件
 * ====================================================
 */
add_filter('wp_handle_upload_prefilter', 'dn_block_scriptable_upload_types');
function dn_block_scriptable_upload_types($file) {
    $blocked_extensions = array('svg', 'svgz', 'html', 'htm', 'xml', 'xhtml');
    $extension = strtolower(pathinfo(isset($file['name']) ? $file['name'] : '', PATHINFO_EXTENSION));

    if (in_array($extension, $blocked_extensions, true)) {
        $file['error'] = '出于安全考虑，本站禁止上传 SVG、HTML、XML 等可能包含脚本的文件。';
    }

    return $file;
}


require_once __DIR__ . '/module-reply-to-me.php';


/**
 * ====================================================
 * 功能模块：文章编辑界面规范 (温和提示与标签强制填写)
 * ====================================================
 */

// 1. 在标题输入框下方添加 CP 格式说明
add_action( 'edit_form_after_title', 'add_custom_notice_after_title' );
function add_custom_notice_after_title( $post ) {
    if ( 'post' === $post->post_type ) {
        // 保持 10px 缩进对齐，并使用 <strong> 加粗标题引导语
        echo '<div style="color: #646970; font-size: 13px; margin-top: 5px; padding-left: 10px;"><strong>标题格式：</strong>标题里请带上CP名。如果是无cp，也请带上主要角色名。发帖规约（必读）<a href="https://www.dnforlife.com/200261" target="_blank" style="color: #0073aa; text-decoration: underline;">请点我查看</a>。</div>';
    }
}

// 2 & 3. 使用 JavaScript 修改提示语并拦截发布按钮
add_action( 'admin_footer-post-new.php', 'enforce_tag_rules_js' );
add_action( 'admin_footer-post.php', 'enforce_tag_rules_js' );

function enforce_tag_rules_js() {
    global $post_type;
    if ( 'post' !== $post_type ) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        
        // --- 需求 2：修改标签输入框的提示文字 ---
        var tagHint = $('#new-tag-post_tag-desc');
        if (tagHint.length) {
            // 仅修改文字，保持原生灰色
            tagHint.text('请每次输入一个标签，并点击“添加”按钮（或按回车）。请注意，含成人内容必须打“R18”标签；cp向文章请输入cp名作为标签。');
        }

        // --- 需求 3：拦截发布按钮，强制要求打 Tag ---
        $('#post').on('submit', function(e) {
            // 获取已保存的标签和当前输入框里还没来得及添加的字
            var savedTags = $('#tax-input-post_tag').val();
            var typingTag = $('#new-tag-post_tag').val(); 
            
            // 如果既没有已保存的标签，输入框里也是空的，说明完全没打 Tag
            if ( (!savedTags || savedTags.trim() === '') && (!typingTag || typingTag.trim() === '') ) {
                e.preventDefault(); // 中止发布
                
                // 弹出警告说明 (浏览器原生弹窗不支持加粗，此处使用【】符号实现视觉强调)
                var alertMsg = "⚠️发布失败：请添加文章标签！\n" +
                               "为读者便于阅读，请遵守以下Tag规范：\n" +
                               "【固定CP】：只打 CP 标签，无需打该 CP 的单人标签。（配角需打单人标签）\n" +
                               "【无差/互攻】：无差请同时打正逆双向标签，并加打“无差”；互攻请加打“互攻”。\n" +
                               "【分级预警】：含成人内容必须打“R18”标签！\n" +
                               "【避免冗余】：请尽量选择下拉列表已有的标签，非必要请勿新增。";
                
                alert(alertMsg);
                
                // 恢复发布按钮为可点击状态
                $('#publish').removeClass('button-primary-disabled');
                $('.spinner').removeClass('is-active');
                
                // 自动聚焦到标签输入框，逼迫用户填
                $('#new-tag-post_tag').focus();
                
                return false;
            }
        });
        
    });
    </script>
    <?php
}

/**
 * ====================================================
 * 功能模块：精准拦截 Kratos 主题评论回复邮件 (comment_notify)
 * ====================================================
 */
add_action('init', function() {
    // 源码没写优先级，所以必定是默认的 10
    remove_action('comment_post', 'comment_notify', 10);
    remove_action('comment_post', 'comment_approved', 10); 
}, 99); // 让这个卸载动作在主题加载完之后执行


/**
 * ====================================================
 * 功能模块：前端剪贴板净化 (强制 wpDiscuz 评论区纯文本粘贴)
 * ====================================================
 */
add_action('wp_footer', 'dn_force_plain_text_paste_in_comments', 99);

function dn_force_plain_text_paste_in_comments() {
    // 仅在文章或页面（可能包含评论区的地方）加载此脚本，避免影响后台
    if ( ! is_singular() ) return;
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        // 监听网页级的“粘贴”事件
        document.body.addEventListener('paste', function(e) {
            var target = e.target;
            
            // 1. 确认触发粘贴的地方是在 wpDiscuz 的评论区域内
            // 包括富文本编辑器 (.ql-editor) 和 纯文本输入框 (textarea)
            var isInWpDiscuz = target.closest('.wpd-form-wrap') && (target.closest('.ql-editor') || target.tagName.toLowerCase() === 'textarea');
            
            if (isInWpDiscuz) {
                // 2. 获取剪贴板里的数据
                var clipboardData = e.clipboardData || window.clipboardData;
                if (!clipboardData) return;
                
                var plainText = clipboardData.getData('text/plain');
                var hasHtml = clipboardData.getData('text/html');
                
                // 3. 核心逻辑：如果剪贴板里带有 HTML 格式（比如从 Word 或网页复制），并且包含文字
                if (plainText && hasHtml) {
                    // 阻止浏览器默认的“带格式粘贴”行为
                    e.preventDefault();
                    
                    // 将干净的纯文本模拟键盘输入，插入到光标当前位置
                    if (document.queryCommandSupported('insertText')) {
                        document.execCommand('insertText', false, plainText);
                    } else {
                        // 兼容极少数老旧浏览器
                        document.execCommand('paste', false, plainText);
                    }
                }
                // 注意：如果不包含 HTML，或者是直接粘贴图片文件，脚本会直接放行，不影响正常交互
            }
        });
    });
    </script>
    <?php
}



/**
 * ====================================================
 * 全局开关：控制是否显示文章的“热度”和“点赞”
 * 逻辑：仅允许有权限“编辑”该文章的用户（即管理员和本文作者）查看
 * ====================================================
 */
function dn_is_show_post_stats( $post_id = null ) {
    // 如果没有传入特定的文章 ID，则自动获取当前页面的文章 ID
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    // 如果连 ID 都获取不到（比如在某些非文章列表的特殊页面），为安全起见默认隐藏
    if ( ! $post_id ) {
        return false;
    }

    // 核心判定：当前登录用户是否有权限编辑这篇特定的文章？
    return current_user_can( 'edit_post', $post_id );
}


require_once __DIR__ . '/dn-tag-blocklist.php';


/**
 * ====================================================
 * 功能：对非管理员，隐藏后台所有页面中评论者的邮箱和IP
 * ====================================================
 */
// 将原来的 admin_head-edit-comments.php 替换为 admin_head（后台全局生效）
add_action('admin_head', 'dn_hide_email_ip_for_non_admins');

function dn_hide_email_ip_for_non_admins() {
    // 权限检查：如果当前用户是管理员（拥有 manage_options 权限），则直接放行，不加载隐藏代码
    if ( current_user_can('manage_options') ) {
        return;
    }

    // 对于非管理员，向后台头部注入以下 CSS
    ?>
    <style>
        /* 1. 隐藏电脑宽屏模式下“作者”列里的 邮箱、IP 及 换行符 */
        table.comments td.column-author a[href^="mailto:"],
        table.comments td.column-author a[href*="edit-comments.php?s="],
        table.comments td.column-author br {
            display: none !important;
        }
        
        /* 2. 隐藏手机窄屏模式下（或悬浮展开时）内部包裹的 邮箱、IP 及 换行符 */
        .comment-author a[href^="mailto:"],
        .comment-author a[href*="edit-comments.php?s="],
        .comment-author br {
             display: none !important;
        }

        /* 3. 隐藏点击“快速编辑”时，表单区域暴露的评论者邮箱 */
        #the-comment-list .hidden .author-email,
        #the-comment-list .inline-edit-row .author-email {
            display: none !important;
        }
    </style>
    <?php
}



/**
 * ====================================================
 * 功能：媒体库和添加媒体弹窗默认选中“我的”（允许手动切换）
 * ====================================================
 */

// 1. 针对【后台媒体库页面】(upload.php) - 极速重定向法
add_action('load-upload.php', 'dn_upload_page_default_to_mine');
function dn_upload_page_default_to_mine() {
    // 核心逻辑：如果 URL 中完全没有 attachment-filter 参数，说明是刚刚点击左侧菜单进来的初始状态
    // 如果用户手动选择了“所有多媒体项目”并点击筛选，URL 会变成 attachment-filter= （空字符串），此时 isset 为 true，不会被拦截。
    if ( ! isset($_GET['attachment-filter']) ) {
        $url = admin_url('upload.php?attachment-filter=mine');
        
        // 如果当前是在网格视图中，需要保留视图模式参数
        if ( isset($_GET['mode']) ) {
            $url = add_query_arg('mode', sanitize_text_field($_GET['mode']), $url);
        }
        
        // 执行轻量级的内部重定向（在数据库执行任何繁重扫描前就跳走，性能极高）
        wp_redirect($url);
        exit;
    }
}

// 2. 针对【写文章页面的添加媒体弹窗】 - JS 界面与数据双重同步法
add_action('admin_footer', 'dn_media_modal_default_to_mine');
function dn_media_modal_default_to_mine() {
    // 仅在加载了媒体核心脚本的页面执行，不污染其他页面
    if ( ! did_action('wp_enqueue_media') ) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        if ( typeof wp !== 'undefined' && wp.media && wp.media.view && wp.media.view.AttachmentsBrowser ) {
            // 劫持 WordPress 原生的附件浏览器初始化流程
            var oldInitialize = wp.media.view.AttachmentsBrowser.prototype.initialize;
            wp.media.view.AttachmentsBrowser.prototype.initialize = function() {
                oldInitialize.apply(this, arguments);
                
                // 步骤A (控数据)：在弹窗发出第一个 AJAX 请求前，锁定参数为当前用户
                // 这能让数据库瞬间走索引出结果，避免首次打开弹窗时导致的全表扫描卡顿
                this.collection.props.set('author', <?php echo get_current_user_id(); ?>);
                
                // 步骤B (修 UI)：当弹窗的工具栏加载完毕后，强行修正下拉框的文本
                this.on('ready', function() {
                    var filters = this.toolbar.get('filters');
                    if (filters && filters.$el) {
                        // 1. 将下拉框的视觉选项卡到“我的” (value='mine')
                        filters.$el.val('mine');
                        
                        // 2. 将底层 Backbone 数据模型同步为 'mine' 
                        // {silent: true} 非常关键！它能防止 Backbone 发现下拉框变了而再去触发一次多余的 AJAX 查询
                        if (filters.model) {
                            filters.model.set('filter', 'mine', {silent: true});
                        }
                    }
                }, this);
            };
        }
    });
    </script>
    <?php
}

require_once __DIR__ . '/module-bookmark.php';

require_once __DIR__ . '/module-default-avatars.php';
