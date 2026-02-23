<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Realisations_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'realisations';
    }

    public function get_title() {
        return __('Realisations', 'realisations-elementor');
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return ['general'];
    }

    public function get_keywords() {
        return ['realisations', 'portfolio', 'gallery', 'projects'];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'realisations-elementor'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Get all categories
        $categories = get_terms([
            'taxonomy' => 'categorie_de_realisations',
            'hide_empty' => false,
        ]);

        $category_options = ['' => __('All Categories', 'realisations-elementor')];
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $category_options[$category->slug] = $category->name;
            }
        }

        $this->add_control(
            'category',
            [
                'label' => __('Category', 'realisations-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $category_options,
                'default' => '',
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label' => __('Posts Per Page', 'realisations-elementor'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 4,
                'min' => 1,
                'max' => 50,
            ]
        );

        $this->add_control(
            'column',
            [
                'label' => __('Columns', 'realisations-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    '1' => __('1 Column', 'realisations-elementor'),
                    '2' => __('2 Columns', 'realisations-elementor'),
                    '3' => __('3 Columns', 'realisations-elementor'),
                    '4' => __('4 Columns', 'realisations-elementor'),
                ],
                'default' => '2',
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'realisations-elementor'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'item_background',
            [
                'label' => __('Item Background Color', 'realisations-elementor'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#DCDBDB',
                'selectors' => [
                    '{{WRAPPER}} .realisation-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'image_height',
            [
                'label' => __('Image Height', 'realisations-elementor'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 200,
                        'max' => 800,
                    ],
                ],
                'default' => [
                    'size' => 450,
                ],
                'selectors' => [
                    '{{WRAPPER}} .realisation-item img' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'pagination_color',
            [
                'label' => __('Pagination Background', 'realisations-elementor'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333',
                'selectors' => [
                    '{{WRAPPER}} .realisations-pagination a, {{WRAPPER}} .realisations-pagination span' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'pagination_active_color',
            [
                'label' => __('Pagination Active Color', 'realisations-elementor'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#F36827',
                'selectors' => [
                    '{{WRAPPER}} .realisations-pagination .current' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        $column = intval($settings['column']);
        if ($column < 1) $column = 2;

        // Current page
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

        $args = array(
            'post_type'      => 'realisations',
            'posts_per_page' => intval($settings['posts_per_page']),
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if (!empty($settings['category'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'categorie_de_realisations',
                'field'    => 'slug',
                'terms'    => sanitize_title($settings['category']),
            );
        }

        $query = new WP_Query($args);

        if ($query->have_posts()) :
            ?>
            
            have_posts()) : $query->the_post();
                $thumb_url = get_the_post_thumbnail_url(get_the_ID(), 'large');
                $title     = get_the_title();
                $desc      = wp_strip_all_tags(get_the_content());
                $link      = get_permalink();
                ?>
                
                    
                        
                            
                        
                    
                    
                        
                            
                        
                    
                
            
            

            <?php
            // Pagination
            $big = 999999999;
            $pagination = paginate_links(array(
                'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format'    => '?paged=%#%',
                'current'   => max(1, $paged),
                'total'     => $query->max_num_pages,
                'type'      => 'array',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            ));

            if ($pagination) :
                echo '';
                foreach ($pagination as $page_link) {
                    echo $page_link;
                }
                echo '';
            endif;

        else :
            echo '' . __('No réalisations found in this category.', 'realisations-elementor') . '';
        endif;

        wp_reset_postdata();
    }
}