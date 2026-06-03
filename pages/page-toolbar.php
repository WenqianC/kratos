<?php
/**
 * 文章工具栏
 * @author Seaton Jiang <seaton@vtrois.com>
 * @license MIT License
 * @version 2020.04.12
 */
?>
<div class="toolbar clearfix">
	<div class="meta float-md-left">
	    <img src="<?php echo esc_url(get_avatar_url(get_the_author_meta('ID'))); ?>">
	    <p class="name">
	        <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>" style="color: #0073aa; text-decoration: none;">
	            <?php echo esc_html(get_the_author()); ?>
	        </a>
	    </p>
	    <p class="motto mb-0"><?php echo esc_html(get_the_author_meta('description')); ?></p>
	</div>

	<div class="share float-md-right text-center">
        <?php if(kratos_option('g_donate',false)){ ?>
		    <a href="javascript:;" id="donate" class="btn btn-donate mr-3" role="button"><i class="kicon i-donate"></i> <?php _e('打赏','kratos'); ?></a>
        <?php } ?>
		    <a href="javascript:;" id="thumbs" data-action="love" data-id="<?php the_ID(); ?>" role="button" class="btn btn-thumbs <?php if(isset($_COOKIE['love_'.$post->ID])) echo 'done'; ?>" ><i class="kicon i-like"></i><span class="ml-1"><?php _e('点赞','kratos'); ?></span></a>
	</div>
</div>
