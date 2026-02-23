<?php
/**
 * BuddyPress - Members Home
 *
 * @since   1.0.0
 * @version 3.0.0
 */
?>

<?php 
 $dummyImg =  get_stylesheet_directory_uri() .'/assets/images/contact-banner.webp';
 $innerBanner = get_field('banner_image_via');


 if($innerBanner){
    $imgUrl   = $innerBanner['url'];
    $alt      = $innerBanner['alt'];
    $height   = $innerBanner['height'];;
    $width    = $innerBanner['width'];;
 }else{
    $imgUrl   = $dummyImg;
    $alt      = 'Inner banner';
    $height   = '390';
    $width    = '1920';
 }
?>
    
    
    <section class="inner-banner">
      <img src="<?php echo $imgUrl; ?>" alt="<?php echo $alt; ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" >
    </section>

<section class="members_profile_home pt_100 pt_100">
    <div class="container">

	<?php bp_nouveau_member_hook( 'before', 'home_content' ); ?>

	<div id="item-header" role="complementary" data-bp-item-id="<?php echo esc_attr( bp_displayed_user_id() ); ?>" data-bp-item-component="members" class="users-header single-headers">

		<?php bp_nouveau_member_header_template_part(); ?>

	</div><!-- #item-header -->

	<div class="bp-wrap">
		<?php if ( ! bp_nouveau_is_object_nav_in_sidebar() ) : ?>

			<?php bp_get_template_part( 'members/single/parts/item-nav' ); ?>

		<?php endif; ?>

		<div id="item-body" class="item-body">

			<?php bp_nouveau_member_template_part(); ?>

		</div><!-- #item-body -->
	</div><!-- // .bp-wrap -->

	<?php bp_nouveau_member_hook( 'after', 'home_content' ); ?>
    </div>
</section><!-- #members-dir-search -->
