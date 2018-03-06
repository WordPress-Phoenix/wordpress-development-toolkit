# On WordPress Security

* Using Nonces

The primary security mechanism in WordPress is nonces. Nonces are time-stamped and tap into the logged-in user cookie. All nonces in the WordPress Admin leveraging the REST API should use the `wp_rest` action ( `wp_create_nonce('wp_rest')` ) and be passed via either the `_wpnonce` data param (GET or POST) or the `X-WP-Nonce` header.

###### More on Nonces
###### How Nonces Work and Are Secured
###### How The WordPress Login Cookie Works

* Admin Ajax

"Admin Ajax" requests are the original way of executing AJAX requests for WordPress data. Requests routed to Admin Ajax load the entire WordPress Admin bootstrap. Other admin actions will fire if not scoped out using DOING_AJAX constant. When an action is only run by authenticated users, do not register the `no_priv` hook for the callback.

* Escaping Data on Display (PHP & JavaScript)

Cross-site scripting (XSS) is when hackers target unescaped data, manipulating it prior to it reaching the DOM to execute nefarious code.

In PHP we use WordPress Core functions like, esc_html, esc_attr, esc_url and wp_kses to prepare data for safe output to the DOM. Always escape data late, meaning please don't set escaped data as a variable `$escaped = esc_html( $html )` and `echo` the variable -- this defeats the point of escaping.

In JavaScript, XSS happens most often when `jQuery.html()` is used to insert an HTML string to the document. Instead of inserting prebuilt markup strings, use JavaScript to construct DOM nodes and then append built nodes to the document.

* Validating Data

All user input should be validated to assure it's in an intended form.

WordPress Core Data Validation Functions:

COMING SOON :D

* Sanitizing Data On Save

All data passed to the database should first be sanitized, trimming excess characters, unsafe markup and appropriately encoding special characters.

* Storing/File-Logging HTTP Responses & Headers

It can be tempting to store HTTP responses and headers in the database for later debug. Always check you aren't accidentally storing passwords or tokens in cleartext that are normally protected.

* Storing Passwords and Secure Tokens

When authenticating to 3rd party APIs and services, we often need secure keys. These keys should never be committed to code in version control.

When storing keys in the WordPress database, values should be stored encrypted and never decrypted to cleartext, even within a password input.

If the option should be allowed to decrypt in any environment, use a decryption key stored in version-controlled code. This requires a hacker to gain both database access and access to the files via the repository or server.

If the option should never be decrypted outside the current environment, use `wp_salt()` as your encryption key -- this taps the unique cryptographic key that was generated in the current environment's `wp-config.php` file. This requires a hacker gain access to database and file server access to your production environment.

* On User Roles and Permissions

WordPress has preset "roles" for users. These roles shouldn't be used to establish a user's abilities due to how WordPress User Capabilities can be reassigned and distributed to roles.

Always check user permissions based on capabilities. For example, editors can `edit_others_posts` and administrators can `manage_options`, so these are capabilities we can check to see if the user is appropriately credentialed to view a screen or execute an action.
