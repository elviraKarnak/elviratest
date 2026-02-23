<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

if ( ! defined( 'GRAVITY_API_URL' ) ) {
	define( 'GRAVITY_API_URL', 'https://gravityapi.com/wp-json/gravityapi/v1' );
}

// Update options only if not already set
if ( ! get_option( 'rg_gforms_key' ) ) {
    update_option( 'rg_gforms_key', 'D3559D4B-A81D006C-7FE55693-BC098E50' );
    update_option( 'gform_pending_installation', false );
    delete_option( 'rg_gforms_message' );
}

// Pre-constructed JSON response for licenses
$license_response = json_encode([
    "license_key_md5" => "d3559d4ba81d006c7fe55693bc098e50",
    "date_created" => "2024-11-19 00:00:00",
    "date_expires" => "2050-12-01 00:00:00",
    "renewal_date" => "2050-11-01 00:00:00",
    "is_active" => "1",
    "is_subscription_canceled" => "0",
    "product_code" => "GFELITE",
    "product_name" => "Gravity Forms Elite",
    "is_near_expiration" => false,
    "days_to_expire" => 9490,
    "is_expired" => false,
    "is_valid" => true,
    "is_past_renewal_period" => false,
    "is_legacy" => true,
    "is_perpetual" => false,
    "max_sites" => "unlimited",
    "active_sites" => 200,
    "remaining_seats" => "unlimited",
    "is_multisite_allowed" => true,
]);

// Handle requests to the license and message endpoints
add_filter('pre_http_request', function($preempt, $parsed_args, $url) use ($license_response) {

    // Check if it's a POST request to the license API endpoint
    if ($parsed_args['method'] === 'POST') {
        if ( strpos( $url, 'https://gravityapi.com/wp-json/gravityapi/v1/licenses/' ) === 0 ) {
            return [
                'headers' => [],
                'body' => $license_response,
                'response' => ['code' => 200, 'message' => 'OK']
            ];
        }

        // Check if it's a POST request to message.php endpoint
        if ( strpos( $url, 'https://gravityapi.com/wp-content/plugins/gravitymanager/message.php' ) === 0 ) {
            return [
                'headers' => [],
                'body' => '', // Return empty body for message.php
                'response' => ['code' => 200, 'message' => 'OK']
            ];
        }
    }

    // Return the original response if no conditions are met
    return $preempt;

}, 10, 3);

if ( ! class_exists( 'Gravity_Api' ) ) {

	/**
	 * Client-side API wrapper for interacting with the Gravity APIs.
	 *
	 * @package    Gravity Forms
	 * @subpackage Gravity_Api
	 * @since      1.9
	 * @access     public
	 */
	class Gravity_Api {

		private static $instance = null;

		private static $raw_response = null;

		public static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Retrieves site key and site secret key from remote API and stores them as WP options. Returns false if license key is invalid; otherwise, returns true.
		 *
		 * @since  2.3
		 *
		 * @param string $license_key License key to be registered.
		 * @param boolean $is_md5 Specifies if $license_key provided is an MD5 or unhashed license key.
		 *
		 * @return bool|WP_Error
		 */
		public function register_current_site( $license_key, $is_md5 = false ) {
			GFCommon::log_debug( __METHOD__ . '(): registering site' );

			$site_key = 'fake_site_key';
			$site_secret = 'fake_site_secret';

			update_option( 'gf_site_key', $site_key );
			update_option( 'gf_site_secret', $site_secret );

			GFCommon::log_debug( __METHOD__ . '(): site registration successful. Site Key: ' . $site_key );

			return true;
		}

		/**
		 * Updates license key for a site that has already been registered.
		 *
 transferring data to/from Gravity Forms API.
		 *
		 * @since  2.3
		 * @since  2.5 Returns License Response on success.
		 *
		 * @access public
		 *
		 * @param string $new_license_key_md5 Hash license key to be updated
		 *
		 * @return \Gravity_Forms\Gravity_Forms\License\GF_License_API_Response|WP_Error
		 */
		public function update_current_site( $new_license_key_md5 ) {
			GFCommon::log_debug( __METHOD__ . '(): refreshing license info' );

			$result = array(
				'license_key_md5' => $new_license_key_md5,
				'success' => true
			);

			return $result;
		}

		/***
		 * Removes a license key from a registered site. NOTE: It doesn't actually deregister the site.
		 *
		 * @deprecated Use gapi()->update_current_site('') instead.
		 *
		 * @return bool|WP_Error
		 */
		public function deregister_current_site() {
			GFCommon::log_debug( __METHOD__ . '(): deregistering' );

			return true;
		}

		/**
		 * Check the given license key to get its information from the API.
		 *
		 * @since 2.5
		 *
		 * @param string $key The license key.
		 *
		 * @return array|false|WP_Error
		 */
		public function check_license( $key ) {
			GFCommon::log_debug( __METHOD__ . '(): getting site and license info' );

			$response = array(
				"license_key_md5" => "d3559d4ba81d006c7fe55693bc098e50",
				"date_created" => "2024-11-19 00:00:00",
				"date_expires" => "2050-12-01 00:00:00",
				"renewal_date" => "2050-11-01 00:00:00",
				"is_active" => "1",
				"is_subscription_canceled" => "0",
				"product_code" => "GFELITE",
				"product_name" => "Gravity Forms Elite",
				"is_near_expiration" => false,
				"days_to_expire" => 9490,
				"is_expired" => false,
				"is_valid" => true,
				"is_past_renewal_period" => false,
				"is_legacy" => true,
				"is_perpetual" => false,
				"max_sites" => "unlimited",
				"active_sites" => 200,
				"remaining_seats" => "unlimited",
				"is_multisite_allowed" => true,
			);

			set_transient( 'rg_gforms_license', $response, DAY_IN_SECONDS );

			return $response;
		}

		/**
		 * Get GF core and add-on family information.
		 *
		 * @since 2.5
		 *
		 * @return false|array
		 */
		public function get_plugins_info() {
			return array(
				'offerings' => array(
					'gravityforms' => array(
						'version' => '2.9.8',
						'url' => 'https://gravityforms.com'
					)
				)
			);
		}

		/**
		 * Get version information from the Gravity Manager API.
		 *
		 * @since 2.5
		 *
		 * @param false $cache
		 *
		 * @return array
		 */
		private function get_version_info( $cache = false ) {
			$version_info = null;
			return $decoded = null;
		}

		/**
		 * Update the usage data (call version.php in Gravity Manager). We will replace it once we have statistics API endpoints.
		 *
		 * @since 2.5
		 */
		public function update_site_data() {
			GFCommon::log_debug( __METHOD__ . '(): site data updated successfully' );
		}

		public function send_email_to_hubspot( $email ) {
			GFCommon::log_debug( __METHOD__ . '(): Sending installation wizard to hubspot.' );

			return true;
		}

		// # HELPERS

		/**
		 * @return false|mixed|void
		 */
		public function get_key() {
			return 'D3559D4B-A81D006C-7FE55693-BC098E50';
		}

		/**
		 * @param $site_key
		 * @param $site_secret
		 *
		 * @return string[]
		 */
		private function get_site_auth_header( $site_key, $site_secret ) {
			return array();
		}

		/**
		 * @param $site_secret
		 *
		 * @return string[]
		 */
		private function get_license_info_header( $site_secret ) {
			return array();
		}

		/**
		 * @param $license_key_md5
		 *
		 * @return string[]
		 */
		private function get_license_auth_header( $license_key_md5 ) {
			return array();
		}

		/**
		 * Prepare response body.
		 *
		 * @since unknown
		 * @since 2.5     Support a WP_Error being returned.
		 * @since 2.5     Allow results to be returned as array with second param.
		 *
		 * @param WP_Error|WP_REST_Response $raw_response The API response.
		 * @param bool                      $as_array     Whether to return the response as an array or object.
		 *
		 * @return array|object|WP_Error
		 */
		public function prepare_response_body( $raw_response, $as_array = false ) {
			$response = array(
				'key' => 'fake_site_key',
				'secret' => 'fake_site_secret',
				'success' => true
			);

			return $as_array ? $response : (object) $response;
		}

		/**
		 * Purge the site credentials.
		 *
		 * @since unknown
		 * @since 2.5     Added the deletion of the gf_site_registered option.
		 */
		public function purge_site_credentials() {
			delete_option( 'gf_site_key' );
			delete_option( 'gf_site_secret' );
			delete_option( 'gf_site_registered' );
		}

		/**
		 * Making API requests.
		 *
		 * @since unknown
		 * @since 2.5     Purge the registration data on site if certain errors matched.
		 *
		 * @param string $resource The API route.
		 * @param array $body The request body.
		 * @param string $method The method.
		 * @param array $options The options.
		 *
		 * @return array|WP_Error
		 */
		public function request( $resource, $body, $method = 'POST', $options = array() ) {
			return array(
				'response' => array(
					'code' => 200,
					'message' => 'OK'
				),
				'body' => json_encode( array(
					'key' => 'fake_site_key',
					'secret' => 'fake_site_secret',
					'success' => true
				) )
			);
		}

		/**
		 * @return false|mixed|void
		 */
		public function get_site_key() {
			return 'fake_site_key';
		}

		/**
		 * @return false|mixed|void
		 */
		public function get_site_secret() {
			return 'fake_site_secret';
		}

		/**
		 * @return string
		 */
		public function get_gravity_api_url() {
			return trailingslashit( GRAVITY_API_URL );
		}

		/**
		 * Check if the site has the gf_site_key and gf_site_secret options.
		 *
		 * @since unknown
		 *
		 * @return bool
		 */
		public function is_site_registered() {
			return true;
		}

		/**
		 * Check if the site has the gf_site_key, gf_site_secret and also the gf_site_registered options.
		 *
		 * @since 2.5
		 *
		 * @return bool
		 */
		public function is_legacy_registration() {
			return false;
		}

	}

	function gapi() {
		return Gravity_Api::get_instance();
	}

	gapi();

}