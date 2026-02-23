     <section class="explore_tranding_room">
        <div class="container">
            <?php if(is_user_logged_in()) {
                $user_id = get_current_user_id();

                 $wishlist = get_user_meta($user_id, 'saved_properties', true);


                $args = [
                    'post_type'      => 'property',
                    'posts_per_page' => -1,
                    'post__in'         => $wishlist ? $wishlist : array(),
                    'post_status'    => 'publish'
                ];

                $query = new WP_Query($args);

                if ($query->have_posts() && !empty($wishlist)) {

                    echo '<div class="row gy-md-4 gy-3 property-slider">';

                    while ($query->have_posts()) {
                        $query->the_post();
                            get_template_part( 'template-part/poperty-loop' );
                            
                    ?>
                        
                        <?php }  echo '</div>';

                } else {
                    echo '<p>No Save properties found.</p>';
                }

                wp_reset_postdata();


         } else { ?>

        <div class="row">
        <div class="col-12 text-center">
            <h2 class="title">You are not logged in</h2>
            <p>Please log in to view your saved properties.</p>
        </div>
        </div>
    <?php } ?>

    </div>
</section>
