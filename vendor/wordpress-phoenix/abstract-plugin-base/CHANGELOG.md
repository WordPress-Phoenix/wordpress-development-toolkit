#### 2.5.2
* Remove PHP7 function and replace with PHP5+ function for backwards compatibility.

#### 2.5.1
* Fix single site issue

#### 2.5.0
* Added logic that allows WordPress VIP hosted plugins to run on init instead of plugins_loaded.
* Added parameters and logic to determine is_network_active on the plugin object.
* Added abstract_plugin version to easily tell which version of abstract_plugin the actual plugin object is using.

#### 2.4.0
* adjust App::get() to be static for easy access to plugin object

#### 2.3.1
* Fix fatal error caused by get_plugin_data function not always available

#### 2.3.0
* Add ability to turn off auto-loading for VIP hosting
* Add plugin_data and version
* Fix scoping issue with plugin activation
* Simplified plugins_loaded init by moving into static run function
* Removed vendor autoload loader from main plugin
* Had to skip version 2.1 and 2.2 as several plugins were hot-patched with those version and we don't want to run into those.
* Moved filename back to a dash, since VIP requires a dash for CI phpcs rules, works fine since plugin uses vendor autoload by classmap anyways

#### 2.0.3
* Back to classmap loading, WP vs PSR-4 = fail

#### 2.0.2
* Large rewrite to handle namespaces and psr-4 option
* Alpha sorted parameter and function names
* Uses namespace matching filter for better performance
* Now requires use of namespaces to properly load files
* Updated documentation

#### 1.1.3
* Fixed filename to follow PSR-4 standard requirement

#### 1.1.2
* Fixed / moved main plugin file into src/

#### 1.1.1
* Introduced namespaced version to handle problems with composer in WordPress plugins
* Removed some parts of the class that were not generic enough for this class
* Fixed / Handled namespacing with composer autoload and PSR-4

#### 1.0.3
* call onload function in constructor bug fix

#### 1.0.2
* allow autoload to pull array of directories to autoload
* filter out namespacing from class names for get_file_name_from_class()

#### 1.0.1
* Initial release