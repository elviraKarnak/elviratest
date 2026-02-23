<?php 

$footerLogo = get_field('footer_logo_vib','option');
$footerDescription = get_field('description_logo_vib','option');

$facebook = get_field('facebook_url','option');
$twitter = get_field('twitter_url','option');
$instagram = get_field('instagram_url','option');
$linkedin = get_field('linkedin_url','option');
$youtube = get_field('youtube_url','option');

$menuTitle = get_field('menu_title_vib','option');

$contactTitle = get_field('title_contact','option');
$contactNumber = get_field('contact_number','option');
$contactEmail = get_field('contact_email','option');

$copyright = get_field('copyright_via','option');
$termslink = get_field('terms_n_condition_via','option');

$elvlink = get_field('elvira_link_via','option');




?>
<footer class="site_footer">
      <div class="container">
        <div class="top_footer">
          <div class="row gy-5 justify-content-between">
            <div class="col-lg-4">
              <div class="inner_wrapper">
                <?php if($footerLogo) {?>
                 <img
                  src="<?php echo $footerLogo['url'];?>" 
                  alt="<?php echo $footerLogo['alt'];?>"
                  height="<?php echo $footerLogo['height'];?>"
                  width="<?php echo $footerLogo['width'];?>"
                  loading="lazy"
                  />
                <?php } ?>
                <?php if($footerDescription){ ?>
                  <p><?php echo $footerDescription; ?></p>
                <?php } ?>

                <div class="social_icons">
                 <?php if($facebook){?> 
                  <a href="<?php echo $facebook; ?>">
                    <i class="fab fa-facebook-f"></i>
                  </a>
                  <?php } ?>
                  <?php if($twitter){?> 
                  <a href="<?php echo $twitter; ?>">
                    <i class="fab fa-twitter"></i>
                  </a>
                  <?php } ?>
                  <?php if($instagram){?> 
                  <a href="<?php echo $instagram; ?>">
                    <i class="fab fa-instagram"></i>
                  </a>
                  <?php } ?>
                  <?php if($linkedin){?> 
                  <a href="<?php echo $linkedin; ?>">
                    <i class="fab fa-linkedin-in"></i>
                  </a>
                  <?php } ?>
                  <?php if($youtube){?> 
                  <a href="<?php echo $youtube; ?>">
                    <i class="fab fa-youtube"></i>
                  </a>
                  <?php } ?>
                </div>
              </div>
            </div>
            <div class="col-lg-7">
              <div class="inner_wrapper">
                <div class="row gy-5">
                  <div class="col-md-6">
                    <div class="menu_wrapper">
                      <?php if($menuTitle){ ?>
                        <h3 class="title"><?php echo $menuTitle; ?></h3>
                        <?php } ?>
                        <?php
                          wp_nav_menu(
                            array(
                              'container' => '',
                              'container_class' => '',
                              'container_id' => '',
                              'items_wrap' => '<ul id="%1$s menu" class="%2$s  ">%3$s</ul>',
                              'theme_location' => 'menu-2',
                            )
                          );
                          ?>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="contact_wrapper">
                      <?php if($contactTitle ){ ?>
                      <h3 class="title"><?php echo $contactTitle; ?></h3>
                      <?php } ?>
                      <ul>
                        <?php if($contactNumber){ ?>
                        <li>
                          <i class="fas fa-phone-alt"></i
                          ><a href="tel:+<?php echo preg_replace('/[^0-9]/', '', $contactNumber);?>"><?php echo $contactNumber; ?></a>
                        </li>
                         <?php } ?>
                         <?php if($contactEmail){ ?>
                        <li>
                          <i class="fas fa-envelope"></i
                          ><a href="mailto:<?php echo  preg_replace('/\s+/', '', $contactEmail); ?>">
                            <?php echo $contactEmail; ?></a>
                        </li>
                          <?php } ?>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="bottom_footer">
          <?php if($elvlink){ ?>
                   <a href="<?php echo $elvlink; ?>">
          <p>
            Designed & Developed by
   
              <img
                src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/elvira logo.svg"
                alt="elvira-logo"
                width="27"
                height="20"
            />
          </p></a>
          <?php } ?>
          <?php if($copyright){?>
            <p><?php echo $copyright; ?></p>
          <?php } ?>
          <p>
            <?php 
                $link = get_field('terms_n_condition_via', 'option');
                if( $link ): 
                    $link_url = $link['url'];
                    $link_title = $link['title'];
                    $link_target = $link['target'] ? $link['target'] : '_self';
                    ?>
                     <a href="<?php echo  $link_url; ?>" target="<?php echo $link_target; ?>"><?php echo $link_title; ?></a>
                <?php endif; ?>
         
          </p>
        </div>
      </div>
    </footer>
    

 
<?php wp_footer(); ?>

</body>
</html>	