# Changelog

## 2.0.2 - 25.07.2017

### Fixed

* Issue with renaming plugins or themes which should not get updates from GitLab updater. The issue leads in renamed
directories, so WordPress cannot find the plugin/theme after an update and deactivates it.

## 2.0.1 - 08.07.2017

### Fixed

* Works now with multisite.

## 2.0.0 - 06.07.2017

**This is a major update. If you used the plugin or lib version 1.0.0, you need to modify your code. See
updated readme for details.**

### Added

* Check to prevent crash with plugin and theme updates with same slug from W.org or other sources.
* GitHub updater support.
* Settings page. 

### Changed

* Use `plugins_loaded` hook in readme for calling the plugin.

## 1.0.0 - 30.06.2017
* Initial release
