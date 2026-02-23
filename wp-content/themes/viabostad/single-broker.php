<?php
get_header(); 

    if(have_posts()) :
        while (have_posts() ) : the_post(); 

          $author = get_the_author_ID();
          $full_name  = false;
          $post_id  = get_the_ID(); // Your post ID
          $agency_id = get_user_meta($author, 'agency_id_viabostad', true);
          $get_agency = get_user_by('id', $agency_id);
          $full_name = trim( $get_agency->first_name . ' ' . $get_agency->last_name );


            $dummyImg =  get_stylesheet_directory_uri() .'/assets/images/contact-banner.webp';
            $innerBanner = get_field('broker_details_banner', 'option');


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
                
    

  <main>

   <section class="inner-banner">
      <img src="<?php echo $imgUrl; ?>" alt="<?php echo $alt; ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" >
    </section>


    <section class="broker-details">
      <div class="container">
        <div class="row gy-md-0 gy-4">
          <div class="col-md-4 lft-sc">
            <?php 
             $thumb_id = get_post_thumbnail_id();
            $img_src  = $thumb_id ? wp_get_attachment_image_src($thumb_id, 'full') : '';
            $img_alt  = $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '';
            ?>
              <?php if ( $img_src ) : ?>
                <img src="<?php echo esc_url($img_src[0]); ?>"
                     width="<?php echo esc_attr($img_src[1]); ?>"
                     height="<?php echo esc_attr($img_src[2]); ?>"
                     alt="<?php echo esc_attr($img_alt ?: get_the_title()); ?>">
              <?php endif; ?>
          </div>
          <div class="col-md-8 rgt-sc">
            <?php if( $full_name){?>
            <div class="ad-pf">
               
                <?php echo $full_name; ?>     
            
            </div>
            <?php } ?>
            <h2><?php the_title(); ?></h2>
               <?php the_content(); ?>
                <a href="#" class="primary_btn icon arrow"  data-bs-toggle="modal" data-bs-target="#agentModal">Contact Broker</a>
            </p>
          </div>
        </div>
      </div>
    </section>

 <?php 

    $product_query = new WP_Query([

      'post_type'      => 'property',
      'posts_per_page' => -1,
      'orderby' => 'id',
      'order' => 'ASC',
      'author' => $author
  ]);


if ( $product_query->have_posts() ) { ?>

    <section class="explore_tranding_room bd-card-sc">
      <div class="container">
        <div class="sec_head">
          <h2 class="sec_hdng mb-0">Properties Managed By <span><?php the_title(); ?></span></h2>

        </div>
        <div class="row gy-md-4 gy-3 property-slider">

            <?php while ( $product_query->have_posts() ) :
                  
                  $product_query->the_post();

                    get_template_part( 'template-part/poperty-loop' );
                
                endwhile; 
            ?>
        </div>
      </div>
    </section>
  <?php } else { ?>
  
      <section class="explore_tranding_room bd-card-sc">
        <div class="container">
          <div class="sec_head">
            <h2 class="sec_hdng mb-0">No property posts have been published yet by <span><?php the_title(); ?></span></h2>
          </div>
        </div>
      </section>

  <?php } ?>
  </main>

<?php endwhile; 
    endif; wp_reset_query();?>



        <!-- Modal -->
        <div class="modal fade el_modal" id="agentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>

              <div class="modal-body text-ceter">
                 <?php if ( $img_src ) : ?>
                      <div class="logo_wrapper">
                          <img src="<?php echo esc_url($img_src[0]); ?>"
                          width="<?php echo esc_attr($img_src[1]); ?>"
                          height="<?php echo esc_attr($img_src[2]); ?>"
                          alt="<?php echo esc_attr($img_alt ?: get_the_title()); ?>">
                      </div>
                  <?php endif; ?>
                  
              
                  <div class="text_wrapper">
                    <h4 class="hdng"><?php the_title(); ?></h4>
                      <?php if (!empty($terms) && !is_wp_error($terms)) {
                        foreach ($terms as $term) {
                            echo"<p>". $term->name."</p>";
                            //break;
                        }
                      }?>
                  </div>

                  <?php 

                  $user = get_user_by( 'id', $author );

                  $phoneNo = get_user_meta( $author, 'phone', true );
                  
                  
                  
                  
                  ?>
                  <div class="btns_wrapper">
                    <a href="tel:+<?php echo preg_replace('/[^0-9]/', '', $phoneNo);?>"><i class="fas fa-phone-alt"></i><?php echo $phoneNo; ?></a>
                    <a href="mailto:<?php echo $user->user_email;?>"><i class="fas fa-envelope"></i><?php echo  $user->user_email;?></a>
                  </div>
              </div>
          </div>
        </div>





<?php get_footer(); ?>