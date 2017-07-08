<?php
/**
 * Creates options page with settings..
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
 * Class for creating options page and handling saving/changing/adding of options.
 *
 * Class Settings
 */
class Settings {
	/**
	 * Array with the installed themes.
	 *
	 * @var array
	 */
	private $installed_themes = [];

	/**
	 * Array with the installed plugins.
	 *
	 * @var array
	 */
	private $installed_plugins = [];

	/**
	 * Settings constructor.
	 */
	public function __construct() {
		/**
		 * Create options page.
		 *
		 * @link https://github.com/afragen/github-updater/blob/develop/src/GitHub_Updater/Settings.php#L101
		 */
		add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', [ $this, 'add_options_page' ] );

		add_action( 'network_admin_edit_wp-gitlab-updater', [ $this, 'update_network_options' ] );

		/**
		 * Register settings.
		 */
		add_action( 'admin_init', [ $this, 'settings_init' ] );
	}

	/**
	 * Add options page.
	 */
	public function add_options_page() {
		/**
		 * Set parent page and capability.
		 *
		 * @link https://github.com/afragen/github-updater/blob/develop/src/GitHub_Updater/Settings.php#L197-L198
		 */
		$parent     = is_multisite() ? 'settings.php' : 'options-general.php';
		$capability = is_multisite() ? 'manage_network' : 'manage_options';

		/**
		 * Add submenu page.
		 */
		add_submenu_page(
			$parent,
			esc_html__( 'GitLab Updater Settings', 'wp-gitlab-updater' ),
			esc_html__( 'GitLab Updater', 'wp-gitlab-updater' ),
			$capability,
			'wp-gitlab-updater',
			[ $this, 'create_options_page' ]
		);
	}

	/**
	 * Create options page.
	 */
	public function create_options_page() {
		/**
		 * Set parent page and capability.
		 *
		 * @link https://github.com/afragen/github-updater/blob/develop/src/GitHub_Updater/Settings.php#L248
		 */
		$action = is_multisite() ? 'edit.php?action=wp-gitlab-updater' : 'options.php'; ?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php
			/**
			 * Create tabs.
			 *
			 * @link https://github.com/afragen/github-updater/blob/develop/src/GitHub_Updater/Settings.php#L219
			 */
			$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'themes';
			if ( isset( $_GET['updated'] ) ) { ?>
				<div id="message" class="updated notice is-dismissible">
					<p><?php _e( 'Settings saved.', 'wp-gitlab-updater' ) ?></p></div>
			<?php } ?>
			<h2 class="nav-tab-wrapper">
				<?php
				$tab_items = [
					'themes'  => __( 'Themes', 'wp-gitlab-updater' ),
					'plugins' => __( 'Plugins', 'wp-gitlab-updater' ),
				];
				foreach ( $tab_items as $key => $name ) {
					$active = ( $current_tab === $key ) ? 'nav-tab-active' : '';
					echo '<a class="nav-tab ' . $active . '" href="?page=wp-gitlab-updater&tab=' . $key . '">' . $name . '</a>';
				} ?>
			</h2>
			<ul>
				<?php
				/**
				 * Check for currently displayed tab.
				 */
				if ( 'themes' === $current_tab ) {
					/**
					 * Display instructions.
					 */
					$this->section_instructions( $current_tab );

					/**
					 * Display list of themes as jump nav.
					 */
					foreach ( $this->installed_themes as $installed_theme ) {
						/**
						 * Get slug and name.
						 */
						$slug = $installed_theme->stylesheet;
						$name = $installed_theme->get( 'Name' );

						/**
						 * Output link inside li.
						 */
						?>
						<li><a href="#<?php echo $slug; ?>"><?php echo $name; ?></a></li>
					<?php }
				} else {
					/**
					 * Display instructions.
					 */
					$this->section_instructions( $current_tab );

					/**
					 * Display list of plugins as jump nav.
					 */
					foreach ( $this->installed_plugins as $plugin_basename => $plugin_data ) {
						/**
						 * Get slug.
						 */
						$arr  = explode( "/", $plugin_basename, 2 );
						$slug = $arr[0];

						/**
						 * Get name.
						 */
						$name = $plugin_data['Name'];

						/**
						 * Output link inside li.
						 */
						?>
						<li><a href="#<?php echo $slug; ?>"><?php echo $name; ?></a></li>
					<?php }
				} ?>
			</ul>
			<form action="<?php echo $action; ?>" method="post">
				<?php
				/**
				 * Output security fields.
				 */
				if ( 'themes' === $current_tab ) {
					settings_fields( 'wp-gitlab-updater-themes' );
				} else {
					settings_fields( 'wp-gitlab-updater-plugins' );
				}

				/**
				 * Output settings sections and fields.
				 */
				do_settings_sections( 'wp-gitlab-updater' );

				/**
				 * Submit button.
				 */
				submit_button( __( 'Save Settings', 'wp-gitlab-updater' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * This function here is hooked up to a special action and necessary to process
	 * the saving of the options. This is the big difference with a normal options
	 * page.
	 *
	 * @link https://vedovini.net/2015/10/using-the-wordpress-settings-api-with-network-admin-pages/
	 */
	function update_network_options() {
		$tab = '';

		$update = false;

		/**
		 * Check for themes settings page.
		 */
		if ( isset( $_POST['option_page'] ) && 'wp-gitlab-updater-themes' === $_POST['option_page'] ) {
			if ( false !== check_admin_referer( 'wp-gitlab-updater-themes-options' ) ) {
				$options = $_POST['wp-gitlab-updater-themes'];
				update_site_option( 'wp-gitlab-updater-themes', $options );
				$tab    = 'themes';
				$update = true;
			}
		}


		/**
		 * Check for plugins settings page.
		 */
		if ( isset( $_POST['option_page'] ) && 'wp-gitlab-updater-plugins' === $_POST['option_page'] ) {
			if ( false !== check_admin_referer( 'wp-gitlab-updater-plugins-options' ) ) {
				$options = $_POST['wp-gitlab-updater-plugins'];
				update_site_option( 'wp-gitlab-updater-plugins', $options );
				$tab    = 'plugins';
				$update = true;
			}
		}

		/**
		 * Redirect to our options page.
		 */
		$location = add_query_arg(
			[
				'page'    => 'wp-gitlab-updater',
				'tab'     => $tab,
				'updated' => $update,
			],
			network_admin_url( 'settings.php' )
		);
		wp_safe_redirect( $location );
		exit;
	}

	/**
	 * Displays section instructions.
	 *
	 * @param string $tab The currently displayed tab.
	 */
	private function section_instructions( $tab ) {
		if ( 'themes' === $tab ) { ?>
			<p><?php _e( 'This is a list of all installed themes. To use a private GitLab repo for one or more of them, just enter the details and hit »Save Settings«.', 'wp-gitlab-updater' ); ?></p>
		<?php } else { ?>
			<p><?php _e( 'This is a list of all installed plugins. To use a private GitLab repo for one or more of them, just enter the details and hit »Save Settings«.', 'wp-gitlab-updater' ); ?></p>
		<?php }
	}

	/**
	 * Init settings.
	 */
	public function settings_init() {
		/**
		 * Set properties.
		 */
		$this->installed_themes  = wp_get_themes();
		$this->installed_plugins = get_plugins();

		/**
		 * Register settings.
		 */
		register_setting( 'wp-gitlab-updater-themes', 'wp-gitlab-updater-themes', [
			'sanitize_callback' => [ $this, 'sanitize_theme_settings' ],
		] );

		register_setting( 'wp-gitlab-updater-plugins', 'wp-gitlab-updater-plugins', [
			'sanitize_callback' => [ $this, 'sanitize_plugin_settings' ],
		] );

		/**
		 * Check if we need the plugin settings.
		 */
		if ( isset( $_GET['tab'] ) && 'plugins' === $_GET['tab'] ) {
			/**
			 * Loop them.
			 */
			foreach ( $this->installed_plugins as $plugin_basename => $plugin_data ) {
				/**
				 * Get slug.
				 */
				$arr  = explode( "/", $plugin_basename, 2 );
				$slug = $arr[0];

				/**
				 * Get name.
				 */
				$name = $plugin_data['Name'];

				/**
				 * Section for plugins.
				 */
				add_settings_section(
					"wp-gitlab-updater-plugins-$slug-section",
					sprintf(
						'<span id="%s" style="padding-top: 50px;">%s (%s)</span>',
						$slug,
						$name,
						$plugin_basename
					),
					[ $this, 'section_cb' ],
					'wp-gitlab-updater'
				);

				/**
				 * Create slug field.
				 */
				add_settings_field(
					"wp-gitlab-updater-plugin-$slug-slug-field",
					__( 'Plugin slug', 'wp-gitlab-updater' ),
					[ $this, 'field_cb' ],
					'wp-gitlab-updater',
					"wp-gitlab-updater-plugins-$slug-section",
					[
						'label_for'          => "wp-gitlab-updater-plugin-$slug-slug-field",
						'class'              => 'hidden',
						'settings-array-key' => $plugin_basename,
						'value'              => $slug,
						'type'               => 'plugins',
						'readonly'           => true,
						'value_array_key'    => 'slug',
					]
				);

				/**
				 * Create basename field.
				 */
				add_settings_field(
					"wp-gitlab-updater-plugin-$slug-basename-field",
					__( 'Plugin basename', 'wp-gitlab-updater' ),
					[ $this, 'field_cb' ],
					'wp-gitlab-updater',
					"wp-gitlab-updater-plugins-$slug-section",
					[
						'label_for'          => "wp-gitlab-updater-plugin-$slug-basename-field",
						'class'              => 'hidden',
						'settings-array-key' => $plugin_basename,
						'value'              => $plugin_basename,
						'type'               => 'plugins',
						'readonly'           => true,
						'value_array_key'    => 'settings-array-key',
					]
				);

				/**
				 * Create Access token field.
				 */
				add_settings_field(
					"wp-gitlab-updater-plugin-$slug-access-token-field",
					__( 'Access token', 'wp-gitlab-updater' ),
					[ $this, 'field_cb' ],
					'wp-gitlab-updater',
					"wp-gitlab-updater-plugins-$slug-section",
					[
						'label_for'          => "wp-gitlab-updater-plugin-$slug-access-token-field",
						'settings-array-key' => $plugin_basename,
						'type'               => 'plugins',
						'value_array_key'    => 'access-token',
						'description'        => __( 'GitLab Access token. Needs »api« and »read_registry« scope.', 'wp-gitlab-updater' ),
					]
				);

				/**
				 * GitLab URL.
				 */
				add_settings_field(
					"wp-gitlab-updater-plugin-$slug-gitlab-url",
					__( 'GitLab URL', 'wp-gitlab-updater' ),
					[ $this, 'field_cb' ],
					'wp-gitlab-updater',
					"wp-gitlab-updater-plugins-$slug-section",
					[
						'label_for'          => "wp-gitlab-updater-plugin-$slug-gitlab-url",
						'settings-array-key' => $plugin_basename,
						'type'               => 'plugins',
						'value_array_key'    => 'gitlab-url',
						'description'        => __( 'URL of the GitLab instance (for example, https://gitlab.com).', 'wp-gitlab-updater' ),
					]
				);

				/**
				 * Repo information.
				 */
				add_settings_field(
					"wp-gitlab-updater-plugin-$slug-repo-information",
					__( 'Repo', 'wp-gitlab-updater' ),
					[ $this, 'field_cb' ],
					'wp-gitlab-updater',
					"wp-gitlab-updater-plugins-$slug-section",
					[
						'label_for'          => "wp-gitlab-updater-plugin-$slug-repo-information",
						'settings-array-key' => $plugin_basename,
						'type'               => 'plugins',
						'value_array_key'    => 'repo',
						'description'        => __( 'Username or group and name of repository. For example: username/repo-name or group/repo-name.', 'wp-gitlab-updater' ),
					]
				);
			} // End foreach().
		} else {
			/**
			 * Loop through the themes and create a option for each of it.
			 */
			foreach ( $this->installed_themes as $installed_theme ) {
				$slug = $installed_theme->stylesheet;
				$name = $installed_theme->get( 'Name' );

				/**
				 * Register new sections in »wp-gitlab-updater« page.
				 */
				add_settings_section(
					"wp-gitlab-updater-themes-$slug-section",
					sprintf(
						'<span id="%1$s" style="padding-top: 50px;">%2$s (%1$s)</span>',
						$slug,
						$name
					),
					[ $this, 'section_cb' ],
					'wp-gitlab-updater'
				);

				/**
				 * Create slug field.
				 */
				add_settings_field(
					"wp-gitlab-updater-theme-$slug",
					__( 'Theme slug', 'wp-gitlab-updater' ),
					[ $this, 'field_cb' ],
					'wp-gitlab-updater',
					"wp-gitlab-updater-themes-$slug-section",
					[
						'label_for'          => "theme-slug-field-$slug",
						'class'              => 'hidden',
						'settings-array-key' => $slug,
						'value'              => $slug,
						'type'               => 'themes',
						'readonly'           => true,
						'value_array_key'    => 'settings-array-key',
					]
				);

				/**
				 * Create Access token field.
				 */
				add_settings_field(
					"wp-gitlab-updater-theme-$slug-access-token-field",
					__( 'Access token', 'wp-gitlab-updater' ),
					[ $this, 'field_cb' ],
					'wp-gitlab-updater',
					"wp-gitlab-updater-themes-$slug-section",
					[
						'label_for'          => "wp-gitlab-updater-theme-$slug-access-token-field",
						'settings-array-key' => $slug,
						'type'               => 'themes',
						'value_array_key'    => 'access-token',
						'description'        => __( 'GitLab Access token. Needs »api« and »read_registry« scope.', 'wp-gitlab-updater' ),
					]
				);

				/**
				 * GitLab URL.
				 */
				add_settings_field(
					"wp-gitlab-updater-theme-$slug-gitlab-url",
					__( 'GitLab URL', 'wp-gitlab-updater' ),
					[ $this, 'field_cb' ],
					'wp-gitlab-updater',
					"wp-gitlab-updater-themes-$slug-section",
					[
						'label_for'          => "wp-gitlab-updater-theme-$slug-gitlab-url",
						'settings-array-key' => $slug,
						'type'               => 'themes',
						'value_array_key'    => 'gitlab-url',
						'description'        => __( 'URL of the GitLab instance (for example, https://gitlab.com).', 'wp-gitlab-updater' ),
					]
				);

				/**
				 * Repo information.
				 */
				add_settings_field(
					"wp-gitlab-updater-theme-$slug-repo-information",
					__( 'Repo', 'wp-gitlab-updater' ),
					[ $this, 'field_cb' ],
					'wp-gitlab-updater',
					"wp-gitlab-updater-themes-$slug-section",
					[
						'label_for'          => "wp-gitlab-updater-theme-$slug-repo-information",
						'settings-array-key' => $slug,
						'type'               => 'themes',
						'value_array_key'    => 'repo',
						'description'        => __( 'Username or group and name of repository. For example: username/repo-name or group/repo-name.', 'wp-gitlab-updater' ),
					]
				);
			} // End foreach().
		} // End if().
	}

	/**
	 * Sanitize theme settings.
	 *
	 * @param $input
	 *
	 * @return mixed
	 */
	public function sanitize_theme_settings( $input ) {
		/**
		 * Loop through the themes.
		 * $key is theme slug.
		 */
		foreach ( $input as $key => $value ) {
			/**
			 * Check if the theme exists.
			 */
			if ( $this->installed_themes[ $key ]->exists() ) {
				/**
				 * Check if one of the fields is empty.
				 */
				if ( '' === $value['settings-array-key'] || '' === $value['access-token'] || '' === $value['gitlab-url'] || '' === $value['repo'] ) {
					/**
					 * Unset the array with the theme info.
					 */
					unset( $input[ $key ] );
				} else {
					/**
					 * We have all information, now we need to
					 * replace the slash in the repo info with %2F
					 * for usage in the API URL.
					 */
					$input[ $key ]['repo'] = str_replace( '/', '%2F', $input[ $key ]['repo'] );
					/**
					 * And remove a trailing slash from the URL (if set).
					 */
					$input[ $key ]['gitlab-url'] = untrailingslashit( $input[ $key ]['gitlab-url'] );
				}
			} else {
				/**
				 * We do not have that theme installed, so we unset the array.
				 */
				unset( $input[ $key ] );
			}
		}

		return $input;
	}

	/**
	 * Sanitize plugin settings.
	 *
	 * @param array $input Array with submitted options.
	 *
	 * @return mixed
	 */
	public function sanitize_plugin_settings( $input ) {
		/**
		 * Loop through the plugins.
		 * $key is plugin basename.
		 */
		foreach ( $input as $key => $value ) {
			/**
			 * Check if plugin is installed.
			 */
			if ( isset( $this->installed_plugins[ $key ] ) ) {
				/**
				 * Check if one of the fields is empty.
				 */
				if ( '' === $value['slug'] || '' === $value['settings-array-key'] || '' === $value['access-token'] || '' === $value['gitlab-url'] || '' === $value['repo'] ) {
					/**
					 * Unset the array with the theme info.
					 */
					unset( $input[ $key ] );
				} else {
					/**
					 * We have all information, now we need to
					 * replace the slash in the repo info with %2F
					 * for usage in the API URL.
					 */
					$input[ $key ]['repo'] = str_replace( '/', '%2F', $input[ $key ]['repo'] );

					/**
					 * And remove a trailing slash from the URL (if set).
					 */
					$input[ $key ]['gitlab-url'] = untrailingslashit( $input[ $key ]['gitlab-url'] );
				}
			} else {
				/**
				 * Plugins is not installed, so we unser the array key.
				 */
				unset( $input[ $key ] );
			}
		}

		return $input;
	}

	/**
	 * Section callback.
	 *
	 * @param array $args
	 */
	public function section_cb( $args ) {
	}

	/**
	 * Field callback.
	 *
	 * @param array $args               {
	 *                                  Argument array.
	 *
	 * @type string $type               (Required) »themes« or »plugins«.
	 * @type string $label_for          (Required) Value for the for attribute.
	 * @type string $settings           (Required) Theme slug or plugin basename.
	 * @type string $value_array_key    (Required) array key for the value.
	 * }
	 */
	public function field_cb( $args ) {
		/**
		 * Get type (plugins or themes).
		 */
		$type = $args['type'];

		/**
		 * Get the value of the setting we've registered with register_setting()
		 *
		 * @link https://konstantin.blog/2012/the-wordpress-settings-api/
		 */
		$options = ( is_multisite() ? (array) get_site_option( "wp-gitlab-updater-$type" ) : (array) get_option( "wp-gitlab-updater-$type" ) );

		/**
		 * Get label for.
		 */
		$label_for = esc_attr( $args['label_for'] );

		/**
		 * Get settings array key.
		 */
		$settings_array_key = $args['settings-array-key'];

		/**
		 * Get array key for value.
		 */
		$value_array_key = $args['value_array_key'];

		/**
		 * Get description.
		 */
		$description = isset( $args['description'] ) ? $args['description'] : '';

		/**
		 * Get value
		 */
		if ( isset( $args['value'] ) ) {
			$value = esc_attr( $args['value'] );
		} else {
			/**
			 * Check if we have a value in the settings array.
			 */
			$value = isset( $options[ $settings_array_key ][ $value_array_key ] ) ? $options[ $settings_array_key ][ $value_array_key ] : '';

			/**
			 * Check if this is the repo.
			 * Then we replace the %2F with / again.
			 */
			if ( 1 === preg_match( '/-repo-information$/', $label_for ) ) {
				$value = str_replace( '%2F', '/', $value );
			}
		} ?>
		<input
				id="<?php echo $label_for; ?>" <?php echo ( isset( $args['readonly'] ) && true === $args['readonly'] ) ? 'readonly' : ''; ?>
				type="text" value="<?php echo $value; ?>"
				name="wp-gitlab-updater-<?php echo $type; ?>[<?php echo $settings_array_key ?>][<?php echo $value_array_key ?>]">
		<?php
		/**
		 * Check for description.
		 */
		if ( '' !== $description ) { ?>
			<p class="description">
				<?php echo $description; ?>
			</p>
		<?php }
	}
}
