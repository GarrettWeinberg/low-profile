<?php
/**
 * Plugin Name:       Low Profile
 * Plugin URI:        https://github.com/GarrettWeinberg/low-profile
 * Description:       Hides what identifies your WordPress install and closes the enumeration paths it leaves open by default — version markers, discovery links, XML-RPC, the public users list, author archives and the login form's username hints.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Garrett Weinberg
 * Author URI:        https://garrettweinberg.me
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       low-profile
 *
 * @package LowProfile
 */

defined( 'ABSPATH' ) || exit;

define( 'LOWPROFILE_VERSION', '1.0.1' );
define( 'LOWPROFILE_FILE', __FILE__ );
define( 'LOWPROFILE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOWPROFILE_OPTION', 'lowprofile_settings' );

require_once LOWPROFILE_DIR . 'includes/class-lowprofile-settings.php';
require_once LOWPROFILE_DIR . 'includes/class-lowprofile-guards.php';

/*
 * The guards register their hooks as soon as the plugin loads rather than on
 * `init`: several of WordPress's discovery hooks are attached in
 * default-filters.php before any plugin runs, and the file-editing constant
 * has to exist before wp-admin builds its menu.
 */
LowProfile_Guards::boot( LowProfile_Settings::get() );

if ( is_admin() ) {
	LowProfile_Settings::register_admin();
}

register_activation_hook( __FILE__, array( 'LowProfile_Settings', 'activate' ) );
