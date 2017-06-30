# WordPress plugin and theme updates from private GitLab repos

## Example for using the plugin updater

```php
require_once 'wp-gitlab-updater/PluginUpdater.php';
  
/**
 * Init the plugin updater with the plugin base name.
 */
new Moenus\GitLabUpdater\PluginUpdater( 'svg-social-menu', 'svg-social-menu/svg-social-menu.php', 'token', 'https://gitlab.com/api/v4/projects/florianbrinkmann%2Fsvg%2Dsocial%2Dmenu/' );
```

## Example for using the theme updater

```php
require_once 'wp-gitlab-updater/ThemeUpdater.php';
  
/**
 * Init the theme updater with the theme slug.
 */
new Moenus\GitLabUpdater\ThemeUpdater( 'fbn', 'token', 'https://gitlab.com/api/v4/projects/florianbrinkmann%2Ffbn/' );
```
