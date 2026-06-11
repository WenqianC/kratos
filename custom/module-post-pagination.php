<?php
/**
 * Multipage post and page navigation.
 */

if (!defined('ABSPATH')) {
    exit;
}

function dn_get_visible_post_pages($total_pages)
{
    $total_pages = max(1, (int) $total_pages);

    if ($total_pages <= 10) {
        return range(1, $total_pages);
    }

    return array_merge(range(1, 5), range($total_pages - 4, $total_pages));
}

function dn_get_hidden_post_pages($total_pages)
{
    $total_pages = max(1, (int) $total_pages);

    if ($total_pages <= 10) {
        return array();
    }

    return range(6, $total_pages - 5);
}

function dn_get_post_page_url($page_number, $post = null)
{
    global $wp_rewrite;

    $page_number = max(1, (int) $page_number);

    if (!$post) {
        $post = get_post();
    }

    if (!is_preview()) {
        $permalink = get_permalink($post);

        if ($page_number === 1) {
            return $permalink;
        }

        if (!get_option('permalink_structure') || in_array($post->post_status, array('draft', 'pending'), true)) {
            return add_query_arg('page', $page_number, $permalink);
        }

        if ('page' === get_option('show_on_front') && (int) get_option('page_on_front') === $post->ID) {
            return trailingslashit($permalink) . user_trailingslashit($wp_rewrite->pagination_base . '/' . $page_number, 'single_paged');
        }

        return trailingslashit($permalink) . user_trailingslashit($page_number, 'single_paged');
    }

    $preview_args = array();
    foreach (array('preview_id', 'preview_nonce') as $key) {
        if (isset($_GET[$key]) && is_scalar($_GET[$key])) {
            $preview_args[$key] = sanitize_text_field(wp_unslash($_GET[$key]));
        }
    }

    $preview_url = get_preview_post_link($post, $preview_args);
    $preview_url = remove_query_arg('page', $preview_url);

    if ($page_number > 1) {
        $preview_url = add_query_arg('page', $page_number, $preview_url);
    }

    return $preview_url;
}

function dn_render_post_page_link($page_number, $label, $post)
{
    printf(
        '<a href="%s" class="post-page-numbers"><span>%s</span></a>',
        esc_url(dn_get_post_page_url($page_number, $post)),
        esc_html($label)
    );
}

function dn_render_post_page_jump($hidden_pages, $current_page, $post)
{
    echo '<span class="dn-post-page-jump">';
    echo '<select data-dn-page-select aria-label="' . esc_attr__('选择页码', 'kratos') . '">';

    foreach ($hidden_pages as $page_number) {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_url(dn_get_post_page_url($page_number, $post)),
            $page_number === $current_page ? ' selected' : '',
            esc_html(sprintf(__('第 %d 页', 'kratos'), $page_number))
        );
    }

    echo '</select>';
    echo '<button type="button" data-dn-page-jump>' . esc_html__('跳转', 'kratos') . '</button>';
    echo '</span>';
}

function dn_render_post_pagination()
{
    global $multipage, $numpages, $page, $post;

    if (!$multipage || $numpages <= 1) {
        return;
    }

    $current_page = max(1, (int) $page);
    $visible_pages = dn_get_visible_post_pages($numpages);
    $hidden_pages = dn_get_hidden_post_pages($numpages);

    echo '<div class="paginations text-center">';

    if ($current_page > 1) {
        dn_render_post_page_link($current_page - 1, __('上一页', 'kratos'), $post);
    }

    $previous_visible_page = 0;
    foreach ($visible_pages as $page_number) {
        if ($hidden_pages && $previous_visible_page && $page_number > $previous_visible_page + 1) {
            dn_render_post_page_jump($hidden_pages, $current_page, $post);
        }

        if ($page_number === $current_page) {
            echo '<span class="post-page-numbers current" aria-current="page"><span>' . esc_html($page_number) . '</span></span>';
        } else {
            dn_render_post_page_link($page_number, $page_number, $post);
        }

        $previous_visible_page = $page_number;
    }

    if ($current_page < $numpages) {
        dn_render_post_page_link($current_page + 1, __('下一页', 'kratos'), $post);
    }

    echo '</div>';
}
