  <?php 

  $homePropertyTitle = get_field('tittle_hrb');
  $homePC = get_field('property_types');

  ?>   
     
    
     <section class="explore_tranding_room">
        <div class="container">
          <div class="sec_head">
            <?php if($homePropertyTitle){ ?>
            <h2 class="sec_hdng mb-0">
              <?php echo $homePropertyTitle; ?>
            </h2>
            <?php } ?>
            <?php 
            $link = get_field('cta_hrb');
            if( $link ): 
                $link_url = $link['url'];
                $link_title = $link['title'];
                $link_target = $link['target'] ? $link['target'] : '_self';
                ?>
              <a href="<?php echo $link_urll ?>" target="<?php echo $link_target;  ?>" class="primary_btn icon arrow"><?php echo $link_title; ?></a>
            <?php endif; ?>
            
          </div>

           <?php 


                    $args = [
                            'post_type'      => 'property',
                            'posts_per_page' => 10,
                            'orderby'        => 'ID',
                            'order'          => 'ASC',
                        ];
                                          


                     if ( ! empty( $homePC ) ) {
                        $args['tax_query'] = [
                            [
                                'taxonomy' => 'property-type',
                                'field'    => 'term_id',
                                'terms'    => $homePC,
                                'operator' => 'IN',
                            ]
                        ];
                    }

                      $product_query = new WP_Query($args);

                if ( $product_query->have_posts() ) { ?>


                  <div
                    class="tab-pane fade show active"

                    role="tabpanel"
                    aria-labelledby="pills-all-tab"
                  >
                    <div class="row gy-md-4 gy-3 property-slider">
                
                      <?php while ( $product_query->have_posts() ) :
                    
                          $product_query->the_post();

                           get_template_part( 'template-part/poperty-loop' );
                  endwhile; ?>
            </div>
        <?php } ?>
    </section>