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