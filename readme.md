# WordPress plugin and theme updates from private GitLab repos

You can use this as a WordPress plugin (for example, if you have 
multiple themes and/or plugins in one WP installation, which 
should be updated with the script), or include it directly in a theme or plugin.

## Usage as a WordPress plugin

Just download the repo and upload the ZIP as a new plugin to 
your WordPress install (or maybe as a MU plugin). To use the 
update feature for your plugins or themes, you need to modify its code a bit:

### With a theme

To enable theme updates, you can put the following code into 
the functions.php (or a related file):
```php
if ( class_exists( '\Moenus\GitLabUpdater\ThemeUpdater' ) ) {
    /**
     * Init the theme updater with the theme slug.
     */
    new Moenus\GitLabUpdater\ThemeUpdater( 'slug', 'access_token', 'gitlab_repo_api_url' );
}
```
* `slug` has to be the name of the theme folder.
* `access_token` is the GitLab API access token. The safest way 
might be to create the access_token for an external user with 
the role »reporter«, who has only access to the theme repo. 
Project features like wiki and issues can be hidden from external users.
* `gitlab_repo_api_url` needs to be the API URL to the repo. This could look something like that: https://gitlab.com/api/v4/projects/username%2Fproject/ — notice the encoded username/project part (replace `/` with `%2F`).

### With a plugin

The usage in a plugin is similar:

```php
if ( class_exists( '\Moenus\GitLabUpdater\PluginUpdater' ) ) {
    /**
     * Init the plugin updater with the plugin base name.
     */
    new \Moenus\GitLabUpdater\PluginUpdater( 'slug', 'plugin_base_name', 'access_token', 'gitlab_repo_api_url' );
}
```
* `slug` has to be the name of the theme folder.
* `plugin_base_name` needs to be the base name of the plugin 
(folder and main file. For example `svg-social-menu/svg-social-menu.php`).
* `access_token` is the GitLab API access token (see _With a theme_ for more info).
* `gitlab_repo_api_url` needs to be the API URL to the repo (see _With a theme_ for an example URL).

## Bundled inside a plugin or theme

### Inside a theme

To bundle it into a theme, you can just grab the `src/ThemeUpdater.php` 
and `src/UpdaterBase.php` and put it into your theme, for example, 
into a `wp-gitlab-updater` folder. After that, you can call it like that:

```php
/**
 * Include the file with the ThemeUpdater class.
 */
 require_once 'wp-gitlab-updater/ThemeUpdater.php';
  
/**
 * Init the theme updater.
 */
new Moenus\GitLabUpdater\ThemeUpdater( 'slug', 'access_token', 'gitlab_repo_api_url' );
```

The params are the same as explained in the _Usage as a WordPress plugin_ part. 

### Inside a plugin

For that, take the `src/PluginUpdater.php` and `src/UpdaterBase.php`, 
put it into your plugin and call it:

```php
/**
 * Include the file with the PluginUpdater class.
 */
 require_once 'wp-gitlab-updater/PluginUpdater.php';
  
/**
 * Init the plugin updater with the plugin base name.
 */
new Moenus\GitLabUpdater\PluginUpdater( 'slug', 'plugin_base_name', 'access_token', 'gitlab_repo_api_url' );
```

Same params as explained in _Usage as a WordPress plugin_
