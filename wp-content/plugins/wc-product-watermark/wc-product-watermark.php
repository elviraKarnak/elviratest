<?php
/**
 * Plugin Name: WC Product Image Watermark
 * Plugin URI: https://elvirainfotech.com/
 * Description: Adds watermark to WooCommerce product main image and gallery images. Class-based. Provides admin settings to upload watermark, set position, opacity and scale.
 * Version:     1.0.0
 * Author:      Raihan Reza
 * Author URI:  https://elvirainfotech.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: wc-product-watermark
 *
 * @package WCProductWatermark
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WC_Product_Watermark' ) ) {
    class WC_Product_Watermark {
        private static $instance = null;
        private $option_name = 'wc_pw_settings';
        private $settings = array();

        public static function instance() {
            if ( self::$instance == null ) {
                self::$instance = new self;
                self::$instance->init();
            }
            return self::$instance;
        }

        private function init() {
            $defaults = array(
                'enabled' => 1,
                'watermark_id' => 0,
                'position' => 'bottom-right',
                'opacity' => 60,
                'scale' => 20,
                'apply_gallery' => 1,
            );
            $this->settings = wp_parse_args( get_option($this->option_name, array()), $defaults );

            add_action('admin_menu', array($this,'admin_menu'));
            add_action('admin_init', array($this,'register_settings'));
            add_action('admin_enqueue_scripts', array($this,'admin_scripts'));

            // Filter images on frontend
            add_filter('wp_get_attachment_image_src', array($this,'maybe_watermark_image'), 10, 4);
        }

        public function admin_menu(){
            add_submenu_page('woocommerce','Product Image Watermark','Image Watermark','manage_options','wc-product-watermark',array($this,'settings_page'));
        }

        public function admin_scripts($hook){
            // only load on plugin page
            if ( strpos($hook,'wc-product-watermark') === false && $hook != 'woocommerce_page_wc-product-watermark' ) return;
            wp_enqueue_media();
            wp_enqueue_script('wc-pw-admin', plugins_url('assets/js/admin.js', __FILE__), array('jquery'), '1.0', true);
        }

        public function register_settings(){
            register_setting($this->option_name, $this->option_name, array($this,'sanitize_settings'));
        }

        public function sanitize_settings($input){
            $out = array();
            $out['enabled'] = !empty($input['enabled'])?1:0;
            $out['watermark_id'] = isset($input['watermark_id'])?intval($input['watermark_id']):0;
            $out['position'] = in_array(@$input['position'], array('top-left','top-right','center','bottom-left','bottom-right'))?esc_attr($input['position']):'bottom-right';
            $out['opacity'] = isset($input['opacity'])?max(0,min(100,intval($input['opacity']))):60;
            $out['scale'] = isset($input['scale'])?max(1,min(100,intval($input['scale']))):20;
            $out['apply_gallery'] = !empty($input['apply_gallery'])?1:0;
            return $out;
        }

        public function settings_page() {
            $s    = $this->settings;
            $opts = $this->option_name; // e.g. 'wc_pw_settings'
            ?>
            <div class="wrap">
                <h1><?php echo esc_html__( 'WC Product Image Watermark', 'wc-product-watermark' ); ?></h1>
                <form method="post" action="options.php">
                    <?php settings_fields( $this->option_name ); ?>
                    <table class="form-table">

                        <tr>
                            <th><?php echo esc_html__( 'Enable watermark', 'wc-product-watermark' ); ?></th>
                            <td>
                                <input
                                    type="checkbox"
                                    name="<?php echo esc_attr( $opts ); ?>[enabled]"
                                    value="1"
                                    <?php checked( 1, isset( $s['enabled'] ) ? $s['enabled'] : 0 ); ?> />
                            </td>
                        </tr>

                        <tr>
                            <th><?php echo esc_html__( 'Watermark image', 'wc-product-watermark' ); ?></th>
                            <td>
                                <input
                                    id="wc_pw_watermark_id"
                                    type="hidden"
                                    name="<?php echo esc_attr( $opts ); ?>[watermark_id]"
                                    value="<?php echo esc_attr( isset( $s['watermark_id'] ) ? $s['watermark_id'] : '' ); ?>" />

                                <div id="wc_pw_watermark_preview" style="margin-bottom:10px;">
                                    <?php
                                    if ( ! empty( $s['watermark_id'] ) ) {
                                        // wp_get_attachment_image returns safe markup, sanitize again with wp_kses_post
                                        echo wp_kses_post( wp_get_attachment_image( intval( $s['watermark_id'] ), array( 120, 120 ) ) );
                                    }
                                    ?>
                                </div>

                                <button class="button" id="wc_pw_select_watermark"><?php echo esc_html__( 'Select watermark', 'wc-product-watermark' ); ?></button>
                                <button class="button" id="wc_pw_remove_watermark"><?php echo esc_html__( 'Remove', 'wc-product-watermark' ); ?></button>
                            </td>
                        </tr>

                        <tr>
                            <th><?php echo esc_html__( 'Position', 'wc-product-watermark' ); ?></th>
                            <td>
                                <select name="<?php echo esc_attr( $opts ); ?>[position]">
                                    <?php
                                    $positions = array(
                                        'top-left'     => __( 'Top Left', 'wc-product-watermark' ),
                                        'top-right'    => __( 'Top Right', 'wc-product-watermark' ),
                                        'center'       => __( 'Center', 'wc-product-watermark' ),
                                        'bottom-left'  => __( 'Bottom Left', 'wc-product-watermark' ),
                                        'bottom-right' => __( 'Bottom Right', 'wc-product-watermark' ),
                                    );

                                    foreach ( $positions as $k => $label ) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr( $k ),
                                            selected( isset( $s['position'] ) ? $s['position'] : '', $k, false ),
                                            esc_html( $label )
                                        );
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th><?php echo esc_html__( 'Opacity (0-100)', 'wc-product-watermark' ); ?></th>
                            <td>
                                <input
                                    type="number"
                                    name="<?php echo esc_attr( $opts ); ?>[opacity]"
                                    value="<?php echo esc_attr( isset( $s['opacity'] ) ? $s['opacity'] : 60 ); ?>"
                                    min="0" max="100" />
                            </td>
                        </tr>

                        <tr>
                            <th><?php echo esc_html__( 'Scale (% of image width)', 'wc-product-watermark' ); ?></th>
                            <td>
                                <input
                                    type="number"
                                    name="<?php echo esc_attr( $opts ); ?>[scale]"
                                    value="<?php echo esc_attr( isset( $s['scale'] ) ? $s['scale'] : 20 ); ?>"
                                    min="1" max="100" />
                            </td>
                        </tr>

                        <tr>
                            <th><?php echo esc_html__( 'Apply to gallery images', 'wc-product-watermark' ); ?></th>
                            <td>
                                <input
                                    type="checkbox"
                                    name="<?php echo esc_attr( $opts ); ?>[apply_gallery]"
                                    value="1"
                                    <?php checked( 1, isset( $s['apply_gallery'] ) ? $s['apply_gallery'] : 0 ); ?> />
                            </td>
                        </tr>

                    </table>

                    <?php submit_button(); ?>

                </form>

                <p>
                    <strong><?php echo esc_html__( 'Notes:', 'wc-product-watermark' ); ?></strong>
                    <?php echo esc_html__( 'This plugin generates cached watermarked images under', 'wc-product-watermark' ); ?>
                    <code><?php echo esc_html( 'wp-content/uploads/wc-product-watermarks/' ); ?></code>.
                    <?php echo esc_html__( 'Make sure the GD PHP extension is enabled on your server.', 'wc-product-watermark' ); ?>
                </p>
            </div>
            <?php
        }

        public function maybe_watermark_image($image, $attachment_id, $size, $icon){
            // don't run in admin
            if ( is_admin() ) return $image;
            if ( empty($this->settings['enabled']) ) return $image;
            if ( empty($this->settings['watermark_id']) ) return $image;

            // Only apply for product contexts: single product page or product loops
            $apply = false;
            if ( function_exists('is_product') && ( is_product() || is_shop() || is_product_category() || is_product_tag() ) ) {
                $apply = true;
            }
            // also apply when attachment belongs to a product
            $parent = get_post_field('post_parent', $attachment_id);
            if ( $parent && get_post_type($parent) == 'product' ) $apply = true;
            if ( ! $apply ) return $image;

            // If apply_gallery disabled, only process the product's featured image (post_thumbnail)
            if ( empty($this->settings['apply_gallery']) ) {
                $post = get_post();
                if ( $post && get_post_thumbnail_id($post->ID) != $attachment_id ) {
                    return $image;
                }
            }

            $orig_url = $image[0];
            $cached = $this->get_cached_image_url($attachment_id, $orig_url, $size);
            if ( $cached ) {
                // attempt to read dimensions
                $dims = $this->get_image_dimensions_from_path( str_replace( wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $cached ) );
                $w = $dims[0]?:$image[1];
                $h = $dims[1]?:$image[2];
                return array($cached, $w, $h, $image[3]);
            }

            return $image;
        }

        private function get_cached_image_url($attachment_id, $orig_url, $size){
            $upload_dir = wp_upload_dir();
            $cache_dir = trailingslashit( $upload_dir['basedir'] ) . 'wc-product-watermarks/';
            if ( ! file_exists( $cache_dir ) ) {
                wp_mkdir_p( $cache_dir );
            }
            $watermark_id = $this->settings['watermark_id'];
            $pos = $this->settings['position'];
            $opacity = $this->settings['opacity'];
            $scale = $this->settings['scale'];
            $key = md5($attachment_id . '|' . $orig_url . '|' . $watermark_id . '|' . $pos . '|' . $opacity . '|' . $scale);
            $path_component = wp_parse_url( $orig_url, PHP_URL_PATH );
            $ext = pathinfo( $path_component ? $path_component : $orig_url, PATHINFO_EXTENSION );
            if (!$ext) $ext = 'jpg';
            $cached_file = $cache_dir . $key . '.' . $ext;
            $cached_url = trailingslashit( $upload_dir['baseurl'] ) . 'wc-product-watermarks/' . $key . '.' . $ext;

            if ( file_exists($cached_file) ) return $cached_url;

            $watermark_path = get_attached_file($watermark_id);
            if ( !file_exists($watermark_path) ) return false;

            $orig_path = get_attached_file($attachment_id);
            if ( !file_exists($orig_path) ) {
                // try to get remote copy
                $resp = wp_remote_get($orig_url);
                if ( is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200 ) return false;
                $body = wp_remote_retrieve_body($resp);
                file_put_contents($cached_file . '.tmp', $body);
                $orig_tmp = $cached_file . '.tmp';
                $ok = $this->create_watermarked_file($orig_tmp, $watermark_path, $cached_file, $pos, $opacity, $scale);
                wp_delete_file( $orig_tmp );
                if ( $ok ) return $cached_url;
                return false;
            } else {
                $ok = $this->create_watermarked_file($orig_path, $watermark_path, $cached_file, $pos, $opacity, $scale);
                if ( $ok ) return $cached_url;
                return false;
            }
        }

        private function get_image_dimensions_from_path($path){
            if ( file_exists($path) ) {
                $size = getimagesize($path);
                if ($size) return array($size[0], $size[1]);
            }
            return array(0,0);
        }

        private function create_watermarked_file($orig_path, $watermark_path, $dest_path, $position, $opacity, $scale_percent){
            if ( ! function_exists('imagecreatetruecolor') ) return false;

            $orig_info = @getimagesize($orig_path);
            $water_info = @getimagesize($watermark_path);
            if ( !$orig_info || !$water_info ) return false;

            $orig_mime = $orig_info['mime'];
            $water_mime = $water_info['mime'];

            $orig_img = $this->image_create_from_type($orig_path, $orig_mime);
            $water_img = $this->image_create_from_type($watermark_path, $water_mime);

            if ( !$orig_img || !$water_img ) return false;

            $orig_w = imagesx($orig_img);
            $orig_h = imagesy($orig_img);

            $target_water_w = max(1, intval($orig_w * ($scale_percent/100)));
            $ratio = imagesy($water_img)/imagesx($water_img);
            $target_water_h = max(1, intval($target_water_w * $ratio));

            $resized_water = imagecreatetruecolor($target_water_w, $target_water_h);
            imagealphablending($resized_water, false);
            imagesavealpha($resized_water, true);
            $transparent = imagecolorallocatealpha($resized_water, 0,0,0,127);
            imagefill($resized_water,0,0,$transparent);
            imagecopyresampled($resized_water, $water_img, 0,0,0,0, $target_water_w, $target_water_h, imagesx($water_img), imagesy($water_img));

            // apply opacity (0..100)
            $opacity = max(0,min(100,intval($opacity)));
            if ( $opacity < 100 ) {
                $resized_water = $this->image_opacity($resized_water, $opacity/100);
            }

            // compute position
            switch($position){
                case 'top-left': $dst_x = 10; $dst_y = 10; break;
                case 'top-right': $dst_x = $orig_w - $target_water_w - 10; $dst_y = 10; break;
                case 'center': $dst_x = intval(($orig_w - $target_water_w)/2); $dst_y = intval(($orig_h - $target_water_h)/2); break;
                case 'bottom-left': $dst_x = 10; $dst_y = $orig_h - $target_water_h - 10; break;
                default: $dst_x = $orig_w - $target_water_w - 10; $dst_y = $orig_h - $target_water_h - 10; break;
            }

            // preserve orig transparency/color
            $output = imagecreatetruecolor($orig_w, $orig_h);
            imagealphablending($output, false);
            imagesavealpha($output, true);
            $transparent_bg = imagecolorallocatealpha($output, 0,0,0,127);
            imagefill($output,0,0,$transparent_bg);
            imagecopy($output, $orig_img, 0,0,0,0,$orig_w,$orig_h);

            // copy watermark onto output (alpha-aware)
            imagecopy($output, $resized_water, $dst_x, $dst_y, 0,0, $target_water_w, $target_water_h);

            // save
            $ext = strtolower(pathinfo($dest_path, PATHINFO_EXTENSION));
            if ( $ext === 'png' ) {
                imagepng($output, $dest_path);
            } else {
                imagejpeg($output, $dest_path, 90);
            }

            imagedestroy($orig_img);
            imagedestroy($water_img);
            imagedestroy($resized_water);
            imagedestroy($output);
            return file_exists($dest_path);
        }

        private function image_create_from_type($file, $mime){
            switch($mime){
                case 'image/jpeg':
                case 'image/jpg': return @imagecreatefromjpeg($file);
                case 'image/png': return @imagecreatefrompng($file);
                case 'image/gif': return @imagecreatefromgif($file);
                default: return @imagecreatefromstring(file_get_contents($file));
            }
        }

        private function image_opacity($img, $opacity){
            if ($opacity >= 1) return $img;
            $w = imagesx($img);
            $h = imagesy($img);
            $tmp = imagecreatetruecolor($w, $h);
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);
            $trans = imagecolorallocatealpha($tmp, 0,0,0,127);
            imagefill($tmp,0,0,$trans);

            for($x=0;$x<$w;$x++){
                for($y=0;$y<$h;$y++){
                    $rgba = imagecolorat($img,$x,$y);
                    $a = ($rgba & 0x7F000000) >> 24;
                    // convert to 0..127 alpha
                    $orig_a = ($a);
                    $c = imagecolorsforindex($img, imagecolorat($img,$x,$y));
                    // compute new alpha
                    $new_a = intval(127 - (127 - $orig_a) * $opacity);
                    $col = imagecolorallocatealpha($tmp, $c['red'], $c['green'], $c['blue'], $new_a);
                    imagesetpixel($tmp, $x, $y, $col);
                }
            }
            return $tmp;
        }
    }

    add_action('plugins_loaded', array('WC_Product_Watermark','instance'));
}
