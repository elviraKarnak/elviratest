<?php
/**
 * Show message and log-in form when the currently requested
 * screen is not accessible by the current user.
 *
 * @package BuddyPress
 * @subpackage bp-nouveau
 *
 * @since 12.0.0
 */

// Exit if accessed directly.
//defined( 'ABSPATH' ) || exit;
?>



<div class="relative_header"></div> 



<section class="pt_100 pb_100">

    <div class="container">

        <p class="has-text-align-center">
            <?php esc_html_e( 'This community area is accessible to logged-in members only.', 'buddypress' ); ?>
        </p>

        <div class="login-form-wrapper">

            <?php
            wp_login_form( array(
                'redirect'       => get_permalink(), // redirect back after login
                'form_id'        => 'restricted-login-form',
                'label_username' => __( 'Username or Email' ),
                'label_password' => __( 'Password' ),
                'label_log_in'   => __( 'Log In' ),
                'remember'       => true
            ) );
            ?>

        </div>
    </div>

</section>



