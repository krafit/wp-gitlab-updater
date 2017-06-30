<?php
/**
 * Lib to make theme updates from private GitLab repos possible.
 * The safest way might be to create the access_token for an
 * external user with the role »reporter«, who has only access to
 * the theme repo. Project features like wiki and issues can be
 * hidden from external users.
 *
 * @package Moenus\GitLabUpdater
 * @author  Florian Brinkmann
 */

namespace Moenus\GitLabUpdater;

/**
 * Class ThemeUpdater
 *
 * Class for handling theme updates from GitLab repo.
 *
 * @package Moenus\GitLabUpdater
 */
class ThemeUpdater {
	/**
	 * Slug of theme to get updates for.
	 *
	 * @var string
	 */
	private $theme_slug;

	/**
	 * Personal access token, which needs the »api« scope.
	 *
	 * @var string
	 */
	private $access_token;

	/**
	 * GitLab repo API URL. For example: https://gitlab.com/api/v4/projects/user%2FprojectName/
	 *
	 * @var string
	 */
	private $gitlab_repo_api_url;

	/**
	 * ThemeUpdater constructor.
	 *
	 * @param string $theme_slug          Slug of theme to get updates for.
	 * @param string $access_token        Personal access token, which needs the »api« scope.
	 * @param string $gitlab_repo_api_url GitLab repo API URL. For example:
	 *                                    https://gitlab.com/api/v4/projects/user%2FprojectName/.
	 */
	public function __construct( $theme_slug, $access_token, $gitlab_repo_api_url ) {
		$this->theme_slug   = $theme_slug;
		$this->access_token = $access_token;

		/**
		 * Remove trailing slash from Repo URL (if set).
		 */
		$this->gitlab_repo_api_url = untrailingslashit( $gitlab_repo_api_url );

		/**
		 * Hook into pre_set_site_transient_update_themes to modify the update_themes
		 * transient if a new theme version is available.
		 */
		add_filter( 'pre_set_site_transient_update_themes', function ( $transient ) {
			$transient = $this->theme_update( $transient );

			return $transient;
		}, 15 );

		/**
		 * Before the files are copied to wp-content/themes, we need to rename the
		 * folder of the theme, so it matches the slug. That is because WordPress
		 * uses the folder name for the destination inside wp-content/themes, not
		 * the theme slug. And the name of the ZIP we get from the GitLab API call
		 * is something with the project name, the tag number and the commit SHA
		 * (so everything but matching the theme slug).
		 */
		add_filter( 'upgrader_source_selection', function ( $source, $remote_source, $wp_upgrader, $args ) {
			/**
			 * Check if the currently updated theme matches our theme slug.
			 */
			if ( $args['theme'] === $this->theme_slug ) {
				$source = $this->filter_source_name( $source, $remote_source, $wp_upgrader, $args );
			}

			return $source;
		}, 10, 4 );
	}

	/**
	 * Checking for updates and updating the transient for theme updates.
	 *
	 * @param object $transient Transient object for theme updates.
	 *
	 * @return object Theme update transient.
	 */
	private function theme_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		/**
		 * Get tag list from GitLab repo.
		 */
		$request = $this->fetch_tags_from_repo();

		/**
		 * Get response code of the request.
		 */
		$response_code = wp_remote_retrieve_response_code( $request );

		/**
		 * Check if request is not valid and return the $transient.
		 * Otherwise get the data body.
		 */
		if ( is_wp_error( $request ) || 200 !== $response_code ) {
			return $transient;
		} else {
			$response = wp_remote_retrieve_body( $request );
		}

		/**
		 * Decode json.
		 */
		$data = json_decode( $response );

		/**
		 * Check if we have no tags and return the transient.
		 */
		if ( empty( $data ) ) {
			return $transient;
		}

		/**
		 * Get the latest tag.
		 */
		$latest_version = $data[0]->name;

		/**
		 * Check if new version is available.
		 */
		if ( version_compare( $transient->checked[ $this->theme_slug ], $latest_version, '<' ) ) {
			/**
			 * Get the package URL.
			 */
			$theme_package = "$this->gitlab_repo_api_url/repository/archive.zip?sha=$latest_version&private_token=$this->access_token";

			/**
			 * Check the response.
			 */
			$response      = wp_safe_remote_get( $theme_package );
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( is_wp_error( $response ) || 200 !== $response_code ) {
				return $transient;
			} else {
				/**
				 * Add data to response array.
				 */
				$transient->response[ $this->theme_slug ]['theme']       = $this->theme_slug;
				$transient->response[ $this->theme_slug ]['new_version'] = $latest_version;
				$transient->response[ $this->theme_slug ]['package']     = $theme_package;
			}
		} // End if().

		return $transient;
	}

	/**
	 * Fetch data of latest theme version.
	 *
	 * @return array|WP_Error Array with data of the latest theme version or WP_Error.
	 */
	private function fetch_tags_from_repo() {
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
	private function filter_source_name( $source, $remote_source, $wp_upgrader, $args ) {
		global $wp_filesystem;

		/**
		 * Check if the remote source directory exists.
		 */
		if ( $wp_filesystem->exists( $remote_source ) ) {
			/**
			 * Create a folder with theme slug as name inside the folder.
			 */
			$upgrade_theme_folder = $remote_source . "/$this->theme_slug";
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
