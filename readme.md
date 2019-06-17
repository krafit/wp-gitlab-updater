# WordPress plugin and theme updates from private GitLab repos

You can use this as a WordPress plugin (for example, if you have 
multiple themes and/or plugins in one WP installation, which 
should be updated with the script), or include it directly in a theme or plugin.
The script uses the GitLab tags to check for a new version.

## Usage as a WordPress plugin

Just download the repo and upload the ZIP as a new plugin to 
your WordPress install or use the [GitHub updater](https://github.com/afragen/github-updater) plugin.

After that, you will find a new options page under *Settings* › *GitLab Updater*. There 
you can find all installed themes and plugins, and fields to insert the needed data
to make one or more of them use your GitLab repo as update source.

Search the theme or plugin in the list and insert the following data:

* **Access token** is the GitLab API access token 
(needs »api« and »read_registry« scope. If you use the gitlab.com version, you can create the token here: [gitlab.com/profile/personal_access_tokens](https://gitlab.com/profile/personal_access_tokens)). The safest way 
might be to create the access token for an external user with 
the role »reporter«, who has only access to the theme repo. 
Project features like wiki and issues can be hidden from external users.
* **GitLab URL** needs to be the URL of your GitLab install. For example `https://gitlab.com`
* **Repo** needs to be the identifier of the repo in the format `username/repo` or `group/repo`

## Bundled inside a plugin or theme

### Inside a theme

To bundle it into a theme, you can just grab the `src/theme-updater.php` 
and `src/updater-base.php` and put it into your theme, for example, 
into a `wp-gitlab-updater` folder. After that, you can call it like that:

```php
/**
 * Include the file with the ThemeUpdater class.
 */
 require_once 'wp-gitlab-updater/theme-updater.php';
  
/**
 * Init the theme updater.
 */
new Moenus\GitLabUpdater\ThemeUpdater( [
    'slug' => 'SlugOfTheTheme', 
    'access_token' => 'YourGitLabAccessToken',
    'gitlab_url' => 'URLtoGitLabInstall',
    'repo' => 'RepoIdentifier',
] );
```

The params are the same as explained in the _Usage as a WordPress plugin_ part — `slug` must be the 
directory of the theme. 

### Inside a plugin

For that, take the `src/plugin-updater.php` and `src/updater-base.php`, 
put it into your plugin and call it:

```php
/**
 * Include the file with the PluginUpdater class.
 */
require_once 'wp-gitlab-updater/plugin-updater.php';
  
/**
 * Init the plugin updater with the plugin base name.
 */
new Moenus\GitLabUpdater\PluginUpdater( [
    'slug' => 'SlugOfPlugin', 
    'plugin_base_name' => 'BaseNameOfThePlugin', 
    'access_token' => 'YourGitLabAccessToken', 
    'gitlab_url' => 'URLtoGitLabInstall',
    'repo' => 'RepoIdentifier',
] );
```

Same params as explained in _Usage as a WordPress plugin_ — `slug` is plugin directory
and `plugin_base_name` the basename (for example, `svg-social-menu/svg-social-menu.php`).
