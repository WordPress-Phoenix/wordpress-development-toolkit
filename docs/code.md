# Coding Standards Philosophy

Adhering to the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/) makes them familiar to a large, mature community of WordPress Developers, which expedites code review, employee onboarding and promotes easy knowledge transfer and file skimming.

# PHP, JavaScript and HTML Standards

* [**WordPress PHP Standards**](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/)
* [**WordPress HTML Standards**](https://make.wordpress.org/core/handbook/best-practices/coding-standards/html/)
* [**WordPress Inline Documentation Standards**](https://make.wordpress.org/core/handbook/best-practices/inline-documentation-standards/)

# Using Modern CSS & JavaScript

Please evaluate CSS and JavaScript at caniuse.com for appropriate browser support, applying lean polyfills when appropriate.

# On Naming Things

* Working with global namespaces... Be creative, be courteous, be concise, be clear and be defensive.
* Always avoid "rekeying" existing data keys in WordPress and our custom data, even if the key names could be better/clearer (add inline comments instead).
* In general, lean on and mimic WordPress' existing terminology and pattern.
* Avoid redundancy -- if the product name is in the namespace, don't repeat. `Team\Plugin\Admin` is preferred to `Team\Plugin\Team_Plugin_Admin`.
* Avoid technical jargon when there's more appropriate product jargon. `get_site_logs_data()` is preferred to `get_single_opts_and_metadata_arr()`.
* Name using pairing language and avoid intermixing (the word begin pairs with end, start pairs with finish, get pairs with set, read pairs with write, add pairs with remove, insert pairs with delete, etc).
* Use parallel and consistent constructs `Contact` + `About` OR `Contact Us` + `About Us` are preferred to `Contact Us` + `About The Company` or `General Settings` + `Video Options` + `Image Config`.
* Avoid unnecessary, uncommon and unclear truncation. `$video_attributes` preferred to `$vid_att_str`, `$service_id` to `$s_id`.
* Avoid oversimplifying when naming products -- `Awesome Video Library` is preferred to `Awesome Video` and `Awesome AMP Core` to `Awesome AMP`. This more easily allows for `Awesome Video Feeds` or `Awesome Video Syndication` and `Awesome AMP Live Blogs`, etc. Overlarge product names encourage overlage plugins and classes.
* Avoid verbose names that can be supported by variable, method and inline documentation i.e. `service_oauth_token` is preferred to `oauth_token_for_HTTP_POST_to_service`.

### Other Tips
* Verbose and descriptive names like `parse_clean_and_modify_logs_data()` are a sign that logic should be reduced to `clean_logs_data()`, `modify_logs_data()` and `parse_logs_data()`.
* If a function doesn't always do something, consider whether it deserves a `maybe_` prefix i.e. `maybe_create_table()` might run a series of checks before trying to create a new database table.

# On WordPress "Scoping"

When we talk about scoping in WordPress, we're talking about collision or improper access of some kind.

WordPress does nothing to prevent collision and little granular control for access out-of-the-box. This means large enterprise environments must self-police themselves.

Here are X discrete ways we do and don't "scope" in WordPress

1. Always Register Scripts and Styles Globally

Having environment-aware loading of minified vs. unminified assets is encouraged, but scripts should always register globally to prevent disparate and confusing registrations.

2. Maybe Check User Permissions for Entire Tools, Interfaces Parts and Saving Data

Check user permissions via `current_user_can()`. Never check via editor level (TL;DR - WordPress editorial levels are not a valid testable value -- instead test a permission that editor level should have). Perhaps interfaces vary by role. Many tools are only available to certain roles and capabilities (perhaps you add a custom capability to your product?)

3. Never scope deeply without documenting

Make it clear to users how scoping logic affects your application. Document logic for other engineers who may work with your product.