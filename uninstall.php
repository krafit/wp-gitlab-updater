<?php
/**
 * Uninstall routine
 *
 * @package Leitsch\GitLabUpdater
 * @author  Florian Brinkmann
 */

namespace Leitsch\GitLabUpdater;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

/**
 * Delete the options.
 */
delete_site_option( 'wp-gitlab-updater-themes' );
delete_site_option( 'wp-gitlab-updater-plugins' );
delete_option( 'wp-gitlab-updater-themes' );
delete_option( 'wp-gitlab-updater-plugins' );
