

### Great Enterprise-Grade WordPress & Web Development Resources

* [WordPress The Right Way](https://www.wptherightway.org/en/getting_started/) - [PDF](https://www.gitbook.com/download/pdf/book/tomjn/wordpress-the-right-way?lang=en)

* [WordPress Action & Filter Reference](https://codex.wordpress.org/Plugin_API/Action_Reference)
* [GenerateWP.com](https://generatewp.com) -- Interactive tools for creating Core API queries and WordPress File Headers
* [Design Patterns for Humans](https://github.com/kamranahmedse/design-patterns-for-humans)

* [10Up Engineering Standards & Best Practices](https://10up.github.io/Engineering-Best-Practices/) [(forked XWP copy of standards)](https://xwp.github.io/engineering-best-practices/)
* [WordPress.com VIP "What We Look For"](https://vip.wordpress.com/documentation/code-review-what-we-look-for/)
* [Locutus.io/php](http://locutus.io/php) -- Common PHP methods ported to JavaScript.
* [YouMightNotNeedjQuery.com](http://youmightnotneedjquery.com) -- An explainer on using native JavaScript alternatives to jQuery.

### Great Topical Guides / Blogs
* [Understanding WordPress Directory Structure](https://www.rarst.net/wordpress/directory-structure/)
* [10 WordPress Things I've Learned Working With 10Up](http://rachievee.com/10-wordpress-things-ive-learned-working-with-10up/)
* [Intro to Underscore.js Templates in WordPress](https://themehybrid.com/weblog/intro-to-underscore-js-templates-in-wordpress)
* [On using `apply_filters( 'the_content' )` in the loop or rolling a custom version](https://themehybrid.com/weblog/how-to-apply-content-filters)
* [Introduction to WordPress Term Meta API](https://themehybrid.com/weblog/introduction-to-wordpress-term-meta)
* [Using Shortcode Attributes](https://pippinsplugins.com/shortcodes-101-shortcode-attributes/)
* [Using Template Files for Better Frontend Shortcodes](https://pippinsplugins.com/shortcodes-101-using-template-files-better-shortcodes/)
* [On Checking Plugin, Class and Method Dependencies](https://pippinsplugins.com/checking-dependent-plugin-active/)
* [On the WordPress Heartbeat API](https://pippinsplugins.com/using-the-wordpress-heartbeat-api/)
* [WordPlate Helpers](https://wordplate.github.io/docs/helpers)

### Little-used/documented WordPress Core JavaScript
* wp.template() -- A simple implementation of underscore.js' `_.template()` method.
* wp.shortcode.next() -- Parse shortcode string into data object using JavaScript.
* wp.shortcode.string() -- Generate a WordPress shortcode string using JavaScript data.
* wp.html.string() -- Generate an HTML tag string using JavaScript data.
* wp.media.attachment( id ) - Rapidly retrieve attachment data, image sizes, edit link, etc. Returns promise.
* wp.ajax.send() - Create a fully-authenticated admin ajax request anywhere in the WordPress admin.
* wp.Uploader() - Helper for file uploads
* wp.emoji.parse() - Parse emojis into twemoji.
* wp.emoji.test() - Test if a string contains emoji.
* wpCookie.get(), wpCookie.set(), wpCookie.remove() - Cookie CRUD tools

### On Leveraging Stack Overflow, The WordPress Codex and Search-Sourced Solutions

Stack Overflow, The Codex and Solutions found on blogs are often excellent starting points to learn and develop solutions. However, many solutions aren't written to consider enterprise needs, high-traffic sites or large-scale environments.

When integrating someone else's solution, please consider the following:
* Are other developers on the thread pointing out edge-cases, performance concerns or other issues the original author didn't code for?
* Will this scale in a high-traffic environment? Can performance be better addressed through caching or storing data that's resource-intensive to generate?
* Are there enough filters and actions for modification? Perhaps add some.
* Are variable names and function names clear? Please clarify them for our purposes.
* Is this functionality "right-sized?" Perhaps break large procedural functions into helpers.
* Does the author properly sanitize and validate user input while late-escaping output?

### Helpful Utility/Development Plugins
* [FakerPress](https://wordpress.org/plugins/fakerpress/) - Generate Fake WordPress Data (optionally use 500px for real, varied featured post images)
* [Regenerate Thumbnails](https://wordpress.org/plugins/regenerate-thumbnails/) - Regenerate Thumbnails after 
changing Thumbnail Sizes
* [P3 Plugin Profiler](https://wordpress.org/plugins/p3-profiler/) - Tool to better understand plugin performance
* [Locomotive](https://github.com/reaktivstudios/locomotive) - Simple, repeatable batch process tasks for wordpress
* [Query Monitor](https://wordpress.org/plugins/query-monitor/) - Debug WordPress deeply in any environment. Detect every running action, filter, registered script, environment configuration, simple PHP errors and evaluate long-running database queries.
