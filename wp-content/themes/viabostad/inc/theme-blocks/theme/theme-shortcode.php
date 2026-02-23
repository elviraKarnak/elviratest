<?php 
    $shortcodeTheme = get_field('shortcode_theme');
    $title = get_field('page_title_block');
    
?>

<?php if(!empty($shortcodeTheme)){ ?>
    <section class="default-content-defult-pages pt_100 pb_100">
        <div class="container">
            <?php if($title){ ?>
                <div class="page_heading">
                    <h1 class="sec_hdng"><?php echo $title; ?></h1>
                </div>
            <?php } ?>
        <?php echo do_shortcode($shortcodeTheme); ?>
        </div>
    </section>
<?php } ?>