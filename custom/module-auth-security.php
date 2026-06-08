<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('register_form', 'add_security_question');
function add_security_question()
{ ?>
    <p>
        <label for="proof">验证问答：本站作品有个暴露m的冷门双马尾人物，她的职业是？英文全小写。2026.6.1起禁止公开在外发布答案，禁止公开发帖提供教学私聊答案。被发现停止新用户注册1个月。<br />
            <input type="text" name="proof" id="proof" class="input" size="25" tabindex="20" /></label>
    </p>
<?php }

add_action('lostpassword_form', 'add_security_question2');
function add_security_question2()
{ ?>
    <p>
        <label for="proof">验证问答：本站作品主角（两人都可以）的生日是什么？四位数字mmdd<br />
            <input type="text" name="proof" id="proof" class="input" size="25" tabindex="20" /></label>
    </p>
<?php }

add_action('register_post', 'add_security_question_validate', 10, 3);
function add_security_question_validate($sanitized_user_login, $user_email, $errors)
{
    if (! isset($_POST['proof']) || empty($_POST['proof'])) {
        $errors->add('proofempty', '<strong>错误</strong>: 您必须回答验证问题。');
    } elseif (strtolower($_POST['proof']) != 'artist' && strtolower($_POST['proof']) != '画家') {
        $errors->add('prooffail', '<strong>错误</strong>: 验证问题回答错误。');
    }
}

add_action('lostpassword_post', 'add_lostpassword_security_question_validate');
function add_lostpassword_security_question_validate($errors)
{
    if ( is_admin() ) {
        return;
    }

    if (! isset($_POST['proof']) || empty($_POST['proof'])) {
        $errors->add('proofempty', '<strong>错误</strong>: 您必须回答验证问题。');
    } elseif (strtolower($_POST['proof']) != '0228' && strtolower($_POST['proof']) != '1031') {
        $errors->add('prooffail', '<strong>错误</strong>: 验证问题回答错误。');
    }
}

add_filter( 'wp_mail', 'append_custom_notice_to_all_emails', 9999 );
function append_custom_notice_to_all_emails( $args ) {
    $custom_text = "\n\n---------------------------------\n温馨提示：请将链接【完整】复制进浏览器打开。不要在邮箱app中打开。";
    $custom_html = '<br><br><hr style="border:none; border-top:1px dashed #ccc; margin-top:20px;"><p style="color:#888; font-size:13px;"><strong>温馨提示：</strong>请将链接【完整】复制进浏览器打开。不要在邮箱app中打开。</p>';

    $is_html = false;
    if ( isset( $args['headers'] ) ) {
        $headers = is_array( $args['headers'] ) ? implode( "\n", $args['headers'] ) : $args['headers'];
        if ( stripos( $headers, 'text/html' ) !== false ) {
            $is_html = true;
        }
    }

    if ( $is_html ) {
        if ( stripos( $args['message'], '</body>' ) !== false ) {
            $args['message'] = str_ireplace( '</body>', $custom_html . "\n</body>", $args['message'] );
        } else {
            $args['message'] .= $custom_html;
        }
    } else {
        $args['message'] .= $custom_text;
    }

    return $args;
}

add_filter( 'retrieve_password_message', 'force_add_ip_to_reset_email', 9999, 4 );
function force_add_ip_to_reset_email( $message, $key, $user_login, $user_data ) {
    $user_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '未知 IP';
    $ip_notice = "\n\n此密码重置请求来自 IP 地址 " . $user_ip . "。";

    if ( strpos( $message, 'IP 地址' ) === false ) {
        $message .= $ip_notice;
    }

    return $message;
}

add_filter('registration_errors', 'restrict_sensitive_usernames_with_msg', 999, 3);
function restrict_sensitive_usernames_with_msg($errors, $sanitized_user_login, $user_email) {
    $forbidden_keywords = array('admin', 'user', 'root', 'system', 'support', 'manager', 'webmaster', 'dnforlife', 'allfordn');
    $username_lower = strtolower($sanitized_user_login);

    foreach ($forbidden_keywords as $keyword) {
        if (strpos($username_lower, $keyword) !== false) {
            $errors->add(
                'username_forbidden',
                '<strong>错误</strong>：您输入的用户名包含系统保留或不支持的关键词，请更换其他名称。'
            );
            break;
        }
    }

    return $errors;
}

add_filter('allow_password_reset', 'disable_admin_password_reset', 999, 2);
function disable_admin_password_reset($allow, $user_id) {
    $user = get_userdata($user_id);

    if ($user && (in_array('administrator', (array) $user->roles) || $user->user_login === 'admin000')) {
        return new WP_Error('no_password_reset', '出于安全考虑，管理员账号已禁用特定账号的前台密码重置功能。');
    }
    return $allow;
}
