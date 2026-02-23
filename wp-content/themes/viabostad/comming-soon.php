<?php
/**
* Template Name: Page: Coming Soon
**/

get_header(); 
    if(have_posts()) :
        while (have_posts() ) : the_post(); ?>

         <main>
      <section class="inner-banner">
        <img src="<?php echo get_stylesheet_directory_uri(); ?>/<?php echo get_stylesheet_directory_uri(); ?>/assets/images/contact-banner.webp" alt="Inner banner" width="1920" height="390" alt="Inner banner">
      </section>
      <section class="default-content">
        <div class="container">
          <div class="row justify-content-center">
            <div class="md-col-12 text-center">
 <img src="https://viabostad.elvirainfotech.live/wp-content/uploads/2026/02/coming-soon.png" alt="cs" class="img-fluid">
            </div>
          </div>
         
        </div>
      </section>
  </main>


      <?php endwhile; 
    endif; 
get_footer(); ?>