<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_VERSION', '3.4.5' );
define( 'EHP_THEME_SLUG', 'hello-elementor' );

define( 'HELLO_THEME_PATH', get_template_directory() );
define( 'HELLO_THEME_URL', get_template_directory_uri() );
define( 'HELLO_THEME_ASSETS_PATH', HELLO_THEME_PATH . '/assets/' );
define( 'HELLO_THEME_ASSETS_URL', HELLO_THEME_URL . '/assets/' );
define( 'HELLO_THEME_SCRIPTS_PATH', HELLO_THEME_ASSETS_PATH . 'js/' );
define( 'HELLO_THEME_SCRIPTS_URL', HELLO_THEME_ASSETS_URL . 'js/' );
define( 'HELLO_THEME_STYLE_PATH', HELLO_THEME_ASSETS_PATH . 'css/' );
define( 'HELLO_THEME_STYLE_URL', HELLO_THEME_ASSETS_URL . 'css/' );
define( 'HELLO_THEME_IMAGES_PATH', HELLO_THEME_ASSETS_PATH . 'images/' );
define( 'HELLO_THEME_IMAGES_URL', HELLO_THEME_ASSETS_URL . 'images/' );

if ( ! isset( $content_width ) ) {
	$content_width = 800; // Pixels.
}

if ( ! function_exists( 'hello_elementor_setup' ) ) {
	/**
	 * Set up theme support.
	 *
	 * @return void
	 */
	function hello_elementor_setup() {
		if ( is_admin() ) {
			hello_maybe_update_theme_version_in_db();
		}

		if ( apply_filters( 'hello_elementor_register_menus', true ) ) {
			register_nav_menus( [ 'menu-1' => esc_html__( 'Header', 'hello-elementor' ) ] );
			register_nav_menus( [ 'menu-2' => esc_html__( 'Footer', 'hello-elementor' ) ] );
		}

		if ( apply_filters( 'hello_elementor_post_type_support', true ) ) {
			add_post_type_support( 'page', 'excerpt' );
		}

		if ( apply_filters( 'hello_elementor_add_theme_support', true ) ) {
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'automatic-feed-links' );
			add_theme_support( 'title-tag' );
			add_theme_support(
				'html5',
				[
					'search-form',
					'comment-form',
					'comment-list',
					'gallery',
					'caption',
					'script',
					'style',
					'navigation-widgets',
				]
			);
			add_theme_support(
				'custom-logo',
				[
					'height'      => 100,
					'width'       => 350,
					'flex-height' => true,
					'flex-width'  => true,
				]
			);
			add_theme_support( 'align-wide' );
			add_theme_support( 'responsive-embeds' );

			/*
			 * Editor Styles
			 */
			add_theme_support( 'editor-styles' );
			add_editor_style( 'assets/css/editor-styles.css' );

			/*
			 * WooCommerce.
			 */
			if ( apply_filters( 'hello_elementor_add_woocommerce_support', true ) ) {
				// WooCommerce in general.
				add_theme_support( 'woocommerce' );
				// Enabling WooCommerce product gallery features (are off by default since WC 3.0.0).
				// zoom.
				add_theme_support( 'wc-product-gallery-zoom' );
				// lightbox.
				add_theme_support( 'wc-product-gallery-lightbox' );
				// swipe.
				add_theme_support( 'wc-product-gallery-slider' );
			}
		}
	}
}
add_action( 'after_setup_theme', 'hello_elementor_setup' );

function hello_maybe_update_theme_version_in_db() {
	$theme_version_option_name = 'hello_theme_version';
	// The theme version saved in the database.
	$hello_theme_db_version = get_option( $theme_version_option_name );

	// If the 'hello_theme_version' option does not exist in the DB, or the version needs to be updated, do the update.
	if ( ! $hello_theme_db_version || version_compare( $hello_theme_db_version, HELLO_ELEMENTOR_VERSION, '<' ) ) {
		update_option( $theme_version_option_name, HELLO_ELEMENTOR_VERSION );
	}
}

if ( ! function_exists( 'hello_elementor_display_header_footer' ) ) {
	/**
	 * Check whether to display header footer.
	 *
	 * @return bool
	 */
	function hello_elementor_display_header_footer() {
		$hello_elementor_header_footer = true;

		return apply_filters( 'hello_elementor_header_footer', $hello_elementor_header_footer );
	}
}

if ( ! function_exists( 'hello_elementor_scripts_styles' ) ) {
	/**
	 * Theme Scripts & Styles.
	 *
	 * @return void
	 */
	function hello_elementor_scripts_styles() {
		if ( apply_filters( 'hello_elementor_enqueue_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor',
				HELLO_THEME_STYLE_URL . 'reset.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( apply_filters( 'hello_elementor_enqueue_theme_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor-theme-style',
				HELLO_THEME_STYLE_URL . 'theme.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( hello_elementor_display_header_footer() ) {
			wp_enqueue_style(
				'hello-elementor-header-footer',
				HELLO_THEME_STYLE_URL . 'header-footer.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_scripts_styles' );

if ( ! function_exists( 'hello_elementor_register_elementor_locations' ) ) {
	/**
	 * Register Elementor Locations.
	 *
	 * @param ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager theme manager.
	 *
	 * @return void
	 */
	function hello_elementor_register_elementor_locations( $elementor_theme_manager ) {
		if ( apply_filters( 'hello_elementor_register_elementor_locations', true ) ) {
			$elementor_theme_manager->register_all_core_location();
		}
	}
}
add_action( 'elementor/theme/register_locations', 'hello_elementor_register_elementor_locations' );

if ( ! function_exists( 'hello_elementor_content_width' ) ) {
	/**
	 * Set default content width.
	 *
	 * @return void
	 */
	function hello_elementor_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'hello_elementor_content_width', 800 );
	}
}
add_action( 'after_setup_theme', 'hello_elementor_content_width', 0 );

if ( ! function_exists( 'hello_elementor_add_description_meta_tag' ) ) {
	/**
	 * Add description meta tag with excerpt text.
	 *
	 * @return void
	 */
	function hello_elementor_add_description_meta_tag() {
		if ( ! apply_filters( 'hello_elementor_description_meta_tag', true ) ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( empty( $post->post_excerpt ) ) {
			return;
		}

		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $post->post_excerpt ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'hello_elementor_add_description_meta_tag' );

// Settings page
require get_template_directory() . '/includes/settings-functions.php';

// Header & footer styling option, inside Elementor
require get_template_directory() . '/includes/elementor-functions.php';

if ( ! function_exists( 'hello_elementor_customizer' ) ) {
	// Customizer controls
	function hello_elementor_customizer() {
		if ( ! is_customize_preview() ) {
			return;
		}

		if ( ! hello_elementor_display_header_footer() ) {
			return;
		}

		require get_template_directory() . '/includes/customizer-functions.php';
	}
}
add_action( 'init', 'hello_elementor_customizer' );

if ( ! function_exists( 'hello_elementor_check_hide_title' ) ) {
	/**
	 * Check whether to display the page title.
	 *
	 * @param bool $val default value.
	 *
	 * @return bool
	 */
	function hello_elementor_check_hide_title( $val ) {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$current_doc = Elementor\Plugin::instance()->documents->get( get_the_ID() );
			if ( $current_doc && 'yes' === $current_doc->get_settings( 'hide_title' ) ) {
				$val = false;
			}
		}
		return $val;
	}
}
add_filter( 'hello_elementor_page_title', 'hello_elementor_check_hide_title' );

/**
 * BC:
 * In v2.7.0 the theme removed the `hello_elementor_body_open()` from `header.php` replacing it with `wp_body_open()`.
 * The following code prevents fatal errors in child themes that still use this function.
 */
if ( ! function_exists( 'hello_elementor_body_open' ) ) {
	function hello_elementor_body_open() {
		wp_body_open();
	}
}

require HELLO_THEME_PATH . '/theme.php';

HelloTheme\Theme::instance();


/**
 * Christmas Gift Voucher Popup - All-in-One Solution
 * Add this complete code to your theme's functions.php file
 * Popup appears only on homepage after 1 second
 */

// Inject inline CSS
function christmas_popup_inline_styles() {
    if (!is_front_page()) {
        return;
    }
    ?>
    <style>
        /* Christmas Popup Styles */
        .christmas-popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 999999;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease-in-out;
        }

        .christmas-popup-overlay.active {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .christmas-popup-container {
            position: relative;
            background: white;
            border-radius: 20px;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(117, 76, 134, 0.3);
            animation: slideUp 0.4s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .christmas-popup-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid white;
            font-size: 28px;
            font-weight: bold;
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 0;
            padding: 0;
        }

        .christmas-popup-close:hover {
            background: rgba(0, 0, 0, 0.7);
            transform: rotate(90deg);
            border-color: #FFD700;
        }

        .christmas-popup-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 500px;
        }

        .christmas-popup-left {
            background: linear-gradient(135deg, #754C86 0%, #9d6fb5 100%);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .christmas-popup-left::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: movePattern 20s linear infinite;
        }

        @keyframes movePattern {
            0% { transform: translate(0, 0); }
            100% { transform: translate(30px, 30px); }
        }

        .ribbon {
            position: absolute;
            top: 10px;
            left: -5px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #754C86;
            padding: 8px 25px 8px 15px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 5;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 50%, calc(100% - 12px) 100%, 0 100%);
        }

        .ribbon::before {
            content: '';
            position: absolute;
            left: 0;
            bottom: -6px;
            width: 0;
            height: 0;
            border-left: 5px solid #B8860B;
            border-top: 6px solid #B8860B;
            border-bottom: 0px solid transparent;
        }

        .gift-icon {
            margin-top: 20px;
            animation: bounce 2s ease-in-out infinite;
            z-index: 2;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .popup-description {
            font-size: 36px;
            margin: 0 0 15px 0;
            opacity: 1;
            z-index: 2;
            font-weight: 700;
            font-family: 'Georgia', 'Times New Roman', serif;
            font-style: italic;
            color: #FFD700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            line-height: 1.3;
        }

        .popup-link {
            color: white;
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            border-bottom: 2px solid white;
            transition: all 0.3s ease;
            z-index: 2;
            display: inline-block;
            margin-bottom: 10px;
        }

        .popup-link:hover {
            color: #FFD700;
            border-bottom-color: #FFD700;
        }

        .voucher-title {
            font-size: 32px;
            font-weight: 300;
            margin: 30px 0 5px 0;
            letter-spacing: 2px;
            text-transform: uppercase;
            z-index: 2;
            color: white;
        }

        .voucher-amount {
            font-size: 80px;
            font-weight: 700;
            margin: 10px 0;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.3);
            z-index: 2;
            color: #FFD700;
            line-height: 1;
        }

        .voucher-subtitle {
            font-size: 32px;
            font-weight: 700;
            margin: 5px 0 20px 0;
            letter-spacing: 3px;
            text-transform: uppercase;
            z-index: 2;
            color: white;
        }

        .decorative-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
        }

        .sparkle {
            position: absolute;
            font-size: 24px;
            animation: sparkle 2s ease-in-out infinite;
        }

        .sparkle-1 {
            top: 15%;
            left: 10%;
            animation-delay: 0s;
        }

        .sparkle-2 {
            top: 70%;
            right: 15%;
            animation-delay: 0.7s;
        }

        .sparkle-3 {
            bottom: 20%;
            left: 20%;
            animation-delay: 1.4s;
        }

        @keyframes sparkle {
            0%, 100% {
                opacity: 0.3;
                transform: scale(1);
            }
            50% {
                opacity: 1;
                transform: scale(1.2);
            }
        }

        .christmas-popup-right {
            padding: 50px 40px;
            background: #f8f9fa;
            overflow-y: auto;
            position: relative;
        }

        .right-logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .right-popup-logo {
            max-width: 180px;
            height: auto;
            display: inline-block;
        }

        .form-heading {
            font-size: 24px;
            color: #754C86;
            margin: 0 0 25px 0;
            font-weight: 600;
            text-align: center;
        }

        .form-container {
            max-width: 100%;
        }

        @media (max-width: 768px) {
            .christmas-popup-container {
                width: 95%;
                max-height: 95vh;
                border-radius: 15px;
            }
            
            .christmas-popup-content {
                grid-template-columns: 1fr;
                min-height: auto;
            }
            
            .christmas-popup-left {
                padding: 40px 30px;
                min-height: auto;
                border-radius: 15px 15px 0 0;
            }
            
            .christmas-popup-right {
                padding: 30px 25px;
                border-radius: 0 0 15px 15px;
            }

            .popup-description {
                font-size: 26px;
            }

            .voucher-title {
                font-size: 24px;
            }

            .voucher-amount {
                font-size: 60px;
            }

            .voucher-subtitle {
                font-size: 24px;
            }

            .ribbon {
                display: none;
            }

            .right-popup-logo {
                max-width: 140px;
            }
        }

        @media (max-width: 480px) {
            .christmas-popup-left {
                padding: 30px 20px;
                border-radius: 15px 15px 0 0;
            }
            
            .popup-description {
                font-size: 22px;
            }
            
            .voucher-title {
                font-size: 20px;
            }
            
            .voucher-amount {
                font-size: 50px;
            }
            
            .voucher-subtitle {
                font-size: 20px;
            }

            .ribbon {
                display: none;
            }

            .right-popup-logo {
                max-width: 120px;
            }
            
            .christmas-popup-right {
                padding: 25px 20px;
                border-radius: 0 0 15px 15px;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'christmas_popup_inline_styles');

// Inject inline JavaScript
function christmas_popup_inline_script() {
    if (!is_front_page()) {
        return;
    }
    ?>
    <script>
        jQuery(document).ready(function($) {
            const popupShown = sessionStorage.getItem('christmasPopupShown');
            
            if (!popupShown) {
                setTimeout(function() {
                    $('#christmasPopupOverlay').addClass('active');
                    $('body').css('overflow', 'hidden');
                }, 1000);
            }
            
            $('.christmas-popup-close').on('click', function() {
                closePopup();
            });
            
            $('#christmasPopupOverlay').on('click', function(e) {
                if ($(e.target).is('#christmasPopupOverlay')) {
                    closePopup();
                }
            });
            
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#christmasPopupOverlay').hasClass('active')) {
                    closePopup();
                }
            });
            
            function closePopup() {
                $('#christmasPopupOverlay').removeClass('active');
                $('body').css('overflow', '');
                sessionStorage.setItem('christmasPopupShown', 'true');
            }
            
            $(document).on('fluentform_submission_success', function() {
                setTimeout(function() {
                    closePopup();
                }, 2000);
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'christmas_popup_inline_script');

// Auto-inject popup HTML on homepage
function christmas_popup_html() {
    if (!is_front_page()) {
        return;
    }
    ?>
    <div id="christmasPopupOverlay" class="christmas-popup-overlay">
        <div class="christmas-popup-container">
            <button class="christmas-popup-close" aria-label="Close popup">&times;</button>
            
            <div class="christmas-popup-content">
                <div class="christmas-popup-left">
                    <div class="ribbon">Limited Offer</div>
                    <p class="popup-description">Get ready this <br>New Year with</p>
                    <a href="https://www.vitalityhypnosisperth.com.au" class="popup-link" target="_blank">
                        www.vitalityhypnosisperth.com.au
                    </a>
                    
                    <h2 class="voucher-title">Get Your</h2>
                    <div class="voucher-amount">$50</div>
                    <h3 class="voucher-subtitle">Gift Voucher</h3>
                    
                    <div class="gift-icon">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 12V22H4V12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 7H2V12H22V7Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 22V7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 7H7.5C6.83696 7 6.20107 6.73661 5.73223 6.26777C5.26339 5.79893 5 5.16304 5 4.5C5 3.83696 5.26339 3.20107 5.73223 2.73223C6.20107 2.26339 6.83696 2 7.5 2C11 2 12 7 12 7Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 7H16.5C17.163 7 17.7989 6.73661 18.2678 6.26777C18.7366 5.79893 19 5.16304 19 4.5C19 3.83696 18.7366 3.20107 18.2678 2.73223C17.7989 2.26339 17.163 2 16.5 2C13 2 12 7 12 7Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    
                    <div class="decorative-elements">
                        <span class="sparkle sparkle-1">✨</span>
                        <span class="sparkle sparkle-2">✨</span>
                        <span class="sparkle sparkle-3">✨</span>
                    </div>
                </div>
                
                <div class="christmas-popup-right">
                    <div class="right-logo-container">
                        <img src="https://vitalityhypnosisperth.com.au/wp-content/uploads/2025/05/logo-2.png" alt="Vitality Hypnosis Perth" class="right-popup-logo">
                    </div>
                    <h4 class="form-heading">Claim Your Voucher</h4>
                    <div class="form-container">
                        [fluentform id="4"]
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
//add_action('wp_footer', 'christmas_popup_html');