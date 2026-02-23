<?php


add_theme_support('woocommerce');


add_action('wp_ajax_filter_products', 'custom_filter_products');
add_action('wp_ajax_nopriv_filter_products', 'custom_filter_products');

function custom_filter_products() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 12,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    if (isset($_GET['product_cat']) && is_array($_GET['product_cat'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => array_map('sanitize_text_field', $_GET['product_cat']),
            ),
        );
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        echo '<div class="row">';
        while ($query->have_posts()) {
            $query->the_post();
            global $product; ?>
            <div class="col-lg-4 col-sm-6">
                <div class="product-box">
                    <div class="product-img">
                        <a href="<?php the_permalink(); ?>">
                            <?php echo $product->get_image('woocommerce_thumbnail', ['class' => 'img-fluid']); ?>
                        </a>
                    </div>
                    <div class="product-content">
                        <a href="?add-to-cart=<?php echo esc_attr($product->get_id()); ?>" class="cart">
                            <i class="fa-solid fa-plus"></i> <span>Add to cart</span>
                        </a>
                        <h5><?php the_title(); ?></h5>
                        <p><?php echo $product->get_price_html(); ?></p>
                        <a href="<?php the_permalink(); ?>" class="btn btn-primary btn-hover-black">Shop Now</a>
                    </div>
                    <a href="#" class="wishlist"><?php echo do_shortcode('[yith_wcwl_add_to_wishlist]'); ?></a>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    } else {
        echo '<p>No products found.</p>';
    }

    wp_reset_postdata();
    wp_die(); // Always end AJAX
}

add_filter('the_content', function($content) {
    if (is_page('wishlist')) {
        return do_shortcode('[yith_wcwl_wishlist]');
    }
    return $content;
});





function custom_shop_query_fix( $query ) {
    if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( 'product' ) ) {
        $query->set( 'posts_per_page', 12 );
        $query->set( 'paged', get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 );
    }
}
add_action( 'pre_get_posts', 'custom_shop_query_fix' );


?>