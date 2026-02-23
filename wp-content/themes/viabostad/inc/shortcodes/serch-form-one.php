<?php
// Register shortcode
add_action( 'init', function () {
    add_shortcode( 'home-search-form-one', 'home_search_form_one_callback' );
});

// Shortcode callback
function home_search_form_one_callback() {
    ob_start();
    ?>

        <form>
                  <div class="field search">
                    <label>
                      Area
                      <input type="text" placeholder="Enter area or address">
                    </label>
                  </div>
  
                  <div class="field checkboxes_wrapper">
                    <label>
                      <input type="checkbox" name="house_type">
                      <div class="field_text">
                        <img src="/wp-content/uploads/2026/02/buliding.png" alt="icon" width="20" height="20" class='icon'>
                      All types
                      </div>
                    </label>

                    <?php
                        $terms = get_terms([
                            'taxonomy'   => 'property-type',
                            'hide_empty' => true, // set false if you want empty terms too
                        ]);

                    if (!empty($terms) && !is_wp_error($terms)) {?>
                            <?php foreach ( $terms as $term ) { ?>
                            <label>
                              <input type="checkbox" id="<?php echo $term->slug; ?>" name="propertytype[]" value="<?php echo $term->term_id; ?>">
                                <div class="field_text">
                                  <img src="/wp-content/uploads/2026/02/buliding.png" alt="icon" width="20" height="20" class='icon'>
                                  <?php echo esc_html($term->name); ?>
                                </div>
                            </label>
                            <?php  } ?> 
                        <?php } ?>
                      
                  </div>
                  <div class="filter_options text-center">
                    <button type="button" class="show_filter_option">Show search filters <i class="fas fa-chevron-right"></i></button>
                    <div class="filter_options_wrapper">
                      <div class="row_group">
                        <div class="field select">
                          <label>
                            <?php 
                            $rooms_min_values = ['1','1.5','2','2.5','3','3.5','4','5','6','7','8','10','15'];

                            
                            ?>
                            Minimum number of rooms
                            <select class="form-select w-100">
                              <option>All</option>
                              <option value="1">1 room</option>
                              <option value="2">2 room</option>
                            </select>
                          </label>
                        </div>
                        <div class="field select">
                          <label>
                            Minimum living area
                            <select class="form-select w-100">
                              <option>All</option>
                              <option value="20">20<sup>2</sup></option>
                              <option value="25">25 m<sup>2</sup></option>
                            </select>
                          </label>
                        </div>
                        <div class="field select">
                          <label>
                            Highest price
                            <select class="form-select w-100">
                              <option>Nothing</option>
                              <option>100,000 SEK</option>
                              <option>200,000 SEK</option>
                            </select>
                          </label>
                        </div>
                      </div>
                      <div class="row_group">
                       
                        <div class="field flex-grow-1">
                          <label>
                            Keyword
                            <input type="text" placeholder="Pool, tiled stoved, etc ">
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="submit_wrapper">
                    <input type="submit" value="Find homes for sale">
                  </div>
                  </form>


    <?php
    return ob_get_clean();
}
