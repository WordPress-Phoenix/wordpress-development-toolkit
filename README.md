# WordPress Development Toolkit

Tools and resources for WordPress development.

## INSTALL & CONFIGURE
1. Upload the entire `/wordpress-development-toolkit` directory to the `/wp-content/plugins/` directory.
2. Activate WordPress Development Toolkit through the 'Plugins' menu in WordPress. In WordPress Multisite plugins can be activated 
per-site or for the entire network.

## FREQUENTLY ASKED QUESTIONS

## HOW TO DEBUG

### DEVELOPER NOTES
* Main plugin file: `wordpress-development-toolkit.php`.
* Main plugin class: `PHX_WP_DEVKIT\V_1_2\Plugin` in `/app/class-plugin.php`.
	* Public functionality loaded in `Plugin::init()`
	* Auth'd functionality checked with `is_user_logged_in()` and executed in `Plugin::authenticated_init()`
* PHP in `/app`
* JS & CSS in `/app/assets`
* PHP deps in `/vendor` handled by Composer.

Proper PSR-4 class names i.e. (Some_Class named class-some-class.php) in `/app`, `/app/admin`, and `/app/includes` 
are autoloaded and don't require manual declaration.

For more, see DEV-NOTES.md. Note production and development dependencies in package.json and composer.json.

## CONTRIBUTORS

This plugin is maintained by David Ryan - WordPress Phoenix.
||PLUGIN_GITHUB_REPO||/graphs/contributors/