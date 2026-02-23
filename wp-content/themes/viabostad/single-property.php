<?php get_header(); 
    if(have_posts()) :
        while (have_posts() ) : the_post(); 
        
        $property_id = get_the_ID();

        // ACF fields
        $bedroom  = get_field('bedroom_sp', $property_id);
        $bathroom = get_field('bathroom_sp', $property_id);
        $area     = get_field('area', $property_id);
        $location = get_field('address_sp', $property_id);
        $currency =  get_woocommerce_currency_symbol();
        $floor = get_field('floor_sp', $property_id);
        

        // property data
        $property_link  = get_permalink();
        $property_title = get_the_title();
        $property_price = get_field('_price', $property_id);
        $property_img   = get_the_post_thumbnail_url($property_id, 'full');

        $thumb_id = get_post_thumbnail_id();
        $img_src  = $thumb_id ? wp_get_attachment_image_src($thumb_id, 'full') : '';
        $img_alt  = $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '';

        $imgGall = get_field('property_images', $property_id) ?? [];



         $dummyImg =  get_stylesheet_directory_uri() .'/assets/images/housing-details.webp';
         $innerBanner = get_field('product_details_banner', 'option');


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
          <img 
              src="<?php echo esc_url($imgUrl); ?>" 
              alt="<?php echo esc_attr($alt); ?>" 
              width="<?php echo esc_attr($width); ?>" 
              height="<?php echo esc_attr($height); ?>">
        </section>

        <section class="housing-details-sec">
          <div class="container">
            <div class="row g-md-5 g-4">
              <div class="col-xl-8 col-lg-7">
                <div class="house-gallery">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <img src="<?php echo esc_url($property_img ?: get_stylesheet_directory_uri() . '/assets/images/placeholder.webp'); ?>"
                       width="<?php echo esc_attr($img_src[1]); ?>"
                      height="<?php echo esc_attr($img_src[2]); ?>" 
                      alt="<?php echo esc_attr($property_title); ?>">
                    </div>

                    <div class="col-md-6">
                        <?php if(!empty($imgGall)): ?>
                          <div class="row g-3">
                            <?php foreach ($imgGall as $img): ?>
                              <div class="col-md-6 col-6">
                                <img src="<?php echo esc_url($img['url']); ?>"
                                width="<?php echo esc_attr($img['width']); ?>" 
                                height="<?php echo esc_attr($img['height']); ?>" 
                                alt="<?php echo esc_attr($img['alt']); ?>">
                              </div>
                            <?php endforeach; ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                
                <div class="pd-wrapper">

                  <!-- Header -->
                  <div class="pd-header">
                    <div class="pd-header-left">
                      <h2 class="sec_hdng"><?php echo esc_html($property_title); ?></h2>
                      <p class="pd-location">
                        <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/loc2.svg" width="20" height="20" alt="Location"> 
                        <?php echo $location['city'].", ". $location['country']; ?>
                      </p>
                    </div>
                    <div class="pd-price"><?php echo $currency . " " . number_format($property_price); ?></div>
                  </div>

                  <!-- Description -->
                  <div class="pd-section">
                    <h2 class="pd-section-title">Description</h2>
                      <?php the_content();  ?>
                  </div>

                  <div class="pd-divider"></div>

                  <!-- Details -->
                  <div class="pd-section">
                    <h2 class="pd-section-title">Details</h2>

                    <div class="pd-details-grid">
                      <?php if ($bedroom): ?>
                        <div class="pd-detail-card"><img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/icon1.svg" width="20" height="20"
                            alt="Bedrooms"><span> <strong>Bedrooms:</strong> <?php echo $bedroom; ?></span>
                        </div>
                      <?php endif; ?>
                      <?php if ($bathroom): ?>
                        <div class="pd-detail-card"><img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/icon2.svg" width="20" height="20" alt="Bathrooms">
                          <span><strong>Bathrooms:</strong> <?php echo $bathroom; ?></span>
                        </div>
                      <?php endif; ?>
                      <?php if ($area): ?>
                        <div class="pd-detail-card"><img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/icon3.svg" width="20" height="20" alt="Areas">
                          <span><strong>Areas:</strong> <?php echo esc_html($area); ?> sqm</span>
                        </div>
                      <?php endif; ?>
                      <?php
                            $property_id = get_the_ID();

                            $types = get_the_terms($property_id, 'property-type');

                            if (!empty($types) && !is_wp_error($types)) :?>
                                <div class="pd-detail-card"><img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/icon4.svg" width="20" height="20" alt="Type">
                                <span><strong>Type:</strong>
                                
                                <?php $type_links = array();

                                foreach ($types as $type) {
                                    $type_links[] =  esc_html($type->name);
                                }

                                echo implode(', ', $type_links);

                                echo '  </span></div>';
                            endif;
                            ?>

                            <?php if ($property_price): ?>
                              <div class="pd-detail-card"><img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/icon5.svg" width="20" height="20"
                                alt="Charge"><span><strong>Charge:</strong> $<?php echo esc_html($property_price); ?></span>
                              </div>
                            <?php endif; ?>
                            <?php if ($floor): ?>
                              <div class="pd-detail-card"><img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/icon6.svg" width="20" height="20" alt="Floor">
                                <span><strong>Floor:</strong> <?php echo esc_html($floor); ?></span>
                              </div>
                            <?php endif; ?>
                    </div>
                  </div>

                </div>
             </div>
              <div class="col-xl-4 col-lg-5">

                <?php if( $location ): ?>

                <div class="map-sc">
                    <div class="acf-map" style="height: 450px;" data-zoom="<?php echo esc_attr($location['zoom']); ?>">
                        
                        <div class="marker"
                            data-lat="<?php echo esc_attr($location['lat']); ?>"
                            data-lng="<?php echo esc_attr($location['lng']); ?>">
                        </div>

                    </div>
                </div>

              <?php endif; 
              
               $author_id = get_post_field( 'post_author', get_the_ID() );
                $full_name  = false;
          
                //$agency_id = get_user_meta($author, 'agency_id_viabostad', true);


                $author = get_user_by('id', $author_id);
                $full_name = trim( $author->first_name . ' ' . $author->last_name );
                    
              
              
              ?>

                <div class="ac-wrapper">

                  <!-- Header -->
                  <div class="ac-header">

   <?php 
                   $avatar_url = get_avatar_url( $author_id ) ?? get_stylesheet_directory_uri() . '/assets/images/profile_dummy.png';

                  if ( $avatar_url ) : ?>
                      <div class="logo_wrapper">
                          <img 
                              src="<?php echo esc_url( $avatar_url ); ?>" 
                              alt="<?php echo esc_attr( get_the_author_meta( 'display_name', $author_id ) ); ?>"
                              class="ac-avatar"
                              width="91" height="91"
                          >
                      </div>
                  <?php endif; ?>

                   
                
                    <div class="ac-info">
                      <?php if( $full_name){?>
                      <h3 class="ac-name"><?php echo esc_html($full_name); ?></h3>
                      <?php } ?>
                      <span class="ac-role" style="text-transform: capitalize;"><?php echo esc_html($author->roles[0]); ?></span>
                    </div>
                  </div>
                
                  <!-- Description -->
                  <p class="ac-text">
                    Send a message to the advertiser to ask questions.
                  </p>
                
                  <!-- Button -->
                  <div class="ac-button-wrap">
                    <a href="#" class="primary_btn icon arrow" data-bs-toggle="modal" data-bs-target="#agentModal">
                      Contact Broker
                    </a>
                  </div>
                
                  <div class="ac-divider"></div>
                
                  <!-- Interested -->
                  <h4 class="ac-interest-title">Interested?</h4>
                      <?php
                      $property_id = get_the_ID();
                      $is_saved = is_property_saved($property_id);
                      ?>

                      <button class="add_to_wishlist_btn primary_btn wishlist-toggle <?php echo $is_saved ? 'saved' : ''; ?>" 
                              data-property-id="<?php echo $property_id; ?>" style="background: transparent;color: #3fa9db;">
                         <svg class="yith-wcwl-icon yith-wcwl-icon-svg yith-wcwl-add-to-wishlist-button-icon"
                                  id="yith-wcwl-icon-heart"
                                  viewBox="0 0 24 24"
                                  xmlns="http://www.w3.org/2000/svg"
                                  fill="<?php echo $is_saved ? '#3fa9db' : 'none'; ?>"
                                  stroke="<?php echo $is_saved ? '#3fa9db' : 'currentColor'; ?>"
                                  stroke-width="1.8"
                                  stroke-linecap="round"
                                  stroke-linejoin="round">

                                  <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"/>
                              </svg>
                            <span class="button_text"><?php echo $is_saved ? 'Saved' : 'Save Property'; ?></span>
                      </button>
                                    
                                            
                
                </div>
             

                
                <?php 

                    $product_query = new WP_Query([

                      'post_type'      => 'property',
                      'posts_per_page' => 1,
                      'orderby' => 'id',
                      'order' => 'ASC',
                      'author' => $author_id,
                      'post__not_in' => [get_the_ID()]
                  ]);


                if ( $product_query->have_posts() ) { ?>

                  
                      <div class="card-sc details-card-broker">
                         

                            <?php while ( $product_query->have_posts() ) :
                                  
                                  $product_query->the_post();

                                    get_template_part( 'template-part/poperty-loop' );
                                
                                endwhile; 
                            ?>
                        </div>
                  
                  <?php } ?>
                  
                </div>
              </div>
            </div>
          </div>
        </section>
  </main>



     <!-- Modal -->
        <div class="modal fade el_modal" id="agentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>

              <div class="modal-body text-ceter">
                

                  <div class="modal-body text-center">
                   <?php 
                   $avatar_url = get_avatar_url( $author_id ) ?? get_stylesheet_directory_uri() . '/assets/images/profile_dummy.png';

                  if ( $avatar_url ) : ?>
                      <div class="logo_wrapper">
                          <img 
                              src="<?php echo esc_url( $avatar_url ); ?>" 
                              alt="<?php echo esc_attr( get_the_author_meta( 'display_name', $author_id ) ); ?>"
                              class="author-avatar"
                          >
                      </div>
                  <?php endif; ?>
                </div>
                                  
              
             
                  <?php 


                  $phoneNo = get_user_meta( $author_id, 'phone', true );
                  
                  
                  
                  
                  ?>
                  <div class="btns_wrapper">
                    <a href="tel:+<?php echo preg_replace('/[^0-9]/', '', $phoneNo);?>"><i class="fas fa-phone-alt"></i><?php echo $phoneNo; ?></a>
                    <a href="mailto:<?php echo $author->user_email;?>"><i class="fas fa-envelope"></i><?php echo  $author->user_email;?></a>
                  </div>
              </div>
          </div>
        </div>
     
                               <!-- <form class="add_cart_form">
                                  <div class="quantity">
                                    <label class="screen-reader-text" for="quantity_6993fd8e0352a">Product quantity</label>
                                    <input type="number" class="qty_text" min="1" step="1" value='1'>
                                  </div>
                                  <button type="submit"  class="add_to_cart_button">Add to cart</button>
                                </form>
                        -->
                
                      


      <?php endwhile; 
    endif; ?>




<script>
  jQuery(document).ready(function($) {


      function initMap($el) {

          var $markers = $el.find('.marker');

          var mapArgs = {
              zoom        : $el.data('zoom') || 14,
              center      : new google.maps.LatLng(0, 0),
              mapTypeId   : google.maps.MapTypeId.ROADMAP
          };

          var map = new google.maps.Map( $el[0], mapArgs );
          map.markers = [];

          $markers.each(function(){
              initMarker( $(this), map );
          });

          centerMap( map );
      }

      function initMarker($marker, map) {

          var lat = $marker.data('lat');
          var lng = $marker.data('lng');

          var latLng = {
              lat: parseFloat(lat),
              lng: parseFloat(lng)
          };

          var marker = new google.maps.Marker({
              position : latLng,
              map: map
          });

          map.markers.push( marker );
      }

      function centerMap(map) {

          var bounds = new google.maps.LatLngBounds();

          map.markers.forEach(function( marker ){
              bounds.extend({
                  lat: marker.position.lat(),
                  lng: marker.position.lng()
              });
          });

          if( map.markers.length == 1 ){
              map.setCenter( bounds.getCenter() );
          } else{
              map.fitBounds( bounds );
          }
      }

      $(document).ready(function(){
          $('.acf-map').each(function(){
              initMap( $(this) );
          });
      });

  });
</script>


<?php get_footer(); ?>