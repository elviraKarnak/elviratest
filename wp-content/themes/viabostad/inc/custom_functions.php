<?php 

//acf theme page

if( function_exists('acf_add_options_page') ) {

	acf_add_options_page(array(
		'page_title' 	=> 'Theme General Settings',
		'menu_title'	=> 'Theme Settings',
		'menu_slug' 	=> 'theme-general-settings',
		'capability'	=> 'edit_posts',
		'redirect'		=> 'false'
	));

	
	acf_add_options_sub_page(array(
		'page_title' 	=> 'Theme Footer Settings',
		'menu_title'	=> 'Footer',
		'parent_slug'	=> 'theme-general-settings',
	));

	acf_add_options_sub_page(array(
		'page_title' 	=> 'Theme Footer Settings',
		'menu_title'	=> 'Footer',
		'parent_slug'	=> 'theme-general-settings',
	));

	acf_add_options_sub_page(array(
		'page_title' 	=> 'Testimonials',
		'menu_title'	=> 'Testimonials',
		'parent_slug'	=> 'theme-general-settings',
	));

	acf_add_options_sub_page(array(
		'page_title' 	=> 'Theme Additional Settings',
		'menu_title'	=> 'Additional Fields',
		'parent_slug'	=> 'theme-general-settings',
	));

}

add_action('init', 'add_author_support_to_products');

function add_author_support_to_products() {
    add_post_type_support('product', 'author');
}


add_filter('manage_edit-product_columns', 'add_product_author_column');

function add_product_author_column($columns) {
    $columns['author'] = 'Author';
    return $columns;
}


// Broker Registration 
add_action('init', 'create_broker_role');

function create_broker_role() {

    //remove_role('broker'); // reset if already created

    add_role(
        'broker',
        'Broker',
        [

            // Basic
            'read' => true,
            'upload_files' => true,

            // Required to appear in Author dropdown
            'edit_posts' => true,

            // WooCommerce product capabilities
            'edit_products' => true,
            'publish_products' => true,
            'delete_products' => true,
            'edit_published_products' => true,

        ]
    );

     add_role(
        'agency',
        'Agency',
        [

            // Basic
            'read' => true,
            'upload_files' => true,

            // Required to appear in Author dropdown
            'edit_posts' => true,

            // WooCommerce product capabilities
            'edit_products' => true,
            'publish_products' => true,
            'delete_products' => true,
            'edit_published_products' => true,

        ]
    );
}

add_action('wp_ajax_nopriv_register_broker', 'handle_broker_registration');
add_action('wp_ajax_register_broker', 'handle_broker_registration');

function handle_broker_registration() {

    if (!isset($_POST['email']) || !is_email($_POST['email'])) {
        wp_send_json_error(['message' => 'Invalid email address']);
    }

    $user_type = isset($_POST['user_type']) ? sanitize_text_field($_POST['user_type']) : '';

    $email      = sanitize_email($_POST['email']);
    $username   = sanitize_user($_POST['username']);
    $password   = $_POST['password'];
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name  = sanitize_text_field($_POST['last_name']);
    $phone      = sanitize_text_field($_POST['phone']);
    $bio        = sanitize_textarea_field($_POST['bio']);
    $agency     = $_POST['agency'];

    $attachment_id = false;

    if (username_exists($username) || email_exists($email)) {
        wp_send_json_error(['message' => 'Username or Email already exists']);
    }

    // Create user
    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => $user_id->get_error_message()]);
    }


      wp_update_user([
          'ID' => $user_id,
          'first_name' => $first_name,
          'last_name'  => $last_name,
          'role'       => $user_type
      ]);

      update_user_meta($user_id, 'phone', $phone);
      update_user_meta($user_id, 'description', $bio);


       if (!empty($_FILES['profile_picture']['name'])) {

          require_once(ABSPATH . 'wp-admin/includes/file.php');
          require_once(ABSPATH . 'wp-admin/includes/media.php');
          require_once(ABSPATH . 'wp-admin/includes/image.php');

          $attachment_id = media_handle_upload('profile_picture', $post_id);

          if (!is_wp_error($attachment_id)) {
              update_user_meta($user_id, 'profile_picture', $attachment_id);
          } 
      }



    if($user_type == 'broker'){

        update_user_meta($user_id, 'agency_id_viabostad', $agency);
  

      // Create Broker Post
      $post_id = wp_insert_post([
          'post_type'   => 'broker',
          'post_title'  => $first_name . ' ' . $last_name,
          'post_status' => 'publish',
          'post_content'=> $bio,
          'post_author' => $user_id,
      ]);

      if (is_wp_error($post_id)) {
          wp_send_json_error(['message' => 'Failed to create broker profile']);
      }

      update_post_meta($post_id, 'bio', $bio);
      update_post_meta($post_id, '_agency_id', $agency);
      // wp_set_object_terms($post_id, $agency, 'agency');
      update_user_meta($user_id, 'broker_post_id', $post_id);
    
          if (!is_wp_error($attachment_id)) {

              // Set as featured image
              set_post_thumbnail($post_id, $attachment_id);
          } else {
              wp_send_json_error(['message' => 'Image upload failed']);
          }
      }

    // Auto login
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    //$user_id = 123; // your user ID

      if ( function_exists( 'bp_core_get_user_domain' ) ) {
          $profile_url = bp_core_get_user_domain( $user_id );
          //echo $profile_url;
      }

    wp_send_json_success([
        'redirect' => $profile_url
    ]);

    wp_die();
}

add_action('wp_ajax_nopriv_check_username', 'check_username_ajax');
add_action('wp_ajax_check_username', 'check_username_ajax');

function check_username_ajax() {

    if (empty($_POST['username'])) {
        wp_send_json_error(['message' => 'Username is required']);
    }

    $username = sanitize_user($_POST['username']);

    if (username_exists($username)) {
        wp_send_json_error(['message' => 'Username already taken']);
    } else {
        wp_send_json_success(['message' => 'Username available']);
    }

    wp_die();
}


add_action('wp_ajax_nopriv_broker_search', 'broker_search_ajax');
add_action('wp_ajax_broker_search', 'broker_search_ajax');

function broker_search_ajax() {

    $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
    $agency  = isset($_POST['agency']) ? sanitize_text_field($_POST['agency']) : '';

    $args = [
        'post_type'      => 'broker',
        'posts_per_page' => -1,
        's'              => $keyword,
    ];

  if (!empty($agency)) {
      $args['meta_query'] = [
          [
              'key'     => '_agency_id',
              'value'   => $agency,
              'compare' => '=',
          ]
      ];
  }

    $query = new WP_Query($args);

    if ($query->have_posts()){   
      
            $totalPosts = $query->found_posts;
              
            if($totalPosts > 1){
                $mess = $totalPosts. ' Results Found';
            }else{
              $mess = $totalPosts. ' Result Found';
            }
              
        ?>
   

              <!-- Results -->
              <div class="row mb-3">
                <div class="col-12">
                  <p class="results-text"><?php echo $mess; ?> </p>
                </div>
              </div>

              <!-- Cards -->
              <div class="row g-4">

                <?php while ($query->have_posts()){ 
                          
                      $query->the_post();

                    
                  $thumb_id = get_post_thumbnail_id();
                  $img_src  = $thumb_id ? wp_get_attachment_image_src($thumb_id, 'full') : '';
                  $img_alt  = $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '';

                  ?>
        

                <div class="col-lg-4 col-md-6">
                  <a href="<?php the_permalink(); ?>" class="d-block">
                    <div class="agent-card">
                      <?php if ( $img_src ) : ?>
                        <img src="<?php echo esc_url($img_src[0]); ?>"
                          width="<?php echo esc_attr($img_src[1]); ?>"
                          height="<?php echo esc_attr($img_src[2]); ?>"
                          alt="<?php echo esc_attr($img_alt ?: get_the_title()); ?>"
                          class="img-fluid"
                          >
                      <?php endif; 

                      $post_id  = get_the_ID(); // Your post ID
                      $taxonomy = 'agency'; // Change to your taxonomy slug

                      $terms = wp_get_post_terms($post_id, $taxonomy);
                    
                      ?>
                      <div class="agent-info">
                        <h5><?php the_title(); ?></h5>
                        <?php if (!empty($terms) && !is_wp_error($terms)) {
                          foreach ($terms as $term) {
                              echo "<span>". $term->name. "</span>";
                          }
                      }?>
                      </div>
                    </div>
                  </a>
                </div>

                <?php } ?>

              </div>

            <?php }else{?>

              <div class="row g-4">
                <div class="col-md-12">
                  <h6>No Broker Found.</h6>
                </div>
              </div>

            <?php } wp_reset_query(); 

    wp_die();
}


add_filter( 'login_redirect', function( $redirect_to, $request, $user ) {

    // If no user object, return default
    if ( ! isset( $user->roles ) ) {
        return $redirect_to;
    }

    // Admin → Dashboard
    if ( in_array( 'administrator', (array) $user->roles ) ) {
        return admin_url();
    }

    // Other users → BuddyPress profile
    if ( function_exists( 'bp_core_get_user_domain' ) ) {
        return bp_core_get_user_domain( $user->ID );
    }

    return home_url();

}, 10, 3 );