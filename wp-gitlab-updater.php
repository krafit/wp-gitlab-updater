<?php
/**
 * Lib for updating themes and plugins from private GitLab repos.
 *
 * @package   Moenus\GitLabUpdater
 * @author    Florian Brinkmann
 * @license   GPL-2.0+
 * @link      https://florianbrinkmann.com/en/
 * @copyright 2017 Florian Brinkmann
 */

/**
 * Plugin Name:       GitLab updater
 * Plugin URI:        https://github.com/Moenus/wp-gitlab-updater
 * Description:       Plugin for updating themes and plugins from private GitLab repos.
 * Version:           2.0.2
 * Author:            Florian Brinkmann
 * Author URI:        https://florianbrinkmann.com/en/
 * License:           GPL-2.0+
 * Network:           true
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: Moenus/wp-gitlab-updater
 */

namespace Moenus\GitLabUpdater;

/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Include classes.
 */
require_once 'src/updater-base.php';
require_once 'src/settings.php';
require_once 'src/plugin-updater.php';
require_once 'src/theme-updater.php';

/**
 * Init plugin.
 */
new UpdaterBase();
