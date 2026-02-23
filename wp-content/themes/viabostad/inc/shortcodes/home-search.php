<?php

require get_template_directory() . '/inc/shortcodes/serch-form-one.php';
require get_template_directory() . '/inc/shortcodes/serch-form-two.php';
require get_template_directory() . '/inc/shortcodes/serch-form-three.php';


// Register shortcode
add_action( 'init', function () {
    add_shortcode( 'home-search', 'home_search_callback' );
});

// Shortcode callback
function home_search_callback() {
    ob_start();
    ?>
      
          <div class="find_property_wrapper">
            <ul class="nav nav-pills" id="homeFilter" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="for-sale-tab" data-bs-toggle="tab" data-bs-target="#for-sale" type="button" role="tab" aria-controls="for-sale" aria-selected="true">For sale</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="final-price-tab" data-bs-toggle="tab" data-bs-target="#final-price" type="button" role="tab" aria-controls="final-price" aria-selected="false">Final prices</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="broker-tab" data-bs-toggle="tab" data-bs-target="#broker" type="button" role="tab" aria-controls="broker" aria-selected="false">Search for a broker</button>
              </li>
            </ul>

            <div class="tab-content" id="homeFilterContent">
              <div class="tab-pane fade show active" id="for-sale" role="tabpanel" aria-labelledby="home-tab">
                <div class="form_wrap">
                  <?php echo do_shortcode('[home-search-form-one]'); ?>
                </div>
              </div>
              <div class="tab-pane fade" id="final-price" role="tabpanel" aria-labelledby="profile-tab">
                <div class="form_wrap">
                  <?php echo do_shortcode('[home-search-form-two]'); ?>
                </div>
              </div>
              <div class="tab-pane fade" id="broker" role="tabpanel" aria-labelledby="contact-tab">
                <div class="form_wrap">
                  <?php echo do_shortcode('[home-search-form-three]'); ?>
                </div>
              </div>
            </div>
          </div> 
    <?php
    return ob_get_clean();
}
