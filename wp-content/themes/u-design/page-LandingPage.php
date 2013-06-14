<?php
/**
 * @package WordPress
 * @subpackage U-Design
 */
/**
 * Template Name: Landing Page
 */


 ?>
<?php get_header('2'); ?>
<div id="content-container" class="container_24">
    <div id="main-content" class="grid_24">
	<div class="main-content-padding">
<?php       do_action('udesign_above_page_content'); ?>
<?php	    if (have_posts()) : while (have_posts()) : the_post(); ?>
		<div class="post" id="post-<?php the_ID(); ?>">
		    <div class="entry">
<?php			the_content(__('<p class="serif">Read the rest of this entry &raquo;</p>', 'udesign'));
			wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
		    </div>
		</div>
<?php		( $udesign_options['show_comments_on_pages'] == 'yes' ) ? comments_template() : ''; ?>
<?php	    endwhile; endif; ?>
	    
	    <div class="clear"></div>
<?php	    edit_post_link(esc_html__('Edit this entry.', 'udesign'), '<p class="editLink">', '</p>'); ?>

	</div><!-- end main-content-padding -->
    </div><!-- end main-content -->
</div><!-- end content-container -->

<div class="clear"></div>

<div id="theDark"></div>
<span class="mainarticle">
</span>

<div id="home-page-content">
	<div id="content-container" class="container_24">
		<div id="main-content" class="grid_24">
		    <div class="main-content-padding">
				<div id="cont-box-1" class="full_width home-cont-box">
					<div class="column-content-wrapper">
						<div class="cont_col_1 widget_text substitute_widget_class">	
							<div class="textwidget">
								<div class="one_fourth">
									<?php echo(types_render_field( "landing-page-image", array( "alt" => "Product image", "align" => "center", "proportional" => "true" ) ));?>
										<div style="float:left;text-align:center;">
											<h3 style="margin:0 auto;padding-bottom:0;">
												<?php echo(types_render_field( "landing-page-heading1", array( "raw" => "false" ) ));?>
											</h3>
											<p style="float:left;text-align:left;"><?php echo(types_render_field( "landing-page-paragraph", array( "raw" => "false"  ) ));?>
												<?php echo(types_render_field( "read-more", array( "raw" => "false" ) ));?>
											</p>
										</div>
								</div>
								
								<div class="one_fourth">
									<?php echo(types_render_field( "landing-page-image2", array( "alt" => "Product image", "align" => "center", "proportional" => "true" ) ));?>										
										<div style="float:left;text-align:center;">
											<h3 style="margin:0 auto;padding-bottom:0;">
												<?php echo(types_render_field( "landing-page-heading2", array( "raw" => "false" ) ));?>
											</h3>
											<p style="float:left;text-align:left;"><?php echo(types_render_field( "landing-page-paragraph2", array( "raw" => "false"  ) ));?>
												<?php echo(types_render_field( "read-more2", array( "raw" => "false" ) ));?>											</p>
										</div>
								</div>
								
								<div class="one_fourth">
									<?php echo(types_render_field( "landing-page-image3", array( "alt" => "Product image", "align" => "center", "proportional" => "true" ) ));?>										
										<div style="float:left;text-align:center;">
											<h3 style="margin:0 auto;padding-bottom:0;">
												<?php echo(types_render_field( "landing-page-heading3", array( "raw" => "false" ) ));?>
											</h3>
											<p style="float:left;text-align:left;"><?php echo(types_render_field( "landing-page-paragraph3", array( "raw" => "false"  ) ));?>
												<?php echo(types_render_field( "read-more3", array( "raw" => "false" ) ));?>											</p>
										</div>
									</div>

									<div class="one_fourth last_column">
										<?php echo(types_render_field( "landing-page-image4", array( "alt" => "Product image", "align" => "center", "proportional" => "true" ) ));?>											<div style="float:left;text-align:center;">
												<h3 style="margin:0 auto;padding-bottom:0;">
													<?php echo(types_render_field( "landing-page-heading4", array( "raw" => "false" ) ));?>
												</h3>
												<p style="float:left;text-align:left;"><?php echo(types_render_field( "landing-page-paragraph4", array( "raw" => "false"  ) ));?>
													<?php echo(types_render_field( "read-more4", array( "raw" => "false" ) ));?>												</p>
											</div>
									</div>
 
								</div>
		</div></div></div><!-- end cont-box-1 -->		      </div>
		      <!-- end main-content-padding -->
		  </div>
		  <!-- end main-content -->
	    </div>
	    <!-- end content-container -->

	    <div class="clear"></div>





</div>

<?php


get_footer();

    

