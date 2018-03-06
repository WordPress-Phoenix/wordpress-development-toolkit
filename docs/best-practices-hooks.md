# WordPress Action and Filter Hooks Best Practices

Custom hooks and filters separate Custom WordPress Applications from WordPress Code. They let other developers find uses the original author never imagined and create rich extensions and integrations.

These are some best practices around implementing `do_action()` and `apply_filters()` in codebases.

### A list of things that should often have a filter

* HTTP Request Arguments in wp_remote_get() and wp_remote_post().
* WP_Query arguments.
* Many json_decode( file_get_contents( some.json ) );
* Data passed through wp_localize_script();
* Data that you want to allow external manipulation of from other classes and plugins.
* array()'s of default values

### A list of things that should often have an action

* Preflight / postflight actions for major events in your application like starting something, deleting something, etc.
* A series of related actions to build an interface: i.e. `video_player_header`, `video_player_body`, `video_player_footer`.
* Interfaces that need segmenting and versatility.
* Workflows that require sequential and specific execution.

### On naming actions and filters
* **This is a global namespace**. Be creative, be courteous, be concise, be clear and be defensive.
* If you're worried about potential collision with an existing hook name, query Google, search wp.org plugin repo and github.com.
* Always prefix with the product name: `jetpack_myhook` `twentytwenty_process`. This aids locating code.
* Aim for self-descriptive handles that avoid unclear jargon or acronyms. `myplugin_do_single_payment` over `myplugin_cpt_save_post_api_call` or `myplugin_payment`.
* Apply the same zeal, care and caution as you would creating a REST API. These actions and filters are the API for your WordPress product that other developers will need to ingest.
* Create multisite hooks dynamically by `'my_hook_blog_' . get_current_blog_id()` for `my_hook_blog_6` to modify Blog 6's version of the data.

### If you need to pass more than one variable, use an array

WordPress supports more than one attribute in hooks, but this makes the developer using the data ask WordPress for more than the default two that can be returned and additionally learn your order of delivery.

### Simple boolean safety valves

Filters make excellent safety valves. When code is particularly dangerous, particularly resource-intensive or highly narrow/highly broadly focuded. i.e. have the filter default be false and handle be `enable_my_supercustom_feature` or converse default true and `disable_my_supercustom_feature`.

### Timing Hooks

After defining a callback, WordPress uses an absolute integer to time hook execution. The default action timing is 10, so to hook "before Core", often you must define a number lower than 10.

Many Core uses max-out at 100. If you're running community plugins, many tap late actions like `999` or `99999`. Please don't use the PHP constant `PHP_INT_MAX` unless you never want to allow something to run later, and this can create unfortunate timing issues moving code around environments. Use a plugin like [Query Monitor](https://wordpress.org/plugins/query-monitor/) to see what hooks execute on an Admin or Frontend page and when active plugins are firing on those hooks.