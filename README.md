# WordPress Development Toolkit
![Dash](dash.png)

![Preview](example.png)

1. Navigate to https://wp.docker/wp-admin/ (ask for login in main channel).

2. Download and clone this plugin (should NOT be run in production environments on high-traffic servers where 
performance matters...) into the plugins dir. Do not try to upload the zip file into the Add New plugin uploader. This feature is not supported.

3. Navigate to Vanilla WP Network.

4. Go to Plugins.

5. Activate the `WordPress Development Toolkit` plugin.

6. Go back to https://wp.docker/wp-admin/.

7. Open Dev Toolkit from Admin Menu link.

8. Click "Start New Plugin".

How to fill out the New Plugin form:

![Form](https://github.com/WordPress-Phoenix/wordpress-development-toolkit/blob/master/plugin-generator-form.png)


9. Click Generate New Plugin. This will deliver a new zip file. Check that your namespace has been correctly generated.

10. Follow these instructions: https://github.com/WordPress-Phoenix/abstract-plugin-base#installation

## UPDATING `/lib` files

Lib files come from composer, but you need to ensure you run the command without the composer autoloader:
```
composer update --no-dev --no-autoloader
```

## Offline Development Mode

The plugin uses the GitHub API to fetch the latest copy of the Abstract Plugin Base to include in the plugin. You can alternatively set:
```php
define( 'WP_DEV_KIT_AIRPLANE_MODE', true )
```
to pull the ABP powering this plugin for internet-less development.

