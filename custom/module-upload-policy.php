<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('ALLOW_UNFILTERED_UPLOADS')) {
    define('ALLOW_UNFILTERED_UPLOADS', true);
}

add_filter('big_image_size_threshold', '__return_false');
add_filter('wp_image_maybe_exif_rotate', '__return_false');

function remove_image_srcset($sources)
{
    return false;
}
add_filter('wp_calculate_image_srcset', 'remove_image_srcset');

add_filter('wp_handle_upload_prefilter', 'dn_block_scriptable_upload_types');
function dn_block_scriptable_upload_types($file) {
    $blocked_extensions = array('svg', 'svgz', 'html', 'htm', 'xml', 'xhtml');
    $extension = strtolower(pathinfo(isset($file['name']) ? $file['name'] : '', PATHINFO_EXTENSION));

    if (in_array($extension, $blocked_extensions, true)) {
        $file['error'] = '出于安全考虑，本站禁止上传 SVG、HTML、XML 等可能包含脚本的文件。';
    }

    return $file;
}
