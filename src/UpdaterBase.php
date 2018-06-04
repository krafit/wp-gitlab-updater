<?php
/**
 * Base class for GitLab updater.
 *
 * @package Moenus\GitLabUpdater
 * @author  Florian Brinkmann
 */

namespace Moenus\GitLabUpdater;

/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Base class for handling WordPress theme or plugin updates from a GitLab repo.
 *
 * Class UpdaterBase
 */
class UpdaterBase {
	/**
	 * UpdaterBase constructor.
	 */
	public function __construct() {
		/**
		 * Setup options page.
		 */
		new Settings();

		/**
		 * Run plugin updater.
		 */
		new PluginUpdater();

		/**
		 * Run theme updater.
		 */
		new ThemeUpdater();
	}

	/**
	 * Fetch data of latest version.
	 *
	 * @param string $gitlab_url   URL to GitLab install.
	 * @param string $repo         Repo identifier in format username/repo or group/repo.
	 * @param string $access_token Access token.
	 *
	 * @return array|WP_Error Array with data of the latest version or WP_Error.
	 */
	protected function fetch_tags_from_repo( $gitlab_url, $repo, $access_token ) {
		$request_url = "$gitlab_url/api/v4/projects/$repo/repository/tags/?private_token=$access_token";
		$request     = wp_safe_remote_get( $request_url );

		return $request;
	}

	/**
	 * Renames the source directory and returns new $source.
	 *
	 * @param string $source        URL of the tmp folder with the theme or plugin files.
	 * @param string $remote_source Source URL on remote.
	 * @param string $slug          Directory name.
	 *
	 * @return string
	 */
	protected function filter_source_name( $source, $remote_source, $slug ) {
		global $wp_filesystem;

		/**
		 * Check if the remote source directory exists.
		 */
		if ( $wp_filesystem->exists( $remote_source ) ) {
			/**
			 * Create a folder with slug as name inside the folder.
			 */
			$upgrade_theme_folder = $remote_source . "/$slug";
			$wp_filesystem->mkdir( $upgrade_theme_folder );

			/**
			 * Copy files from $source in new $upgrade_theme_folder
			 */
			copy_dir( $source, $upgrade_theme_folder );

			/**
			 * Remove the old $source directory.
			 */
			$wp_filesystem->delete( $source, true );

			/**
			 * Set new folder as $source.
			 */
			$source = $upgrade_theme_folder;
		}

		return $source;
	}
}
