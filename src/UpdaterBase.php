<?php
/**
 * Base class for GitLab updater.
 *
 * @package Moenus\GitLabUpdater
 * @author  Florian Brinkmann
 */

namespace Moenus\GitLabUpdater;

/**
 * Base class for handling WordPress theme or plugin updates from a GitLab repo.
 *
 * Class UpdaterBase
 */
class UpdaterBase {
	/**
	 * Theme or plugin slug.
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * Personal access token, which needs the »api« scope.
	 *
	 * @var string
	 */
	protected $access_token;

	/**
	 * GitLab repo API URL. For example: https://gitlab.com/api/v4/projects/user%2FprojectName/
	 *
	 * @var string
	 */
	protected $gitlab_repo_api_url;

	/**
	 * Fetch data of latest version.
	 *
	 * @return array|WP_Error Array with data of the latest theme version or WP_Error.
	 */
	protected function fetch_tags_from_repo() {
		$request_url = "$this->gitlab_repo_api_url/repository/tags/?private_token=$this->access_token";
		$request     = wp_safe_remote_get( $request_url );

		return $request;
	}

	/**
	 * Renames the source directory and returns new $source.
	 *
	 * @param string $source        URL of the tmp folder with the theme files.
	 * @param string $remote_source Source URL on remote.
	 * @param object $wp_upgrader   WP_Upgrader instance.
	 * @param array  $args          Additional args.
	 *
	 * @return string
	 */
	protected function filter_source_name( $source, $remote_source, $wp_upgrader, $args ) {
		global $wp_filesystem;

		/**
		 * Check if the remote source directory exists.
		 */
		if ( $wp_filesystem->exists( $remote_source ) ) {
			/**
			 * Create a folder with theme slug as name inside the folder.
			 */
			$upgrade_theme_folder = $remote_source . "/$this->slug";
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
