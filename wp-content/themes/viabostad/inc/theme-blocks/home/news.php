<?php $title = get_field('title_news'); ?>

<section class="read_latest_news">
  <div class="container">
    <?php if($title){ ?>
    <h2 class="sec_hdng text-center pb-md-4 pb-2">
      <?php echo $title; ?>
    </h2>
    <?php } ?>

    <div class="row gy-4">

      <?php
      $post_query = new WP_Query([
        'post_type'      => 'post', // or product if intentional
        'posts_per_page' => 5,
        'orderby'        => 'ID',
        'order'          => 'DESC',
      ]);

      if ( $post_query->have_posts() ) :

        // 👉 FIRST POST (BIG)
        $post_query->the_post();

        $thumb_id = get_post_thumbnail_id();
        $img_src  = $thumb_id ? wp_get_attachment_image_src($thumb_id, 'large') : '';
        $img_alt  = $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '';
      ?>

      <!-- BIG CARD -->
      <div class="col-lg-6 d-md-block d-none">
        <div class="main_card_wrapper">
          <?php if ( $img_src ) : ?>
            <img src="<?php echo esc_url($img_src[0]); ?>"
                 width="<?php echo esc_attr($img_src[1]); ?>"
                 height="<?php echo esc_attr($img_src[2]); ?>"
                 alt="<?php echo esc_attr($img_alt ?: get_the_title()); ?>">
          <?php endif; ?>

          <div class="cont">
            <h6>
              <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h6>
            <p><?php echo wp_trim_words(get_the_excerpt(), 25); ?></p>
          </div>
        </div>
      </div>

      <!-- SMALL CARDS -->
      <div class="col-lg-6">
        <div class="row gy-4 property-slider">

          <?php
          while ( $post_query->have_posts() ) :
            $post_query->the_post();

            $thumb_id = get_post_thumbnail_id();
            $img_src  = $thumb_id ? wp_get_attachment_image_src($thumb_id, 'medium') : '';
            $img_alt  = $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '';
          ?>

          <div class="col-md-6">
            <div class="small_card_wrapper">

              <?php if ( $img_src ) : ?>
                <img src="<?php echo esc_url($img_src[0]); ?>"
                     width="<?php echo esc_attr($img_src[1]); ?>"
                     height="<?php echo esc_attr($img_src[2]); ?>"
                     alt="<?php echo esc_attr($img_alt ?: get_the_title()); ?>">
              <?php endif; ?>

              <div class="cont">
                <h3><?php the_title(); ?></h3>
                <p><?php echo wp_trim_words(get_the_excerpt(), 12); ?></p>
              </div>

              <a href="<?php the_permalink(); ?>" class="absolute_link"></a>
            </div>
          </div>

          <?php endwhile; ?>

        </div>
      </div>

      <?php
        wp_reset_postdata();
      endif;
      ?>

    </div>
  </div>
</section>
