<?php 

// Home Page Property Filter

add_action('wp_ajax_load_products_by_category', 'load_products_by_category');
add_action('wp_ajax_nopriv_load_products_by_category', 'load_products_by_category');

function load_products_by_category() {

    check_ajax_referer('viabostad_nonce', 'nonce');

    $slug = sanitize_text_field($_POST['category']);

    $args = [
        'post_type'      => 'property',
        'posts_per_page' => 10,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ];

    // If not "all"
    if ($slug !== 'pills-all-tab' && $slug !== 'all') {
        $args['tax_query'] = [
            [
                'taxonomy' => 'property-type',
                'field'    => 'slug',
                'terms'    => $slug,
            ]
        ];
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) :
        echo '<div class="row gy-md-4 gy-3 property-slider">';

     
               while ( $query->have_posts() ) :
                    
                    $query->the_post();

                        get_template_part( 'template-part/poperty-loop' );

                      ?>
                      

                
            <?php endwhile; 

        echo '</div>';
    else :
        echo '<p>No products found.</p>';
    endif;

    wp_reset_postdata();
    wp_die();
}

add_action('wp_ajax_nopriv_filter_search_property', 'filter_search_property_cb');
add_action('wp_ajax_filter_search_property', 'filter_search_property_cb');

function filter_search_property_cb() {

    $search   = isset($_POST['property-search']) ? sanitize_text_field($_POST['property-search']) : '';
    $location = isset($_POST['property-location']) ? sanitize_text_field($_POST['property-location']) : '';
    $type     = isset($_POST['property-type']) ? sanitize_text_field($_POST['property-type']) : '';
    $price    = isset($_POST['property-price']) ? floatval($_POST['property-price']) : '';

    if( $search == 'undefined' ){
        $search = '';
    }


      $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;

      $args = array(
        'post_type'      => 'property',
        'post_status'    => 'publish',
        'posts_per_page' => 9,
        'paged'          => $paged,
        'tax_query'      => array(),
        'meta_query'     => array(),
        's'              => $search,
      );

    /* ==========================
       PRICE FILTER
    ========================== */

    if ( !empty($price)) {
        $args['meta_query'][] = array(
            'key'     => '_price',
            'value'   => $price,
            'compare' => '<=',
            'type'    => 'NUMERIC'
        );
    }

    /* ==========================
       LOCATION TAXONOMY
       (replace with your taxonomy)
    ========================== */


    if (!empty($location) && $location !== 'undefined' && $location !== 'all'  && $location !== 'null') {
        $args['tax_query'][] = array(
            'taxonomy' => 'location',
            'field'    => 'slug',
            'terms'    => $location,
        );
    }

    /* ==========================
       TYPE TAXONOMY
    ========================== */

    if (!empty($type) && $type !== 'undefined' && $type !== 'all' && $type !== 'null') {
        $args['tax_query'][] = array(
            'taxonomy' => 'property-type',
            'field'    => 'slug',
            'terms'    => $type,
        );
    }


    /* If multiple tax filters */
    if ( count( $args['tax_query'] ) > 1 ) {
        $args['tax_query']['relation'] = 'AND';
    }


    //print_r($args);

    $product_query = new WP_Query( $args );

    if ( $product_query->have_posts() ) { ?>


			      <div class="total_found_products">
								<?php
		
									$total        = $product_query->found_posts;
									$per_page     = $product_query->get( 'posts_per_page' );
									$current      = $paged;

									$first = ( $per_page * $current ) - $per_page + 1;
									$last  = min( $total, $per_page * $current );
									?>

									<p class="search_result">
										Showing Newest Results <?php echo esc_html( $first ); ?>–<?php echo esc_html( $last ); ?>
										of <?php echo esc_html( $total ); ?>
									</p>
							</div>

                 <div class="row gy-md-4 gy-3 property-slider">


                        
                        <?php 
                        
                        $map_properties = [];

                         while ( $product_query->have_posts() ) :
    
                            $product_query->the_post();


                             $location = get_field('address_sp', get_the_ID());

                                if ($location) {
                                    $map_properties[] = [
                                        'title' => get_the_title(),
                                        'lat'   => $location['lat'],
                                        'lng'   => $location['lng'],
                                        'link'  => get_permalink(),
                                        'price' => get_field('_price'),
                                        'image' => get_the_post_thumbnail_url()
                                    ];
                                }

                            get_template_part( 'template-part/poperty-loop' );
                        
                        endwhile; ?>

                </div>

              <?php
            //   /* PAGINATION */
            //   $big = 999999999;

            //   $pagination = paginate_links(array(
            //       'base'      => str_replace($big, '%#%', esc_url('?paged=' . $big)),
            //       'format'    => '?paged=%#%',
            //       'current'   => max(1, $paged),
            //       'total'     => $product_query->max_num_pages,
            //       'type'      => 'array',
            //   ));

            //   if ($pagination) {
            //       echo '<div class="ajax-pagination"><ul>';
            //       foreach ($pagination as $page) {
            //           echo '<li>' . $page . '</li>';
            //       }
            //       echo '</ul></div>';
            //   }


                $total_pages = $product_query->max_num_pages;

                if ( $paged < $total_pages ) : ?>
                    
                    <div class="col-12 mt-md-5 mt-3">
                        <div class="show_more_wrapper text-center">
                            <a href="javascript:void(0)" 
                            class="primary_btn icon arrow load-more-btn"
                            data-page="<?php echo esc_attr($paged + 1); ?>"
                            data-max="<?php echo esc_attr($total_pages); ?>">
                            Show More
                            </a>
                        </div>
                    </div>

                <?php endif; ?>
										
      
      <?php } else { ?>
          <p class="no-result">No properties found.</p>
          <?php } wp_reset_postdata(); 
					
        echo '<script id="ajax-map-data" type="application/json">';
        echo json_encode($map_properties);
        echo '</script>';
    wp_die();
    
}


/*--------------------------------------------------------------
 AJAX PRODUCT SUBMISSION
--------------------------------------------------------------*/
add_action('wp_ajax_submit_property_form', 'handle_ajax_property_submission');
add_action('wp_ajax_nopriv_submit_property_form', 'handle_ajax_property_submission');

function handle_ajax_property_submission() {

    //check_ajax_referer('submit_property_form', 'nonce');

    if (empty($_POST['property_title']) || empty($_POST['property_description'])) {
        wp_send_json_error(['message' => 'Required fields missing.']);
    }

    $property_data = [
        'post_title'   => sanitize_text_field($_POST['property_title']),
        'post_content' => wp_kses_post($_POST['property_description']),
        'post_status'  => 'publish',
        'post_type'    => 'property',
        'post_author'  => get_current_user_id(),
    ];

    $property_id = wp_insert_post($property_data);

    if (!$property_id) {
        wp_send_json_error(['message' => 'Property creation failed.']);
    }

    
        /*--------------------------------
        Save ACF Google Map Field
        --------------------------------*/

        if (!empty($_POST['acf_map']) && is_array($_POST['acf_map'])) {

            $map = $_POST['acf_map'];

                $map_data = array(
                'address' => sanitize_text_field($map['address'] ?? ''),
                'lat' => floatval($map['lat'] ?? 0),
                'lng' => floatval($map['lng'] ?? 0),
                'zoom' => intval($map['zoom'] ?? 14),
                'name' => sanitize_text_field($map['name'] ?? ''),
                'street_number' => sanitize_text_field($map['street_number'] ?? ''),
                'street_name' => sanitize_text_field($map['street_name'] ?? ''),
                'city' => sanitize_text_field($map['city'] ?? ''),
                'state' => sanitize_text_field($map['state'] ?? ''),
                'post_code' => sanitize_text_field($map['post_code'] ?? ''),
                'country' => sanitize_text_field($map['country'] ?? ''),
                'country_short' => sanitize_text_field($map['country_short'] ?? '')
                );
    
            update_field('address_sp', $map_data, $property_id);

            /*--------------------------------
            Save extra location meta separately
            --------------------------------*/

            update_post_meta($property_id, 'street_number', sanitize_text_field($map['street_number'] ?? ''));
            update_post_meta($property_id, 'street_name', sanitize_text_field($map['street_name'] ?? ''));
            update_post_meta($property_id, 'city', sanitize_text_field($map['city'] ?? ''));
            update_post_meta($property_id, 'state', sanitize_text_field($map['state'] ?? ''));
            update_post_meta($property_id, 'post_code', sanitize_text_field($map['post_code'] ?? ''));
            update_post_meta($property_id, 'country', sanitize_text_field($map['country'] ?? ''));
        }
   
    /*--------------------------------
    Property Setup
    --------------------------------*/

    //wp_set_object_terms($property_id, 'simple', 'property-type');

    update_post_meta($property_id, '_regular_price', floatval($_POST['price']));
    update_post_meta($property_id, '_price', floatval($_POST['price']));
    update_post_meta($property_id, '_stock_status', 'instock');
    update_post_meta($property_id, '_visibility', 'visible');

    /*--------------------------------
      Custom Meta
    --------------------------------*/

    update_post_meta($property_id, 'bedroom_sp', intval($_POST['bedrooms']));
    update_post_meta($property_id, 'bathroom_sp', intval($_POST['bathrooms']));
    update_post_meta($property_id, 'area', intval($_POST['area']));
    update_post_meta($property_id, '_location', sanitize_text_field($_POST['location']));

    /*--------------------------------
      Categories (ONLY existing)
    --------------------------------*/

    if (!empty($_POST['propertytype'])) {

        $term_ids = array_map('intval', $_POST['propertytype']);
        wp_set_object_terms($property_id, $term_ids, 'property-type');
    }

    
        /*--------------------------------
        Image Upload - Gallery with Featured Image
        --------------------------------*/
  if (!empty($_FILES['property_images']['name'][0])) {

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $gallery_attachment_ids = [];

    $file_count = count($_FILES['property_images']['name']);

    for ($i = 0; $i < $file_count; $i++) {

        if ($_FILES['property_images']['error'][$i] === UPLOAD_ERR_OK) {

            $file_array = [
                'name'     => $_FILES['property_images']['name'][$i],
                'type'     => $_FILES['property_images']['type'][$i],
                'tmp_name' => $_FILES['property_images']['tmp_name'][$i],
                'error'    => $_FILES['property_images']['error'][$i],
                'size'     => $_FILES['property_images']['size'][$i]
            ];

            // Backup original $_FILES
            $original_files = $_FILES;

            // Replace with single file
            $_FILES = ['upload_file' => $file_array];

            $attachment_id = media_handle_upload('upload_file', $property_id);

            // Restore original $_FILES
            $_FILES = $original_files;

            if (!is_wp_error($attachment_id)) {

                $gallery_attachment_ids[] = $attachment_id;

                if (count($gallery_attachment_ids) === 1) {
                    set_post_thumbnail($property_id, $attachment_id);
                }

            } else {
                error_log($attachment_id->get_error_message());
            }
        }
    }

    if (!empty($gallery_attachment_ids)) {
        update_field('property_images', $gallery_attachment_ids, $property_id);
    }
}


    wp_send_json_success(['message' => 'Property published successfully']);
}

 add_action('wp_ajax_toggle_property_wishlist', 'handle_property_wishlist');
    add_action('wp_ajax_nopriv_toggle_property_wishlist', 'handle_property_wishlist');

    // Handle wishlist toggle via AJAX
    function handle_property_wishlist() {

        check_ajax_referer('viabostad_nonce', 'nonce');

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        $user_id = get_current_user_id();
        $property_id = intval($_POST['property_id']);
        
        // Get current wishlist
        $wishlist = get_user_meta($user_id, 'saved_properties', true);
        if (!$wishlist) {
            $wishlist = array();
        }
        
        // Toggle property in wishlist
        if (in_array($property_id, $wishlist)) {
            // Remove from wishlist
            $wishlist = array_diff($wishlist, array($property_id));
            $action = 'removed';
        } else {
            // Add to wishlist
            $wishlist[] = $property_id;
            $action = 'added';
        }
        
        // Save updated wishlist
        update_user_meta($user_id, 'saved_properties', array_values($wishlist));
        
        wp_send_json_success(array(
            'action' => $action,
            'wishlist' => array_values($wishlist)
        ));
    }


    function is_property_saved($property_id) {
        if (!is_user_logged_in()) return false;
        
        $user_id = get_current_user_id();
        $wishlist = get_user_meta($user_id, 'saved_properties', true);
        $wishlist = $wishlist ? $wishlist : array();
        
        return in_array($property_id, $wishlist);
    }


/**
 * Use Classic Editor for specific CPT only
 */
add_filter('use_block_editor_for_post_type', function ($use_block_editor, $post_type) {

    // Replace 'your_cpt_slug' with your actual post type slug
    if ($post_type === 'property') {
        return false; // Disable Gutenberg (enable Classic Editor)
    }

    return $use_block_editor; // Keep Gutenberg for others
}, 10, 2);


/**
 * Add "My Properties" tab in BuddyPress profile
 */
function viabostad_add_properties_tab() {

    bp_core_new_nav_item([
        'name'                => 'My Properties',
        'slug'                => 'my-properties',
        'screen_function'     => 'viabostad_properties_screen',
        'default_subnav_slug' => 'my-properties',
        'position'            => 40,
    ]);
}
add_action('bp_setup_nav', 'viabostad_add_properties_tab');


function viabostad_properties_screen() {

    add_action('bp_template_content', 'viabostad_properties_content');
    bp_core_load_template('members/single/plugins');
}

function viabostad_properties_content() {

    $user_id = bp_displayed_user_id();

    $args = [
        'post_type'      => 'property',
        'posts_per_page' => -1,
        'author'         => $user_id,
        'post_status'    => 'publish'
    ];

    $query = new WP_Query($args);

    if ($query->have_posts()) {

        echo '<div class="row gy-md-4 gy-3 property-slider">';

        while ($query->have_posts()) {
            $query->the_post();
                get_template_part( 'template-part/poperty-loop' );
                   
           ?>
            
            <?php }  echo '</div>';

    } else {
        echo '<p>No properties found.</p>';
    }

    wp_reset_postdata();
}


/**
 * Add "Property" tab in BuddyPress profile
 */
function viabostad_add_property_tab() {

    bp_core_new_nav_item([
        'name'                => 'Add Property',
        'slug'                => 'add-property',
        'screen_function'     => 'viabostad_property_screen',
        'default_subnav_slug' => 'property',
        'position'            => 40,
    ]);
}
add_action('bp_setup_nav', 'viabostad_add_property_tab');


function viabostad_property_screen() {

    add_action('bp_template_content', 'viabostad_property_template');
    bp_core_load_template('members/single/plugins');
}

function viabostad_property_template() {

    locate_template([
        'buddypress/members/single/add-property.php'
    ], true);

}
