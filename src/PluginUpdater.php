<?php
/**
 * Lib to make plugin updates from private GitLab repos possible.
 * The safest way might be to create the access_token for an
 * external user with the role »reporter«, who has only access to
 * the plugin repo. Project features like wiki and issues can be
 * hidden from external users.
 *
 * @package Moenus\GitLabUpdater
 * @author  Florian Brinkmann
 */

namespace Moenus\GitLabUpdater;

/**
 * Include UpdaterBase class.
 */
require_once 'UpdaterBase.php';

/**
 * Class PluginUpdater
 *
 * Class for handling plugin updates from GitLab repo.
 *
 * @package Moenus\GitLabUpdater
 */
class PluginUpdater extends UpdaterBase {
	/**
	 * Base name of plugin (for example, example-plugin/example-plugin.php).
	 *
	 * @var string
	 */
	private $plugin_base_name;

	/**
	 * PluginUpdater constructor.
	 *
	 * @param string $slug                  Slug of plugin to get updates for.
	 * @param string $plugin_base_name      Relative path of main file. For example:
	 *                                      multilingual-press/multilingual-press.php.
	 * @param string $access_token          Personal access token, which needs the »api« and »read_registry« scope.
	 * @param string $gitlab_repo_api_url   GitLab repo API URL. For example:
	 *                                      https://gitlab.com/api/v4/projects/user%2FprojectName/.
	 */
	public function __construct( $slug, $plugin_base_name, $access_token, $gitlab_repo_api_url ) {
		$this->slug             = $slug;
		$this->plugin_base_name = $plugin_base_name;
		$this->access_token     = $access_token;

		/**
		 * Remove trailing slash from Repo URL (if set).
		 */
		$this->gitlab_repo_api_url = untrailingslashit( $gitlab_repo_api_url );

		/**
		 * Hook into pre_set_site_transient_update_plugins to modify the update_plugins
		 * transient if a new plugin version is available.
		 */
		add_filter( 'pre_set_site_transient_update_plugins', function ( $transient ) {
			$transient = $this->plugin_update( $transient );

			return $transient;
		}, 15 );

		/**
		 * Before the files are copied to wp-content/plugins, we need to rename the
		 * folder of the plugin, so it matches the slug. That is because WordPress
		 * uses the folder name for the destination inside wp-content/plugins, not
		 * the plugin slug. And the name of the ZIP we get from the GitLab API call
		 * is something with the project name, the tag number and the commit SHA
		 * (so everything but matching the plugin slug).
		 */
		add_filter( 'upgrader_source_selection', function ( $source, $remote_source, $wp_upgrader, $args ) {
			/**
			 * Check if the currently updated plugin matches our plugin base name.
			 */
			if ( $args['plugin'] === $this->plugin_base_name ) {
				$source = $this->filter_source_name( $source, $remote_source, $wp_upgrader, $args );
			}

			return $source;
		}, 10, 4 );
	}

	/**
	 * Checking for updates and updating the transient for plugin updates.
	 *
	 * @param object $transient Transient object for plugin updates.
	 *
	 * @return object plugin update transient.
	 */
	private function plugin_update( $transient ) {
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
		if ( version_compare( $transient->checked[ $this->plugin_base_name ], $latest_version, '<' ) ) {
			/**
			 * Get the package URL.
			 */
			$plugin_package = "$this->gitlab_repo_api_url/repository/archive.zip?sha=$latest_version&private_token=$this->access_token";

			/**
			 * Check the response.
			 */
			$response      = wp_safe_remote_get( $plugin_package );
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( is_wp_error( $response ) || 200 !== $response_code ) {
				return $transient;
			} else {
				/**
				 * Build stdClass
				 */
				$info              = new \stdClass();
				$info->slug        = $this->slug;
				$info->plugin      = $this->plugin_base_name;
				$info->package     = $plugin_package;
				$info->new_version = $latest_version;
				/**
				 * Add data to transient.
				 */
				$transient->response[ $this->plugin_base_name ] = $info;
			}
		} // End if().

		return $transient;
	}
}
