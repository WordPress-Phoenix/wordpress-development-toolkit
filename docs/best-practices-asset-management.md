# WordPress Asset Management Best Practices

##### WordPress uses internal functions to register *(`wp_register_script()` & `wp_register_style()`)* and enqueue *(`wp_enqueue_script()` & `wp-enqueue_style()`)* static JavaScript and CSS files so that WordPress Core, Themes and Plugins can **programatically interact with scripts and styles.**

This approach allows WordPress to:
  1. Load registered dependencies
  1. Chose the printing order & location of files in the DOM
  1. Print inline script/style alongside referenced files
  1. Print inline js variables preceeding scripts to make PHP data available to the script via the Window variable (`wp_localize_script()` -- originally used for "localizing" translated strings from WordPress language packs into in labels needed in scripts).

#### Break `wp_register_script()` and `wp_register_style()` arguments onto their own lines

This is a matter of preference and judgement, but these registrations are super important, often overrun 80 characters and are worth breaking out to multiple lines for easy locating in files and nice code hygene.

#### Make assets easy to toggle

Always register CSS and JavaScript first instead of directly enqueueing them. This makes dequeueing easier for other products interacting with your dependency in the environment.

#### Always register assets globally

Never scope a script registration inside `is_admin()` or another check to prevent registration collision. Only scope enqueues.

#### Store the string used to register/enqueue an asset in a static class variable or PHP constant

This allows the entire class and application interact programmatically with the asset, preventing string scavenger hunts across files and easing rename.

#### Naming dependency handle slugs
* **This is a global namespace**. Be creative, be courteous, be concise, be clear and be defensive.
* **Please don't use a postfix like `-js`, `-css`, `-script`, `-style`** -- WordPress will add postfixes when printing assets in the DOM, resulting in `something-js-js`. However, if a product is called purecss or momentjs, we then use the full slug with repetitive postfix, despite repetion (i.e. `purecss-css`)
* **Try to keep parity between filename and dependency string**. This makes life easier and applications scale nicer.
* **Plan for growth: avoid calling dependency "my-product.ext."** Use `-primary`, `-core` or `-main` prefix.
* **Never use WordPress Filters or difficult-to-predict dynamic variables** so others may dequeue and register handles with confidence.

#### Leverage dependency chaining and the `array()` method for `wp_enqueue_*()`

A common place that can feel repetitive is registering and initializing enqueued assets. Perhaps trigger enqueue of dependencies via `wp_register_*`'s `$dependency` parameter.

Also, if enqueueing multiple scripts or styles simultaneously, use a single `wp_enqueue_script()` or `wp_enqueue_style()` with an array of dependency slugs to avoid repetition.

#### Use environment-aware asset loading

Using `some_check()` for your local environment, toggle between using minified or unminified assets to ease debugging:
```php
$min = 'staging' === some_check_for_env() ? '' : '.min';
$asset_uri = 'https://domain.com/folder/asset' . $min . '.js';
```

#### Create groupings of scripts and conditional enqueues using `apply_filters()`

Are a number of assets related somehow in a way you don't define using dependencies?

Maybe a developer is going to need to enqueue and dequeue multiple assets that have complex relationships or other uses that would be impacted by WordPress' simple dependency system.

Wrap enqueue array in a filter:
```php
$my_app_deps = apply_filters( 'my_app_dependencies', array( 'jquery', 'vue', 'axios', 'localforage' ) );
wp_enqueue_script( $my_app_deps );
```

#### Beware bundled dependencies in other plugins and JavaScript codebases with require.js or bundled dependencies.
WordPress does little to prevent the collision of scripts. Short of defining dependency arrays and scoping loading, we have

Plus at time of writing it's 2017 and React and Vue-based apps, use of JavaScript tools is becoming more prevalent. Some of these authors create a rollup file of dependencies and a rollup of their app. Even in an environment you control, you likely rely on some 3rd party plugins that load dependencies.

#### Provide a version string for caching
We recommend tying product assets the version for the Theme or Plugin you're in. Having a condition that checks for local environments (i.e. check request string for ".test") and toggling between a production version and `time()` is another good option for cache-busting on a local env.

###### NEW: Working with Assets containing cache-hash
Often when working with JavaScript SPA frameworks and tools, a unique hash is generated in the filename. To account for these assets
