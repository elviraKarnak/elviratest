<?php 

add_filter('block_categories_all', function ($categories) {

    return array_merge(
        $categories,
        [
            [
                'slug'  => 'home-sections',
                'title' => __('Home Sections', 'viabosted'),
                'icon'  => 'admin-home',
            ],
             [
                'slug'  => 'theme-sections',
                'title' => __('Theme Sections', 'viabosted'),
                'icon'  => 'admin-home',
            ],
        ]
    );

});



add_action('acf/init', function () {

    $blocks = [
        'banner'      => 'Home Banner',
        'property'    => 'Home Property',
        'about'       => 'Home About',
        'rooms'       => 'Home Rooms',
        'why-choose'  => 'Home Why Choose',
        'pricing'     => 'Home Pricing',
        'news'        => 'Home News',
    ];

    $themeBlocks = [
        'inner-banner'       => 'Inner Banner',
        'contact-box'        => 'Contact Form',
        'user-registration'  => 'User Registration',
        'broker-listing'     => 'Broker Listing',  
        'theme-shortcode'    => 'Theme Shortcode',  
        'saved-property'     => 'Saved Property'    
    ];

    foreach ($blocks as $slug => $title) {

        acf_register_block_type([
            'name'            => $slug,
            'title'           => $title,
            'render_template' => get_template_directory() . "/inc/theme-blocks/home/{$slug}.php",
            'category'        => 'home-sections',
            'icon'            => 'block-default',
            'mode'            => 'preview',
            'supports'        => [
                'align'  => true,
                'anchor'=> true,
            ],
        ]);
    }

     foreach ($themeBlocks as $slug => $title) {

        acf_register_block_type([
            'name'            => $slug,
            'title'           => $title,
            'render_template' => get_template_directory() . "/inc/theme-blocks/theme/{$slug}.php",
            'category'        => 'theme-sections',
            'icon'            => 'block-default',
            'mode'            => 'preview',
            'supports'        => [
                'align'  => true,
                'anchor'=> true,
            ],
        ]);
    }

});