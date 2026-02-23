<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;
delete_option('elvismno_settings');
delete_option('elvismno_license_key');
delete_option('elvismno_license_status');
