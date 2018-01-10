# Abstract Plugin Base
Used as a base class to help standardize the way we build WordPress plugins.

# WordPress Options Builder Class Library

WordPress options builder class is a library that helps you setup theme or plugin options that store data in the database with just a line or two of code!

## Table of Contents:
- [Installation](#installation)
- [Usage](#usage)

# Installation

You can use this library to start a new plugin from scratch, or you can enhance your existing plugins with this library. Once you have read over the installation instructions it should make sense which direction to go.

## Composer style (recommended)
1. Confirm that composer is installed in your development environment using `which composer`. If CLI does not print any path, you need to install composer like `brew install composer`.
2. Set CLI working directory to wp-content/plugins/{your-plugin-name}
3. Install Abstract_Plugin class via composer command line like
```bash
composer require WordPress-Phoenix/abstract-plugin-base && composer install
```
4. Look at sample code below to see how to include this library in your plugin.

## Manual Installation
1. Download the most updated copy of this repository from `https://api.github.com/repos/WordPress-Phoenix/abstract-plugin-base/zipball`
2. Extract the zip file, and copy the PHP file into your plugin project.
3. Use SSI (Server Side Includes) to include the file into your plugin.

# Usage

## Why should you use this library when building your plugin?
By building your plugin using OOP principals, and extending this Plugin_Base class object, you will be able to quickly and efficiently build
your plugin, allowing it to be simple to start, but giving it the ability to grow complex without changing its architecture. Immediate 
features include:
- Built in SPL Autoload for your includes folder, should you follow WordPress codex naming standards for class files.
- Template class provides you all the best practices for standard plugin initialization
- Minimizes code needed / maintenance of your main plugin file.
- Assists developers new to WordPress plugin development in file / folder architecture.
- By starting all your plugins with the same architecture, we create a standard that is better for the dev community.

## Simplest example of the main plugin file, and required plugin class file

custom-my-plugin.php
```php
<?php
/**
 * Plugin Name: Custom My Plugin
 * Plugin URI: https://github.com/
 */

//avoid direct calls to this file, because now WP core and framework has been used
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
// Create plugin instance on plugins_loaded action to maximize flexibility of wp hooks and filters system.
include_once 'vendor/autoload.php';
include_once 'app/class-my-plugin.php';
Custom\My_Plugin\App::run( __FILE__ );

```

app/class-app.php
```php
<?php
namespace Custom\My_Plugin;
use WPAZ_Plugin_Base\V_2_5\Abstract_Plugin;

/**
 * Class App
 */
class App extends Abstract_Plugin {
    
    public static $autoload_class_prefix = __NAMESPACE__;
    protected static $current_file = __FILE__;
    public static $autoload_type = 'psr-4';
    // Set to 2 when you use 2 namespaces in the main app file
    public static $autoload_ns_match_depth = 2;
    
    public function onload( $instance ) {
        // Nothing yet
    } // END public function __construct
    
    public function init() {
        do_action( get_called_class() . '_before_init' );
        // Do plugin stuff usually looks something like
        // $subclass = new OptionalAppSubfolder/Custom_Class_Subclass();
        // $subclass->custom_plugin_function();
        do_action( get_called_class() . '_after_init' );
    }
    
    public function authenticated_init() {
        if ( is_user_logged_in() ) {
            // Ready for wp-admin - but not required 
            //require_once( $this->installed_dir . '/admin/class-admin-app.php' );
            //$this->admin = new Admin/Admin_App( $this );
        }
    }
    
    protected function defines_and_globals() {
        // None yet.
    }
    
} // END class

```
