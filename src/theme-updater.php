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
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Include UpdaterBase class.
 */
require_once 'updater-base.php';

/**
 * Class ThemeUpdater
 *
 * Class for handling theme updates from GitLab repo.
 *
 * @package Moenus\GitLabUpdater
 */
class ThemeUpdater extends UpdaterBase {
	/**
	 * Data of themes which should get GitLab updates.
	 *
	 * @var array
	 */
	protected $theme_data = [];

	/**
	 * ThemeUpdater constructor.
	 *
	 * @param array $args                    {
	 *                                       Argument array.
	 *
	 * @type string $slug                    Slug of theme to get updates for.
	 * @type string $access_token            Personal access token, which needs the »api« and »read_registry« scope.
	 * @type string $gitlab_url              GitLab URL. For example: https://gitlab.com/
	 * @type string $repo                    GitLab repo name with user or group.
	 *                                       For example: username/repo or group/repo
	 * }
	 */
	public function __construct( $args = [] ) {
		/**
		 * Set theme data.
		 */
		$theme_data = ( is_multisite() ? (array) get_site_option( "wp-gitlab-updater-themes" ) : (array) get_option( "wp-gitlab-updater-themes" ) );
		if ( false !== $theme_data ) {
			$this->theme_data = $theme_data;
		}

		/**
		 * Check if we have values.
		 */
		if ( isset( $args['slug'] ) && isset( $args['access_token'] ) && isset( $args['gitlab_url'] ) && isset( $args['repo'] ) ) {
			/**
			 * Create array to insert them into theme_data.
			 */
			$tmp_array = [
				'settings-array-key' => $args['slug'],
				'access-token'       => $args['access_token'],
				'gitlab-url'         => untrailingslashit( $args['gitlab_url'] ),
				'repo'               => str_replace( '/', '%2F', $args['repo'] ),
			];

			/**
			 * Insert it.
			 */
			$this->theme_data[ $args['slug'] ] = $tmp_array;
		} // End if().

		/**
		 * Hook into pre_set_site_transient_update_themes to modify the update_themes
		 * transient if a new theme version is available.
		 */
		add_filter( 'pre_set_site_transient_update_themes', function ( $transient ) {
			$transient = $this->theme_update( $transient );

			return $transient;
		} );

		/**
		 * Check for themes with the same slug (for example from W.org) and remove
		 * update notifications for them.
		 *
		 * For whatever reason, it seems to be working better here in an anonymous function
		 * rather than in a method…
		 */
		add_filter( 'site_transient_update_themes', function ( $transient ) {
			if ( empty ( $transient->response ) ) {
				return $transient;
			}

			/**
			 * Check if we have an update for a theme with the same slug from W.org
			 * and remove it.
			 *
			 * At first, we loop the GitLab updater themes.
			 */
			foreach ( $this->theme_data as $theme ) {
				$theme_slug = $theme['settings-array-key'];

				/**
				 * Check if we have a theme with the same slug and another package URL
				 * than our GitLab URL.
				 */
				if ( array_key_exists( $theme_slug, $transient->response ) && false === strpos( $transient->response[ $theme_slug ]['package'], $theme['gitlab-url'] ) ) {
					/**
					 * Unset the response key for that theme.
					 */
					unset( $transient->response[ $theme_slug ] );
				}
			}

			return $transient;
		} );

		/**
		 * Before the files are copied to wp-content/themes, we need to rename the
		 * folder of the theme, so it matches the slug. That is because WordPress
		 * uses the folder name for the destination inside wp-content/themes, not
		 * the theme slug. And the name of the ZIP we get from the GitLab API call
		 * is something with the project name, the tag number and the commit SHA
		 * (so everything but matching the theme slug).
		 */
		add_filter( 'upgrader_source_selection', function ( $source, $remote_source, $wp_upgrader, $args ) {
			foreach ( $this->theme_data as $theme ) {
				/**
				 * Check if the currently updated theme matches our theme slug.
				 */
				if ( $args['theme'] === $theme['settings-array-key'] && false !== $theme ) {
					$source = $this->filter_source_name( $source, $remote_source, $theme['settings-array-key'] );
				}
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

		foreach ( $this->theme_data as $theme ) {
			/**
			 * Get data from array.
			 */
			$gitlab_url   = $theme['gitlab-url'];
			$repo         = $theme['repo'];
			$access_token = $theme['access-token'];

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
			if ( version_compare( $transient->checked[ $theme['settings-array-key'] ], $latest_version, '<' ) ) {
				/**
				 * Get the package URL.
				 */
				$theme_package = "$gitlab_url/api/v4/projects/$repo/repository/archive.zip?sha=$latest_version&private_token=$access_token";

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
					$transient->response[ $theme['settings-array-key'] ]['theme']       = $theme['settings-array-key'];
					$transient->response[ $theme['settings-array-key'] ]['new_version'] = $latest_version;
					$transient->response[ $theme['settings-array-key'] ]['package']     = $theme_package;
				}
			} // End if().
		} // End foreach().

		return $transient;
	}
}
