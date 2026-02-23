<?php
// Register shortcode
add_action( 'init', function () {
    add_shortcode( 'home-search-form-three', 'home_search_three_callback' );
});

// Shortcode callback
function home_search_three_callback() {
    ob_start();
    ?>

    <form>
                  <div class="field search">
                    <label>
                      Area
                      <input type="text" placeholder="Enter area or address">
                    </label>
                  </div>
                  <div class="submit_wrapper">
                    <input type="submit" value="Find homes for sale">
                  </div>
                  </form>
     
    <?php
    return ob_get_clean();
}
