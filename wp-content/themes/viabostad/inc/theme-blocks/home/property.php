   <?php
   $propertyTitle = get_field('title_pb_h');
   ?>
  <section class="explore_tab_section">
        <div class="container">
          <?php if($propertyTitle){ ?>
          <h2 class="sec_hdng text-center pb-3">
           <?php echo $propertyTitle; ?>
          </h2>
          <?php } ?>
          <div class="tabs_wrapper">
            <div class="nav_wrapper">
              <ul class="nav nav-pills" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                  <button
                    class="nav-link active"
                    id="all"
                    data-bs-toggle="pill"
                    data-bs-target="#all"
                    type="button"
                    role="tab"
                    aria-controls="all"
                    aria-selected="true"
                  >
                    All
                  </button>
                </li>
                 <?php
                  $terms = get_terms([
                      'taxonomy'   => 'property-type',
                      'hide_empty' => true, // set false if you want empty terms too
                  ]);

                    if (!empty($terms) && !is_wp_error($terms)) {
                  
                                    
                        foreach ( $terms as $term ) { ?>
                          <li class="nav-item" role="presentation">
                              <button
                                class="nav-link"
                                id="<?php echo $term->slug; ?>"
                                data-bs-toggle="pill"
                                data-bs-target="#<?php echo $term->slug; ?>"
                                type="button"
                                role="tab"
                                aria-controls="<?php echo $term->slug; ?>"
                                aria-selected="false"
                              >
                                <?php echo esc_html($term->name); ?>
                              </button>
                            </li>
                        <?php 
                          $i= $i+100; 
                        } ?>
                   
                  <?php } ?>
              </ul>
            </div>


            <div class="tab-content" id="pills-tabContent">

                      <?php 
                        $product_query = new WP_Query([

                          'post_type'      => 'property',
                          'posts_per_page' => 10,
                          'orderby' => 'id',
                          'order' => 'DESC',
                      ]);

                if ( $product_query->have_posts() ) { ?>


                  <div class="tab-pane fade show active"

                    role="tabpanel"
                    aria-labelledby="pills-all-tab"
                  >
                    <div class="row gy-md-4 gy-3 property-slider">
                
              <?php while ( $product_query->have_posts() ) :
                    
                    $product_query->the_post();

                      get_template_part( 'template-part/poperty-loop' );
                   
                   endwhile; ?>

              </div>
            </div>
            <?php } ?>
          </div>
        </div>
      </section>