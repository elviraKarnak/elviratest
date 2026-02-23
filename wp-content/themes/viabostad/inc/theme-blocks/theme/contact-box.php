<?php 

$contactTitle = get_field('title_cs');
$contactDescription = get_field('description_cs');
$contactImg = get_field('section_image_cs');

$emailLogo = get_field('email_logo');
$emailLabel = get_field('email_label');
$email      = get_field('email_cs');

$phoneLogo = get_field('phone_logo');
$phoneLabel = get_field('phone_label');
$phone      = get_field('phone_cs');

$locationLogo  = get_field('location_logo');
$locationLabel = get_field('location_label');
$location      = get_field('location_cs');

$formTitle = get_field('form_title_cs');
$form = get_field('form_shortcode_cs');

$socialTitle = get_field('social_media_title');

     
?>
  
  
  
  <section class="contact-section">
      <div class="container">
        <div class="row g-4">

          <!-- LEFT CONTENT -->
          <div class="col-lg-6">
            <?php if($contactTitle){ ?>
                <h2 class="sec_hdng"> <?php echo $contactTitle; ?></h2>
            <?php } ?>
            <?php if($contactDescription){ ?>
                <p class="section-desc"><?php echo $contactDescription; ?></p>
            <?php } ?>
            <?php if($contactImg){ ?>
                <div class="image-box">
                <img
                    src="<?php echo $contactImg['url'];?>" 
                    alt="<?php echo $contactImg['alt'];?>"
                    height="<?php echo $contactImg['height'];?>"
                    width="<?php echo $contactImg['width'];?>"
                    loading="lazy"
                />
                </div>
            <?php } ?>

            <!-- Contact Info -->
            <div class="info-box">

            <?php if($email){?>
              <div class="d-flex align-items-center">
                <div class="col-lft">
                    <?php if($emailLogo)?>
                  <div class="icon-box">
                     <img
                    src="<?php echo $emailLogo['url'];?>" 
                    alt="<?php echo $emailLogo['alt'];?>"
                    height="<?php echo $emailLogo['height'];?>"
                    width="<?php echo $emailLogo['width'];?>"
                    />
                </div>
                </div>
                <div class="col-rgt">

                <?php if($emailLabel){?>
                    <h6><?php echo $emailLabel; ?></h6>
                <?php } ?>
                  
                  <p> <a href="mailto:<?php echo  preg_replace('/\s+/', '', $email); ?>"><?php echo $email; ?></a> </p>
                </div>
              </div>
              <?php } ?>

            <?php if($phone){?>

              <div class="d-flex align-items-center">
                <div class="col-lft">
                    <?php if($phoneLogo){ ?>
                  <div class="icon-box">
                    <img
                    src="<?php echo $phoneLogo['url'];?>" 
                    alt="<?php echo $phoneLogo['alt'];?>"
                    height="<?php echo $phoneLogo['height'];?>"
                    width="<?php echo $phoneLogo['width'];?>"
                    /></div>
                    <?php } ?>
                </div>
                <div class="col-rgt">
                    <?php if($phoneLabel){ ?>
                    <h6><?php echo $phoneLabel; ?></h6>
                  <?php } ?>
                  <p> <a href="tel:+<?php echo preg_replace('/[^0-9]/', '', $phone);?>"><?php echo $phone; ?></a></p>
                </div>
              </div>
            <?php } ?>

             <?php if($location){?>
              <div class="d-flex align-items-center">
                <div class="col-lft">
                    <?php if($locationLogo) {?>
                  <div class="icon-box">
                    <img
                    src="<?php echo $locationLogo['url'];?>" 
                    alt="<?php echo $locationLogo['alt'];?>"
                    height="<?php echo $locationLogo['height'];?>"
                    width="<?php echo $locationLogo['width'];?>"
                    /></div>
                    <?php } ?>
                </div>
                <div class="col-rgt">
                    <?php if($locationLabel){ ?>
                        <h6><?php echo $locationLabel; ?></h6>
                  <?php } ?>
                  
                  <p><?php echo $location; ?></p>
           
                </div>
              </div>
              <?php } ?>
            </div>
          </div>


          <!-- RIGHT FORM -->
          <div class="col-lg-6">
            <div class="form-card">
            <?php if($form){ ?>

                <?php if($formTitle){ ?>
                    <h2 class="sec_hdng">
                    <?php echo $formTitle; ?>
                    </h2>
                <?php } ?>

              
              <?php echo do_shortcode($form); ?>

              <?php } ?>

              <?php if(have_rows('social_media_cs')){?>

              <hr>
              <div class="follow-wp d-md-flex">

                <?php if($socialTitle){ ?>
                    <p class="follow-text"><?php echo $socialTitle; ?></p>
                <?php } ?>
                <div class="social-icons">
                   <?php while(have_rows('social_media_cs')){ the_row(); 
                   
                     $url  = get_sub_field('url_sin');
                     $logo = get_sub_field('logo_sin');

                     if($url){
                   ?> 
                        <a href="<?php echo $url; ?>">
                            <img
                            src="<?php echo $logo['url'];?>" 
                            alt="<?php echo $logo['alt'];?>"
                            height="<?php echo $logo['height'];?>"
                            width="<?php echo $logo['width'];?>"
                            />
                     
                        </a>
                        <?php } ?>
                    <?php } ?>
                </div>
              </div>
            <?php } ?>
            </div>
          </div>

        </div>
      </div>
    </section>