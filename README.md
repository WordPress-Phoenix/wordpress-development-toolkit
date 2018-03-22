# WordPress Development Toolkit
![Dash](dash.png)

![Preview](example.png)

1. Download and install plugin (should NOT be run in production environments on high-traffic servers where 
performance matters...).

2. Activate plugin

3. Open Dev Toolkit in Admin Menu

4. Generate New Plugin or access resource guides.

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

