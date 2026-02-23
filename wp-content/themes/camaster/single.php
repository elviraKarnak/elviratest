<?php
get_header();

if ( have_posts() ) :
    while ( have_posts() ) : the_post();

        if ( 'product' === get_post_type() ) {
            global $product; // Important to define the global $product
            $product = wc_get_product( get_the_ID() );

            echo '<div class="woocommerce">';

            do_action( 'woocommerce_before_single_product' );

            // Start product container
            echo '<div class="product-details">';

            // Add product title, image, price etc. manually if needed, or use template part
            wc_get_template_part( 'content', 'single-product' );

            // Add to Cart button
            if ( $product->is_purchasable() && $product->is_in_stock() ) {
                    echo '<form class="cart" method="post" enctype="multipart/form-data">';
                    echo '<button type="submit" name="add-to-cart" value="' . esc_attr( $product->get_id() ) . '" class="single_add_to_cart_button button alt">';
                    echo esc_html__( 'Add to cart', 'woocommerce' );
                    echo '</button>';
                    echo '</form>';
                }


            echo '</div>'; // end product-details

            do_action( 'woocommerce_after_single_product' );

            echo '</div>'; // end woocommerce
        } else {
            the_content();
        }

    endwhile;
endif;

get_footer();
