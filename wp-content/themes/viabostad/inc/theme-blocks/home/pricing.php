<?php 
$secTitle = get_field('section_title_op');

?>


<section class="our_pricing">
        <div class="container">
          <?php if($secTitle){ ?>
            <h2 class="sec_hdng text-center pb-md-3 pb-2">
              <?php echo $secTitle; ?>
            </h2>
            <?php } ?>

            <?php if(have_rows('pricing_hp')){ ?>
          <div class="row gy-4 align-items-center">


          <?php while(have_rows('pricing_hp')){ the_row(); 
        
            $title = get_sub_field('title_hp');
            $price = get_sub_field('price_hp');
            $description = get_sub_field('description_hp');
            $features= get_sub_field('features_hp');
            $cta= get_sub_field('cta_hp');
            $isp= get_sub_field('is_popular_hp');
        
          ?>

            <div class="col-lg-4">
              <div class="inner_wrapper">
                <?php if($isp){ ?>
                  <span class="top_tag"> Most Popular </span>
                <?php } ?>
                <div class="title_wrap">
                  <?php if($title){ ?>
                    <h3 class="title"><?php echo $title; ?></h3>
                  <?php } ?>
                  <?php if($price){ ?>
                    <h4 class="price"><?php echo $price; ?></h4>
                  <?php } ?>
                </div>
                <?php if($description){?>
                <p>
                  <?php echo $description; ?>
                <?php  } ?>
                </p>
               <?php if($features){ echo $features;  } ?>

               <?php 
                $link = get_sub_field('cta_hp');
                if( $link ): 
                    $link_url = $link['url'];
                    $link_title = $link['title'];
                    $link_target = $link['target'] ? $link['target'] : '_self';
                    ?>
                  
                <?php endif; ?>
                
                <a href="<?php echo $link_url; ?>" target="<?php echo $link_target; ?>" class="primary_btn icon arrow"><?php echo $link_title; ?></a>
              </div>
            </div>
            <?php } ?>
          </div>
          <?php } ?>
        </div>
      </section>