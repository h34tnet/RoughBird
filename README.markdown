# RoughBird Microframework

## What is RoughBird?

RoughBird is a php MVC microframework roughly modeled after tiago bastos
[nicedog](http://github.com/bastos/nicedog) (which is in turn modeled after
web.py and sinatra), just a bit less *micro*.

## How to use it

### docRoot/.htaccess

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ /index.php?url=$1 [L,QSA]

### docRoot/index.php

    <?php
        require_once('../RoughBird/RoughBird.class.php');

        echo RoughBird::get()
            // Set the applications model/view/controller directory.
            ->setApp('../myapp')

            // Dispatch the request.
            ->dispatch(!empty($_REQUEST['url']) ? $_REQUEST['url'] : '');
    ?>

### {$appDirectory}/init.php
    <?php
        RoughBird::get()
        // enables debug mode so we have pretty stack traces if errors occur
        ->enableDebug()
        ->setDocumentRoot(getHost())

        // includes {$appDirectory}/libraries/markdown.php
        ->requireLibrary('markdown.php')

        // optional: for configuring external ressources, like
        // maybe database connections
        // requires the {$appDir}/setup.php which defines a class "MyAppSetup"
        ->setup('MyApp')

        ->route ('',                                        'Index')
        ->route('login',                                    'Login')

        // for example, http://example.com/users/bar is routed to the controller
        // UserDetail->GET('bar') or UserDetail->POST('bar'), depending on the
        // request
        ->route('users/(?P<name>[a-zA-Z0-9_\-\(\)]+)',      'UserDetail')

        // these two are special handlers
        // 404 is called if no routes match
        ->route(404,                                        'PageNotFound')

        // 500 is called if an error happens anywhere
        ->route(500,                                        'ErrorOccured');
    ?>
