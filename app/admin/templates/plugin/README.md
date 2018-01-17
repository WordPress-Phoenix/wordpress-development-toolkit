# <%= NAME %>

<%= DESC %>

## INSTALL & CONFIGURE
1. Upload the entire `/<%= SLUG %>` directory to the `/wp-content/plugins/` directory.
2. Activate <%= name %> through the 'Plugins' menu in WordPress. In WordPress Multisite plugins can be activated 
per-site or for the entire network.

## FREQUENTLY ASKED QUESTIONS

## HOW TO DEBUG

### DEVELOPER NOTES
* Main plugin file: `<%= SLUG %>.php`.
* Main plugin class: `<%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Plugin` in `/app/class-plugin.php`.
	* Public functionality loaded in `Plugin::init()`
	* Auth'd functionality checked with `is_user_logged_in()` and executed in `Plugin::authenticated_init()`
* PHP in `/app`
* JS & CSS in `/app/assets`
* PHP deps in `/vendor` handled by Composer.

Proper PSR-4 class names i.e. (Some_Class named class-some-class.php) in `/app`, `/app/admin`, and `/app/includes` 
are autoloaded and don't require manual declaration.

For more, see DEV-NOTES.md. Note production and development dependencies in package.json and composer.json.

## CONTRIBUTORS

This plugin is maintained by <%= AUTHORS %><%= TEAM %>.
||PLUGIN_GITHUB_REPO||/graphs/contributors/