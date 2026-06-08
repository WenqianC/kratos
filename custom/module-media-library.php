<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('load-upload.php', 'dn_upload_page_default_to_mine');
function dn_upload_page_default_to_mine() {
    if ( ! isset($_GET['attachment-filter']) ) {
        $url = admin_url('upload.php?attachment-filter=mine');

        if ( isset($_GET['mode']) ) {
            $url = add_query_arg('mode', sanitize_text_field($_GET['mode']), $url);
        }

        wp_redirect($url);
        exit;
    }
}

add_action('admin_footer', 'dn_media_modal_default_to_mine');
function dn_media_modal_default_to_mine() {
    if ( ! did_action('wp_enqueue_media') ) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        if ( typeof wp !== 'undefined' && wp.media && wp.media.view && wp.media.view.AttachmentsBrowser ) {
            var oldInitialize = wp.media.view.AttachmentsBrowser.prototype.initialize;
            wp.media.view.AttachmentsBrowser.prototype.initialize = function() {
                oldInitialize.apply(this, arguments);

                this.collection.props.set('author', <?php echo get_current_user_id(); ?>);

                this.on('ready', function() {
                    var filters = this.toolbar.get('filters');
                    if (filters && filters.$el) {
                        filters.$el.val('mine');

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
