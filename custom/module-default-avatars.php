<?php
/**
 * ====================================================
 * 模块：固定预设头像列表 & 智能聚合与硬性限制版
 * 描述：支持显示角色名并自动排序，双重防线限制资料页 100KB 上传
 * ====================================================
 */

if (!defined('ABSPATH')) exit;

// 1. 预设头像列表（支持自动按角色名拼音/字节排序聚合）
function dn_get_defined_avatars() {
    $avatars = [
        223964 => ['url' => 'https://cdn.dnforlife.com/2026/05/魅上照_200_200-2.png', 'name' => '魅上照'],
        223965 => ['url' => 'https://cdn.dnforlife.com/2026/05/琳达_200_200-2.png',   'name' => '琳达'],
        223966 => ['url' => 'https://cdn.dnforlife.com/2026/05/松田_200_200-2.png',   'name' => '松田'],
        223967 => ['url' => 'https://cdn.dnforlife.com/2026/05/弥海砂_200_200-2.png', 'name' => '弥海砂'],
        223968 => ['url' => 'https://cdn.dnforlife.com/2026/05/夜神月_200_200-2.png', 'name' => '夜神月'],
        223969 => ['url' => 'https://cdn.dnforlife.com/2026/05/N_200_200-2.png',      'name' => '尼亚'],
        223970 => ['url' => 'https://cdn.dnforlife.com/2026/05/M2_200_200-1.png',      'name' => '玛特'],
        223971 => ['url' => 'https://cdn.dnforlife.com/2026/05/M_200_200-1.png',      'name' => '梅洛'],
        223972 => ['url' => 'https://cdn.dnforlife.com/2026/05/L_200_200-1.png',      'name' => 'L'],
        223973 => ['url' => 'https://cdn.dnforlife.com/2026/05/BB_200_200-1.png',      'name' => 'BB'],
        224557 => ['url' => 'https://cdn.dnforlife.com/2026/05/海砂动图_200_200.gif', 'name' => '弥海砂'],
        224558 => ['url' => 'https://cdn.dnforlife.com/2026/05/海砂邮票_200_200.jpg', 'name' => '弥海砂'],
        224559 => ['url' => 'https://cdn.dnforlife.com/2026/05/琳达动图_200_200.gif', 'name' => '琳达'],
        224560 => ['url' => 'https://cdn.dnforlife.com/2026/05/琳达邮票_200_200.jpg', 'name' => '琳达'],
        224561 => ['url' => 'https://cdn.dnforlife.com/2026/05/玛特动图_200_200.gif', 'name' => '玛特'],
        224562 => ['url' => 'https://cdn.dnforlife.com/2026/05/玛特邮票_200_200.jpg', 'name' => '玛特'],
        224563 => ['url' => 'https://cdn.dnforlife.com/2026/05/梅洛动图_200_200.gif', 'name' => '梅洛'],
        224564 => ['url' => 'https://cdn.dnforlife.com/2026/05/梅洛邮票_200_200.png', 'name' => '梅洛'],
        224565 => ['url' => 'https://cdn.dnforlife.com/2026/05/魅上照正动图_200_200.gif', 'name' => '魅上照'],
        224566 => ['url' => 'https://cdn.dnforlife.com/2026/05/魅上照正邮票_200_200.jpg', 'name' => '魅上照'],
        224567 => ['url' => 'https://cdn.dnforlife.com/2026/05/尼亚动图_200_200.gif', 'name' => '尼亚'],
        224568 => ['url' => 'https://cdn.dnforlife.com/2026/05/尼亚邮票_200_200.jpg', 'name' => '尼亚'],
        224569 => ['url' => 'https://cdn.dnforlife.com/2026/05/松田动图_200_200.gif', 'name' => '松田'],
        224570 => ['url' => 'https://cdn.dnforlife.com/2026/05/松田邮票_200_200.jpg', 'name' => '松田'],
        224571 => ['url' => 'https://cdn.dnforlife.com/2026/05/夜神月动图_200_200.gif', 'name' => '夜神月'],  
        224572 => ['url' => 'https://cdn.dnforlife.com/2026/05/夜神月邮票_200_200.png', 'name' => '夜神月'],  
        224573 => ['url' => 'https://cdn.dnforlife.com/2026/05/BB动图_200_200.gif', 'name' => 'BB'],  
        224574 => ['url' => 'https://cdn.dnforlife.com/2026/05/BB邮票_200_200.jpg', 'name' => 'BB'],  
        224575 => ['url' => 'https://cdn.dnforlife.com/2026/05/L动图_200_200.gif', 'name' => 'L'],   
        224576 => ['url' => 'https://cdn.dnforlife.com/2026/05/L邮票_200_200.jpg', 'name' => 'L'],
        226334 => ['url' => 'https://cdn.dnforlife.com/2026/05/魅上照透明底_200_200.png', 'name' => '魅上照'],
        226335 => ['url' => 'https://cdn.dnforlife.com/2026/05/琳达透明底_200_200.png', 'name' => '琳达'],
        226336 => ['url' => 'https://cdn.dnforlife.com/2026/05/玛特透明底_200_200.png', 'name' => '玛特'],
        226337 => ['url' => 'https://cdn.dnforlife.com/2026/05/海砂透明底_200_200.png', 'name' => '弥海砂'],
        226338 => ['url' => 'https://cdn.dnforlife.com/2026/05/梅洛透明底_200_200.png', 'name' => '梅洛'],
        226339 => ['url' => 'https://cdn.dnforlife.com/2026/05/松田透明底_200_200.png', 'name' => '松田'],
        226340 => ['url' => 'https://cdn.dnforlife.com/2026/05/尼亚透明底_200_200.png', 'name' => '尼亚'],
        226341 => ['url' => 'https://cdn.dnforlife.com/2026/05/夜神月透明底_200_200.png', 'name' => '夜神月'],
        226342 => ['url' => 'https://cdn.dnforlife.com/2026/05/L透明底_200_200.png', 'name' => 'L'],
        226343 => ['url' => 'https://cdn.dnforlife.com/2026/05/BB透明底_200_200.png', 'name' => 'BB'],
        226855 => ['url' => 'https://cdn.dnforlife.com/2026/05/基拉组动图_200_200.gif', 'name' => '基拉组']
        // 随时在此处追加新角色
    ];

    // 按名字排序，将相同角色的头像完美聚合在一起
    uasort($avatars, function($a, $b) {
        return $a['name'] <=> $b['name'];
    });

    return $avatars;
}

// 2. 渲染 UI 与 JS 劫持联动脚本
add_action('show_user_profile', 'dn_render_fixed_avatars_ui');
add_action('edit_user_profile', 'dn_render_fixed_avatars_ui');
function dn_render_fixed_avatars_ui($user) {
    $avatars = dn_get_defined_avatars();
    if (empty($avatars)) return;

    // 获取当前用户存在数据库中的头像 ID
    $current_avatar_id = get_user_meta($user->ID, 'wp_user_avatar', true);
    ?>
    <div id="dn-preset-avatar-wrapper" style="display:none;">
        <p class="description" style="margin: 15px 0 8px 0; font-weight: bold; color: #23282d;">一键选择默认预设头像：</p>
        
        <div style="display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 10px;">
            <label class="dn-preset-label" title="不使用预设">
                <input type="radio" name="dn_preset_avatar_radio" value="" <?php checked(empty($current_avatar_id) || !array_key_exists($current_avatar_id, $avatars)); ?>>
                <div class="dn-preset-none">无</div>
                <span class="dn-preset-name">自定义</span>
            </label>

            <?php foreach ($avatars as $id => $data) : ?>
                <label class="dn-preset-label">
                    <input type="radio" name="dn_preset_avatar_radio" value="<?php echo esc_attr($id); ?>" data-src="<?php echo esc_url($data['url']); ?>" <?php checked((int)$current_avatar_id, $id); ?>>
                    <img src="<?php echo esc_url($data['url']); ?>" alt="<?php echo esc_attr($data['name']); ?>">
                    <span class="dn-preset-name"><?php echo esc_html($data['name']); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        
        <style>
            .dn-preset-label { cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 6px; width: 60px; }
            .dn-preset-label input { display: none; }
            .dn-preset-label img { width: 56px; height: 56px; border-radius: 50%; border: 3px solid transparent; transition: 0.2s; box-sizing: border-box; }
            .dn-preset-none { width: 56px; height: 56px; border-radius: 50%; border: 2px dashed #b5bfc9; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #646970; background: #fafafa; transition: 0.2s; box-sizing: border-box; }
            .dn-preset-name { font-size: 12px; color: #646970; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; transition: 0.2s; }
            
            .dn-preset-label input:checked + img { border-color: #2271b1; transform: scale(1.08); box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
            .dn-preset-label input:checked + .dn-preset-none { border-color: #2271b1; border-style: solid; color: #2271b1; font-weight: bold; background: #fff; }
            .dn-preset-label input:checked ~ .dn-preset-name { color: #2271b1; font-weight: bold; }
            .dn-preset-label:hover img, .dn-preset-label:hover .dn-preset-none { transform: scale(1.05); }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var $pluginContainer = $('.wp-user-avatar-container');
            var $pluginPreview = $('#wpua-preview-existing img, #wpua-thumbnail-existing img');
            var $nativeAvatar = $('.form-table img.avatar, #your-profile img.avatar');
            var $pluginInput = $('input[name="wp-user-avatar"]'); // 核心：这是插件保存 ID 用的隐藏域
            
            var originalAvatarSrc = $pluginPreview.length ? $pluginPreview.eq(0).attr('src') : ($nativeAvatar.length ? $nativeAvatar.eq(0).attr('src') : '');
            var originalAvatarId = $pluginInput.val();

            if ($pluginContainer.length) {
                $('#dn-preset-avatar-wrapper').appendTo($pluginContainer).show();
            } else if ($pluginInput.length) {
                $('#dn-preset-avatar-wrapper').appendTo($pluginInput.closest('td')).show();
            } else {
                $('#dn-preset-avatar-wrapper').removeClass('form-table').appendTo('form#your-profile').show().before('<h3>可选预设头像</h3>');
            }

            // 用户自行上传后，重置预设面板为"自定义"
            $pluginInput.on('change', function() {
                var currentVal = $(this).val();
                var presetIds = <?php echo json_encode(array_keys($avatars)); ?>;
                if (presetIds.indexOf(parseInt(currentVal, 10)) === -1) {
                    $('input[name="dn_preset_avatar_radio"][value=""]').prop('checked', true);
                }
            });

            // 点击预设，强行把媒体库 ID 塞给源插件，借刀杀人实现 0 代码后端保存
            $('input[name="dn_preset_avatar_radio"]').on('change', function() {
                var selectedId = $(this).val();
                if (selectedId) {
                    var newSrc = $(this).data('src');
                    $pluginPreview.attr('src', newSrc).attr('srcset', '');
                    $nativeAvatar.attr('src', newSrc).attr('srcset', '');
                    $('#wp-admin-bar-my-account img.avatar').attr('src', newSrc).attr('srcset', '');
                    $pluginInput.val(selectedId);
                } else {
                    $pluginPreview.attr('src', originalAvatarSrc).attr('srcset', '');
                    $nativeAvatar.attr('src', originalAvatarSrc).attr('srcset', '');
                    $('#wp-admin-bar-my-account img.avatar').attr('src', originalAvatarSrc).attr('srcset', '');
                    $pluginInput.val(originalAvatarId);
                }
            });
        });
        </script>
    </div>
    <?php
}

// =========================================================================
// 🚀 核心限制：双重防线，仅在个人资料页卡死 100KB 上传上限
// =========================================================================

// 定义一个统一常量，方便以后随时修改体积限制（100KB = 100 * 1024 字节）
define('DN_AVATAR_MAX_BYTES', 100 * 1024);

// 1. 前端 UI 限制：动态修改弹窗里的文字，并限制正常浏览器上传
add_filter('upload_size_limit', 'dn_restrict_avatar_upload_size');
function dn_restrict_avatar_upload_size($size) {
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        // 只在个人资料页起效，防止误伤发文章配图
        if (strpos($referer, 'profile.php') !== false || strpos($referer, 'user-edit.php') !== false) {
            return DN_AVATAR_MAX_BYTES; 
        }
    }
    return $size;
}

// 2. 后端物理防线：防止通过脚本绕过前端直传大图
add_filter('wp_handle_upload_prefilter', 'dn_validate_avatar_upload_weight');
function dn_validate_avatar_upload_weight($file) {
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        if (strpos($referer, 'profile.php') !== false || strpos($referer, 'user-edit.php') !== false) {
            
            // 只要文件超标，直接往 $file['error'] 里塞报错信息，系统会自动中断上传
            if (isset($file['size']) && $file['size'] > DN_AVATAR_MAX_BYTES) {
                $file['error'] = '头像上传界面上限100k';
            }
        }
    }
    return $file;
}