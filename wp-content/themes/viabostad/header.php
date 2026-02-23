<!doctype html>
<html <?php language_attributes(); ?>>

<head>
  <!-- Required meta tags -->
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport"
    content="width=device-width, minimum-scale=1, maximum-scale=1, initial-scale=1, shrink-to-fit=no">
  <!-- Page Title -->
  <title><?php wp_title('|', true, 'right');
  bloginfo('name'); ?></title>
  <!-- Stylesheets and Other Head Elements -->
  <?php wp_head(); ?>
</head>



<body <?php body_class(); ?>>
  <?php wp_body_open(); ?>

    <header class="site_header">
      <div class="container">
        <div class="header_wrapper">
          <div class="logo_wrapper">
            <?php if(has_custom_logo()){
                the_custom_logo(); 
              }
              ?>
          </div>
          <nav>

          <?php
            wp_nav_menu(
              array(
                'container' => '',
                'container_class' => '',
                'container_id' => '',
                'items_wrap' => '<ul id="%1$s menu" class="%2$s primary_menu ">%3$s</ul>',
                'theme_location' => 'menu-1',
              )
            );
            ?>
            <?php if(is_user_logged_in()){ ?>
              <a href="<?php echo wp_logout_url(); ?>" class="login_btn">Log Out</a>
            <?php } else{ ?>
              <a href="#" class="login_btn">Log In</a>
            <?php } ?>
          </nav>
          <div class="btns_wrapper d-xl-none">
            <?php if(is_user_logged_in()){ ?>
              <a href="<?php echo wp_logout_url(); ?>" class="login_btn">Log Out</a>
            <?php } else{ ?>
              <a href="#" class="login_btn">Log In</a>
            <?php } ?>
            <div class="hemburger">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
        </div>
      </div>
    </header>