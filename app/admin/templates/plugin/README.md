# ||PLUGIN_NAME||

##### ||PLUGIN_DESC||

### DEVELOPER NOTES

TL;DR - Custom code in `/app`, PHP packages in `/vendor`, main file of importance is `/app/class-plugin.php` and plugin gets loaded by `main.php` in the current directory.

For more, see DEV-NOTES.md. Note production and development dependencies in package.json and composer.json.

###### PLUGIN FOLDER HIEARCHY

* `/app`
    * `/admin` (or code requiring authentication)
        * `/css` production assets, include .min copy
        * `/img` run through `imageoptim`
        * `/js` production assets, include .min copy
        * `/src` preprocessor files for css, js, Vue, etc.
        * INCLUDE NEW PHP CLASSES HERE (i.e. `class-internal-api.php`, `class-admin-page.php`, `class-service-connector.php`, etc)
        * INCLUDE NEW SUBFOLDERS HERE (i.e. `/templates`, `/fields`, `/providers` )
    * `/includes`
        * `/css`
        * `/img`
        * `/js`
        * INCLUDE NEW PHP CLASSES HERE (i.e. `class-api.php`, `class-rewrite-rule.php`, `class-single-item.php`, etc)
        * INCLUDE NEW SUBFOLDERS HERE (i.e. `/templates`, `/shortcodes`, `/taxonomies` )
    * `class-plugin.php`
* `/vendor`

### CONTRIBUTORS

This plugin is maintained by ||PLUGIN_AUTHORS||||PLUGIN_TEAM_DASH||.

||PLUGIN_GITHUB_REPO||/graphs/contributors/