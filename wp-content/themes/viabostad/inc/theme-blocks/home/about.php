<?php 

$homeAboutTitle = get_field('title_hab');
$homeAboutDescription = get_field('description_hsb');
$homeAboutDeskImg = get_field('section_image');



?>
    <section class="explore_dream_property">
        <div class="container">
            <div class="row gy-4">
            <div class="col-lg-6">
                <div class="img_wrapper">
                     <img
                        src="<?php echo $homeAboutDeskImg['url'];?>" 
                        alt="<?php echo $homeAboutDeskImg['alt'];?>"
                        height="<?php echo $homeAboutDeskImg['height'];?>"
                        width="<?php echo $homeAboutDeskImg['width'];?>"
                        loading="lazy"
                     />
                </div>
            </div>
            <div class="col-lg-6">
                <div class="text_wrapper">
                 <?php if($homeAboutTitle){ ?>   
                <h2 class="sec_hdng">
                   <?php echo $homeAboutTitle; ?>
                </h2>
                <?php } ?>
                <?php if($homeAboutDescription){ echo $homeAboutDescription; }?>
                </div>
            </div>
        </div>
    </div>
</section>