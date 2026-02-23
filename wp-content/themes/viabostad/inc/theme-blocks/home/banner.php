<?php 

$homeBannerTitle = get_field('title_hb');
$homeBannerDescription = get_field('description_hb');
$homeBannerDeskImg = get_field('desktop_banner_hb');
$homeBannerMobImg = get_field('mobile_banner_hb');
$homeBannerSearch = get_field('form_shortcode_hb');

?>
  <section class="banner_section">
        <div class="inner_wrapper">

         <img
            src="<?php echo $homeBannerDeskImg['url'];?>" 
            alt="<?php echo $homeBannerDeskImg['alt'];?>"
            height="<?php echo $homeBannerDeskImg['height'];?>"
            width="<?php echo $homeBannerDeskImg['width'];?>"
            class="d-sm-block d-none"
            loading="eager"
          />
          <img
            src="<?php echo $homeBannerMobImg['url'];?>" 
            alt="<?php echo $homeBannerMobImg['alt'];?>"
            height="<?php echo $homeBannerMobImg['height'];?>"
            width="<?php echo $homeBannerMobImg['width'];?>"
            class="d-sm-none d-block"
            loading="eager"
          />
        
          <div class="text_wrapper">
            <?php if($homeBannerTitle){ ?>
              <h1 class="main_heading"><?php echo $homeBannerTitle; ?></h1>
            <?php } ?>
            <?php if($homeBannerDescription){?>
               <p> <?php echo $homeBannerDescription; ?> </p>
            <?php } ?>
          </div>
        </div>
        <?php if($homeBannerSearch){ ?>
          <?php echo do_shortcode($homeBannerSearch); ?>
        <?php } ?>
      </section>