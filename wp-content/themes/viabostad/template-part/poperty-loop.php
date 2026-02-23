<?php 
      
    $property_id = get_the_ID();

    // ACF fields
    $bedroom  = get_field('bedroom_sp', $property_id);
    $bathroom = get_field('bathroom_sp', $property_id);
    $area     = get_field('area', $property_id);
    $location = get_field('address_sp', $property_id);

    // property data
    $property_link  = get_permalink();
    $property_title = get_the_title();
    $property_price = get_field('_price', $property_id);
    $property_img   = get_the_post_thumbnail_url($property_id, 'full');
    $currency =  get_woocommerce_currency_symbol();
 ?>

    <div class="col-lg-4 col-md-6">
        <div class="product_card property_card">

            <!-- TOP -->
            <div class="top">
                <a href="<?php echo esc_url($property_link); ?>">
                <img
                    src="<?php echo esc_url($property_img ?: get_stylesheet_directory_uri() . '/assets/images/placeholder.webp'); ?>"
                    alt="<?php echo esc_attr($property_title); ?>"
                    width="520"
                    height="300"
                />
                </a>

                <?php
                $property_id = get_the_ID();
                    $is_saved = is_property_saved($property_id);

                ?>
                
                <div class="heart_icon <?php echo $is_saved ? 'active' : 'initial'; ?>" data-property-id="<?php echo esc_attr($property_id); ?>">
                    <i class="far fa-heart initial"></i>
                    <i class="fas fa-heart active"></i>
                </div>
            </div>

            <!-- MIDDLE -->
            <div class="middle">
                <div class="title_wrap">
                <h3 class="title">
                    <a href="<?php echo esc_url($property_link); ?>">
                    <?php echo esc_html($property_title); ?>
                    </a>
                </h3>

                <?php if ($location): ?>
                    <span>
                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/location.svg" width="14" height="20" />
                    <?php echo $location['city'].", ". $location['country']; ?>
                    </span>
                <?php endif; ?>
                </div>

                <div class="room_info_wrap">

                <?php if ($bedroom): ?>
                    <p>
                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/bedroom.svg" width="24" height="18" />
                    <?php echo esc_html($bedroom); ?> Bedrooms
                    </p>
                <?php endif; ?>

                <?php if ($bathroom): ?>
                    <p>
                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/bath.svg" width="24" height="18" />
                    <?php echo esc_html($bathroom); ?> Bath
                    </p>
                <?php endif; ?>

                <?php if ($area): ?>
                    <p>
                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/sqm.svg" width="24" height="18" />
                    <?php echo esc_html($area); ?> sqm
                    </p>
                <?php endif; ?>

                </div>
            </div>

            <!-- BOTTOM -->
            <div class="bottom">
                <p><?php echo wp_trim_words(get_the_excerpt(), 18); ?></p>

                <div class="price_wrapper">
                <h4 class="price"><?php echo $currency.$property_price; ?></h4>

                <a href="<?php echo esc_url($property_link); ?>" class="arrow_btn">
                    <img
                    src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/arrow-btn-lgblue.svg"
                    width="50"
                    height="50"
                    />
                </a>
                </div>
            </div>

        </div>
    </div>