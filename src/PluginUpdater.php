<?php
/**
 * Lib to make plugin updates from private GitLab repos possible.
 * The safest way might be to create the access_token for an
 * external user with the role »reporter«, who has only access to
 * the plugin repo. Project features like wiki and issues can be
 * hidden from external users.
 *
 * @package Leitsch\GitLabUpdater
 * @author  Florian Brinkmann
 */

namespace Leitsch\GitLabUpdater;

/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Include UpdaterBase class.
 */
require_once 'UpdaterBase.php';

/**
 * Class PluginUpdater
 *
 * Class for handling plugin updates from GitLab repo.
 *
 * @package Leitsch\GitLabUpdater
 */
class PluginUpdater extends UpdaterBase {
	/**
	 * Data of plugins which should get GitLab updates.
	 *
	 * @var array
	 */
	protected $plugin_data = [];

	/**
	 * PluginUpdater constructor.
	 *
	 * @param           $args                {
	 *                                       Argument array.
	 *
	 * @type string     $slug                Slug of plugin to get updates for.
	 * @type string     $plugin_base_name    Relative path of main file. For example:
	 *                                       multilingual-press/multilingual-press.php.
	 * @type string     $access_token        Personal access token, which needs the »api« and »read_registry« scope.
	 * @type string     $gitlab_url          GitLab URL. For example: https://gitlab.com/.
	 * @type string     $repo                GitLab repo name with user or group.
	 *                                       For example: username/repo or group/repo
	 * }
	 *
	 */
	public function __construct( $args = [] ) {
		/**
		 * Set plugin data.
		 */
		$plugin_data = ( is_multisite() ? (array) get_site_option( "wp-gitlab-updater-plugins" ) : (array) get_option( "wp-gitlab-updater-plugins" ) );
		if ( false !== $plugin_data ) {
			$this->plugin_data = $plugin_data;
		}

		/**
		 * Check if we have values.
		 */
		if ( isset( $args['slug'] ) && isset( $args['plugin_base_name'] ) && isset( $args['access_token'] ) && isset( $args['gitlab_url'] ) && isset( $args['repo'] ) ) {
			/**
			 * Create array to insert them into plugin_data.
			 */
			$tmp_array = [
				'settings-array-key' => $args['plugin_base_name'],
				'slug'               => $args['slug'],
				'access-token'       => $args['access_token'],
				'gitlab-url'         => untrailingslashit( $args['gitlab_url'] ),
				'repo'               => str_replace( '/', '%2F', $args['repo'] ),
			];

			/**
			 * Insert it.
			 */
			$this->plugin_data[ $args['slug'] ] = $tmp_array;
		} // End if().

		/**
		 * Hook into pre_set_site_transient_update_plugins to modify the update_plugins
		 * transient if a new plugin version is available.
		 */
		add_filter( 'pre_set_site_transient_update_plugins', function ( $transient ) {
			$transient = $this->plugin_update( $transient );

			return $transient;
		} );

		/**
		 * Check for plugins with the same slug (for example from W.org) and remove
		 * update notifications for them.
		 *
		 * For whatever reason, it seems to be working better here in an anonymous function
		 * rather than in a method…
		 */
		add_filter( 'site_transient_update_plugins', function ( $transient ) {
			if ( empty ( $transient->response ) ) {
				return $transient;
			}

			/**
			 * Check if we have an update for a plugin with the same slug from W.org
			 * and remove it.
			 *
			 * At first, we loop the GitLab updater plugins.
			 */
			foreach ( $this->plugin_data as $plugin ) {
				$plugin_basename = $plugin['settings-array-key'];
				/**
				 * Check if we have a plugin with the same slug and another package URL
				 * than our GitLab URL.
				 */
				if ( array_key_exists( $plugin_basename, $transient->response ) && false === strpos( $transient->response[ $plugin_basename ]->package, $plugin['gitlab-url'] ) ) {
					/**
					 * Unset the response key for that plugin.
					 */
					unset( $transient->response[ $plugin_basename ] );
				}
			}

			return $transient;
		} );

		/**
		 * Before the files are copied to wp-content/plugins, we need to rename the
		 * folder of the plugin, so it matches the slug. That is because WordPress
		 * uses the folder name for the destination inside wp-content/plugins, not
		 * the plugin slug. And the name of the ZIP we get from the GitLab API call
		 * is something with the project name, the tag number and the commit SHA
		 * (so everything but matching the plugin slug).
		 */
		add_filter( 'upgrader_source_selection', function ( $source, $remote_source, $wp_upgrader, $args ) {
			foreach ( $this->plugin_data as $plugin ) {
				/**
				 * Check if the currently updated plugin matches our plugin base name.
				 */
				if ( $args['plugin'] === $plugin['settings-array-key'] && false !== $plugin ) {
					$source = $this->filter_source_name( $source, $remote_source, $plugin['slug'] );
				}
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

		foreach ( $this->plugin_data as $plugin ) {
			/**
			 * Get data from array which we need to build package URL.
			 */
			$gitlab_url   = $plugin['gitlab-url'];
			$repo         = $plugin['repo'];
			$access_token = $plugin['access-token'];

			/**
			 * Get tag list from GitLab repo.
			 */
			$request = $this->fetch_tags_from_repo( $gitlab_url, $repo, $access_token );

			/**
			 * Get response code of the request.
			 */
			$response_code = wp_remote_retrieve_response_code( $request );

			/**
			 * Check if request is not valid and return the $transient.
			 * Otherwise get the data body.
			 */
			if ( is_wp_error( $request ) || 200 !== $response_code ) {
				continue;
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
				continue;
			}

			/**
			 * Get the latest tag.
			 */
			$latest_version = $data[0]->name;

			/**
			 * Check if new version is available.
			 */
			if ( ! isset( $transient->checked[ $plugin['settings-array-key'] ] ) ) {
				continue;
			}

			if ( ! version_compare( $transient->checked[ $plugin['settings-array-key'] ], $latest_version, '<' ) ) {
				continue;
			}

			/**
			 * Get the package URL.
			 */
			$plugin_package = "$gitlab_url/api/v4/projects/$repo/repository/archive.zip?sha=$latest_version&private_token=$access_token";

			/**
			 * Check the response.
			 */
			$response      = wp_safe_remote_get( $plugin_package );
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( is_wp_error( $response ) || 200 !== $response_code ) {
				continue;
			}

			/*
			 * Build stdClass
			 */
			$info              = new \stdClass();
			$info->slug        = $plugin['slug'];
			$info->plugin      = $plugin['settings-array-key'];
			$info->package     = $plugin_package;
			$info->new_version = $latest_version;
			/**
			 * Add data to transient.
			 */
			$transient->response[ $plugin['settings-array-key'] ] = $info;
		}

		return $transient;
	}
}
