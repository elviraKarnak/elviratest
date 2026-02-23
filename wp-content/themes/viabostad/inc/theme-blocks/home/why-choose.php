 <?php
$homeWhyChooseTitle = get_field('title_wcb');
$homeWhyChooseDescription = get_field('description_wcb');
$homeWhyChooseDeskImg = get_field('section_image_wcb');
 
 ?>
 
 <section class="why_choose">
        <div class="container">
          <?php if($homeWhyChooseTitle){ ?>
            <h2 class="sec_hdng text-center pb-2">
             <?php echo $homeWhyChooseTitle; ?>
            </h2>
          <?php } ?>
          <div class="row align-items-center gy-5">
            <div class="col-lg-6">
              <div class="img_wrapper">
                <img
                        src="<?php echo $homeWhyChooseDeskImg['url'];?>" 
                        alt="<?php echo $homeWhyChooseDeskImg['alt'];?>"
                        height="<?php echo $homeWhyChooseDeskImg['height'];?>"
                        width="<?php echo $homeWhyChooseDeskImg['width'];?>"
                        loading="lazy"
                     />
              </div>
            </div>
            <?php if($homeWhyChooseDescription){ ?>
              <div class="col-lg-6">

                <div class="text_wrapper">
                  <?php echo $homeWhyChooseDescription; ?>
                </div>

              </div>
            <?php } ?>
          </div>
        </div>
      </section>