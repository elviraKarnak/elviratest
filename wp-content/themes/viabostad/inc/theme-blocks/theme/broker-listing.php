<?php 

$name = get_field('title_broker');
$description = get_field('description_broker');

?>

    <section class="agents-section space-mr">
      <div class="container">

        <!-- Header -->
        <div class="row mb-md-5 mb-4 ">
          <div class="col-lg-5">
            <?php if($name) { ?>
              <h2 class="sec_hdng"><?php echo $name; ?></h2>
            <?php } ?>
            <?php if($name) { ?>
              <p><?php echo $description; ?></p>
            <?php } ?>
          </div>

          <div class="col-lg-7">
            <form id="brokerSearchForm" class="search-outer-wp">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="search-wp position-relative">
                    <input type="text" class="form-control" name="keyword" placeholder="Search for broker">
                    <button id="search-broker">
                      <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/search.svg" alt="Search" width="20px" height="20px">
                    </button>
                  </div>

       

                </div>

                   <?php 

                        $agency_users = get_users([
                            'role'    => 'agency',
                            'orderby' => 'display_name',
                            'order'   => 'ASC'
                        ]);

                        if ( ! empty( $agency_users ) ) { ?>

                        <div class="col-md-6">
                        <select class="form-control broker-agencies" name="agency">
                            <?php foreach ( $agency_users as $user ) : 
                                        
                                        $full_name = trim( $user->first_name . ' ' . $user->last_name );

                                        // fallback if no first/last name
                                        if ( empty( $full_name ) ) {
                                            $full_name = $user->display_name;
                                        }
                                    ?>

                                        <option value="<?php echo esc_attr( $user->ID ); ?>">
                                            <?php echo esc_html( $full_name ); ?>
                                        </option>

                                    <?php endforeach; ?>
                        </select>
                        </div>
                 <?php } ?>
              </div>
            </form>
          </div>
        </div>

            <?php 

            $broker_query = new WP_Query([

                'post_type'      => 'broker',
                'posts_per_page' => 10,
                'orderby' => 'id',
                'order' => 'ASC',
            ]);

            if ($broker_query->have_posts()) { 
              


              $totalPosts = $broker_query->found_posts;
              
              if($totalPosts > 1){
                 $mess = $totalPosts. ' Results Found';
              }else{
                $mess = $totalPosts. ' Result Found';
              }
              
              ?>

          <div id="broker-results">      

              <!-- Results -->
              <div class="row mb-3">
                <div class="col-12">
                  <p class="results-text"><?php echo $mess; ?> </p>
                </div>
              </div>

              <!-- Cards -->
              <div class="row g-4">

                <?php while ($broker_query->have_posts()){ 
                          
                      $broker_query->the_post();

                    
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
                
                        $author = get_the_author_ID();
                        $agency_id = get_user_meta($author, 'agency_id_viabostad', true);
                        $get_agency = get_user_by('id', $agency_id);
                        $full_name = trim( $get_agency->first_name . ' ' . $get_agency->last_name );
                    
                      ?>
                      <div class="agent-info">
                        <h5><?php the_title(); ?></h5>
                        <p><?php echo $full_name; ?></p>
                      </div>
                    </div>
                  </a>
                </div>

                <?php } ?>

              </div>

            <?php } wp_reset_query(); ?>

      </div>

      </div>
    </section>

    <script>
      jQuery(document).ready(function ($) {


        $("#search-broker").on('click', function(e) {
           e.preventDefault();
          $('#brokerSearchForm').submit();
        })

          $(".broker-agencies").on('change', function(e) {
           $('#brokerSearchForm').submit();
        })

        $('#brokerSearchForm').on('submit', function (e) {
          e.preventDefault();

          var formData = new FormData(this);

          formData.append('action', 'broker_search');
             
           $('#broker-results').html('<span class="loader-property"></span>');

            $.ajax({
              url: '<?php echo home_url('/wp-admin/admin-ajax.php')?>', // WP default
              type: 'POST',
              data:formData,
              processData: false,
              contentType: false,
              success: function (response) {
                $('#broker-results').html(response);
              }
            });

          });

      });
  
    </script>