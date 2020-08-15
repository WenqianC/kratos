<?php
/**
 * 文章短代码
 * @author Seaton Jiang <seaton@vtrois.com>
 * @license MIT License
 * @version 2020.06.25
 */

function h2title($atts, $content = null, $code = "")
{
    $return = '<h2 class="title">';
    $return .= $content;
    $return .= '</h2>';
    return $return;
}
add_shortcode('h2title', 'h2title');

function wymusic($atts, $content = null, $code = "")
{
    $return = '<div class="mb-3"><iframe style="width:100%" frameborder="no" border="0" marginwidth="0" marginheight="0" height=86 src="//music.163.com/outchain/player?type=2&id=';
    $return .= $content;
    $return .= '&auto=' . kratos_option('g_163mic', false) . '&height=66"></iframe></div>';
    return $return;
}
add_shortcode('music', 'wymusic');

function bdbtn($atts, $content = null, $code = "")
{
    $return = '<a class="downbtn" href="';
    $return .= $content;
    $return .= '" target="_blank"><i class="kicon i-download mr-1"></i>立即下载</a>';
    return $return;
}
add_shortcode('bdbtn', 'bdbtn');

function nrmark($atts, $content = null, $code = "")
{
    $return = '<mark>';
    $return .= $content;
    $return .= '</mark>';
    return $return;
}
add_shortcode('mark', 'nrmark');

function bilibili($atts, $content = null, $code = "")
{
    extract(shortcode_atts(array("cid" => 'cid'), $atts));
    $return = '<div class="video-container"><iframe src="//player.bilibili.com/player.html?cid=';
    $return .= $cid;
    $return .= '&aid=';
    $return .= $content;
    $return .= '&page=1" scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true"> </iframe></div>';
    return $return;
}
add_shortcode('bilibili', 'bilibili');

function accordion($atts, $content=null, $code=""){
    extract(shortcode_atts(array("title"=>__('标题内容','kratos')),$atts));
    $return = '<div class="accordion"><div class="acheader"><div class="icon"><i class="kicon i-plus"></i></div><span>';
    $return .= $title;
    $return .= '</span></div><div class="contents"><div class="inner">';
    $return .= do_shortcode($content);
    $return .= '</div></div></div>';
    return $return;
}
add_shortcode('accordion','accordion');

add_action('init', 'more_button');
function more_button()
{
    if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
        return;
    }
    if (get_user_option('rich_editing') == 'true') {
        add_filter('mce_external_plugins', 'add_plugin');
        add_filter('mce_buttons', 'register_button');
    }
}

function add_more_buttons($buttons) {
    $buttons[] = 'hr';
    $buttons[] = 'wp_page';
//    $buttons[] = 'fontsizeselect';
//    $buttons[] = 'styleselect';
return $buttons;
}
add_filter("mce_buttons", "add_more_buttons");

function register_button($buttons)
{
    array_push($buttons, " ", "h2title");
    array_push($buttons, " ", "mark");
    array_push($buttons, " ", "bdbtn");
    array_push($buttons, " ", "accordion");
    array_push($buttons, " ", "music");
    array_push($buttons, " ", "bilibili");
    return $buttons;
}

function add_plugin($plugin_array)
{
    $plugin_array['h2title'] = ASSET_PATH . '/assets/js/buttons/more.js';
    $plugin_array['mark'] = ASSET_PATH . '/assets/js/buttons/more.js';
    $plugin_array['bdbtn'] = ASSET_PATH . '/assets/js/buttons/more.js';
    $plugin_array['accordion'] = ASSET_PATH . '/assets/js/buttons/more.js';
    $plugin_array['music'] = ASSET_PATH . '/assets/js/buttons/more.js';
    $plugin_array['bilibili'] = ASSET_PATH . '/assets/js/buttons/more.js';
    return $plugin_array;
}
