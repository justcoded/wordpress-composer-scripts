<p align="center">
    <a href="https://wordpress.org" target="_blank">
        <img src="https://s.w.org/about/images/logos/wordpress-logo-notext-rgb.png" height="100px">
    </a>
    <a href="https://getcomposer.org/" target="_blank">
            <img src="https://getcomposer.org/img/logo-composer-transparent.png" height="100px">
        </a>
    <h1 align="center">Composer scripts for WordPress Installation</h1>
    <br>
</p>

Custom [Composer](https://getcomposer.org/) scripts for [WordPress Project Template](https://github.com/justcoded/wordpress-starter).

## Installation

The only way to install this extension is through composer.

Either run

    php composer.phar require --prefer-dist justcoded/wordpress-composer-scripts "*"
    
or add to require section:

    "justcoded/wordpress-composer-scripts": "*"


After that add to scripts section:

      "scripts": {
        "wp:postInstall": "JustCoded\\WP\\Composer\\Environment::post_install",
        "wp:deployReadme": "JustCoded\\WP\\Composer\\Environment::deployment_readme",
        "wp:dbPrefix": "JustCoded\\WP\\Composer\\Environment::wpdb_prefix",
        "wp:salts": "JustCoded\\WP\\Composer\\Environment::salts",
        "wp:secure": "JustCoded\\WP\\Composer\\Security::admin_http_auth",
        "wp:theme": "JustCoded\\WP\\Composer\\Boilerplates::theme"
      }

## Available scripts

### wp:postInstall

Copies .env.example and .htaccess.example to .env and .htaccess.

This script should be added to post-install and post-update hooks, so once you download the 
project - you will have configuration files in place. Just replace them with real values.

### wp:deployReadme

This script is used inside create-project command hook. This script clean up default Project Template readme
with documenation of real project deployment.

### wp:dbPrefix

Create unique db prefix (to improve security) and replace it inside .env.example and .env files.

By default this script should be added to create-project command hooks.

### wp:salts

Regenerate WordPress salts inside .env.example and .env files.

Useful if you want to disable all old open sessions / cookies.

### wp:secure

Allows to create wp-admin folder HTTP Auth password protection.

_*Run this command without params to get command help_

#### wp:theme

Creates a theme based on [JustCoded Theme Boilerplate](https://github.com/justcoded/wordpress-theme-boilerplate).

_*Run this command without params to get command help_
