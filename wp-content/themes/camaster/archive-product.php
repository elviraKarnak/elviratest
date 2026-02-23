
<?php echo get_header();?>

<!----------page navigation------->
<section class="page-nav">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="page-nav-txt">
                    <p><a href="<?=site_url();?>">Home</a> <i class="fa-solid fa-chevron-right"></i> <span>Shop</span></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!------------Shop--------->
<section class="t-product section-b-padding">
    <div class="container">
        <div class="row">
            <div class="col-12 mb-5">
                <div class="product-list-update">
                    <div class="short-box">
                        <select>
                            <option>Default Sorting</option>
                            <option>Price High To Low</option>
                            <option>Price Low To High</option>
                        </select>
                    </div>
                    <div class="list-update-box">
                        <img src="<?=get_template_directory_uri();?>/theme-assets/images/list-icon.svg" alt="prouct list" class="img-fluid" />
                        <img src="<?=get_template_directory_uri();?>/theme-assets/images/th-icon.svg" alt="prouct table" class="img-fluid" />
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="filter-box">
                    <div class="filter-search-box">
                        <h4>Filter</h4>
                        <div class="filter-search">
                            <input type="text" class="filter-search-inpt" placeholder="Search our Store" />
                            <button><i class="fa-solid fa-magnifying-glass"></i></button>
                        </div>
                    </div>
                    <div class="category-filter filter-p">
                        <p>Categories</p>
                        <div class="accordion" id="accordionExample">
                            <div class="accordion-item">
                              <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                  Brands
                                </button>
                              </h2>
                              <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                  <div class="checkbx-list">
                                    <form id="product-filter-form">
                                        <?php
                                        $product_categories = get_terms(array(
                                            'taxonomy' => 'product_cat',
                                            'hide_empty' => true,
                                        ));
                                        if (!empty($product_categories) && !is_wp_error($product_categories)) {
                                            foreach ($product_categories as $category) {
                                                ?>
                                                <div class="form-group">
                                                    <input type="checkbox" id="<?php echo esc_attr($category->slug); ?>" name="product_cat[]" value="<?php echo esc_attr($category->slug); ?>">
                                                    <label for="<?php echo esc_attr($category->slug); ?>">
                                                        <span><?php echo esc_html($category->name); ?></span>
                                                    </label>
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </form>

                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                    </div>
                    <div class="category-filter filter-p">
                        <p>Colour</p>
                        <div class="accordion" id="accordionExample2">
                            <div class="accordion-item">
                              <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseOne">
                                  Size
                                </button>
                              </h2>
                              <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample2">
                                <div class="accordion-body">
                                  <div class="checkbx-list">
                                    <div class="form-group">
                                        <input type="checkbox" id="XXL">
                                        <label for="XXL"><span>XXL</span></label>
                                    </div>
                                    <div class="form-group">
                                        <input type="checkbox" id="XL">
                                        <label for="XL"><span>XL</span></label>
                                    </div>
                                    <div class="form-group">
                                        <input type="checkbox" id="L">
                                        <label for="L"><span>L</span></label>
                                    </div>
                                    <div class="form-group">
                                        <input type="checkbox" id="M">
                                        <label for="M"><span>M</span></label>
                                    </div>
                                    <div class="form-group">
                                        <input type="checkbox" id="S">
                                        <label for="S"><span>S</span></label>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div id="">
                    <div class="row">
                        <div id="product-results">
                            <?php
                            $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
                            $args = array(
                                'post_type' => 'product',
                                'posts_per_page' => 12,
                                'orderby' => 'date',
                                'order' => 'DESC',
                                'paged'          => $paged,
                            );

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
                                            <div class="wishlist">
                                                <?php
                                                    if( function_exists( 'YITH_WCWL' ) ) {
                                                        echo do_shortcode('[yith_wcwl_add_to_wishlist product_id="' . $product->get_id() . '"]');
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                                echo '</div>';
                            } else {
                                echo '<p>No products found.</p>';
                            }

                            wp_reset_postdata();
                            ?>
                        </div>



                        
                    </div>
                    <?php
                        if ( $query->max_num_pages > 1 ) :
                            $paged = max( 1, get_query_var( 'paged' ) );
                            $total_pages = $query->max_num_pages;

                            $pagination_links = paginate_links( array(
                                'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                                'format'    => '?paged=%#%',
                                'current'   => $paged,
                                'total'     => $total_pages,
                                'type'      => 'array',
                                'prev_text' => '<i class="fa-solid fa-arrow-left"></i>',
                                'next_text' => '<i class="fa-solid fa-arrow-right"></i>',
                            ) );
                            ?>
                            <div class="row">
                                <div class="col-12 text-center mt-3">
                                    <nav class="shop-paginattion" aria-label="Page navigation example">
                                        <ul class="pagination">
                                            <?php
                                            foreach ( $pagination_links as $link ) {
                                                if ( strpos( $link, 'current' ) !== false ) {
                                                    echo '<li class="page-item"><a class="page-link active-tab">' . strip_tags( $link ) . '</a></li>';
                                                } else {
                                                    $link = str_replace('page-numbers', 'page-link', $link);
                                                    echo '<li class="page-item">' . $link . '</li>';
                                                }
                                            }
                                            ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        <?php endif; ?>



                </div>
            </div>
        </div>
        
    </div>
</section>



<script>
jQuery(document).ready(function($) {
    $('#product-filter-form input[type="checkbox"]').on('change', function() {
        let form = $('#product-filter-form');
        let data = form.serialize();

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'GET',
            data: data + '&action=filter_products',
            beforeSend: function() {
                $('#product-results').html('<p>Loading...</p>');
            },
            success: function(response) {
                $('#product-results').html(response);
            }
        });
    });
});
</script>


<?php get_footer();?>