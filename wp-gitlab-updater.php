<?php
/**
 * Lib for updating themes and plugins from private GitLab repos.
 *
 * @package   Moenus\GitLabUpdater
 * @author    Florian Brinkmann
 * @license   GPL-2.0+
 * @link      https://florianbrinkmann.com/en/
 * @copyright 2017 Florian Brinkmann
 *
 * @wordpress-plugin
 * Plugin Name: GitLab updater
 * Plugin URI:  https://github.com/Moenus/wp-gitlab-updater
 * Description: Lib for updating themes and plugins from private GitLab repos (visit GitHub repo for usage instructions).
 * Version:     1.0.0
 * Author:      Florian Brinkmann
 * Author URI:  https://florianbrinkmann.com/en/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace Moenus\GitLabUpdater;

/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Include PluginUpdater and ThemeUpdater class.
 */
require_once 'src/plugin-updater.php';
require_once 'src/theme-updater.php';
