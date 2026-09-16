<?php
/**
 * Remove the option on uninstall, on every site of a network. Nothing else
 * is stored.
 *
 * @package LowProfile
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $lowprofile_site_id ) {
		delete_blog_option( $lowprofile_site_id, 'lowprofile_settings' );
	}
	unset( $lowprofile_site_id );
} else {
	delete_option( 'lowprofile_settings' );
}
