<?php
class RoughBird {
    public static
        $instance;
        
    public
        $documentRoot,
        $appDir,
        $routes,
        $debug = false,
        $setup;

    /*
     * RoughBird is a Singleton, so to create an object you need to call
     * Roughbird::get()
     */
    private function __construct() {
        // utf-8 only
        mb_internal_encoding("UTF-8");

        // a static member function is used for autoloading controllers and
        // models. libraries are included by registering them
        spl_autoload_register(array('Roughbird', 'autoload'));

        // error handler that throws exceptions instead of errors
        // the function_exists check is so that the tests won't fail
        if (!function_exists('rb_exception_error_handler')) {
            function rb_exception_error_handler($errno, $errstr, $errfile, $errline ) {
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
        }
        set_error_handler("rb_exception_error_handler");

        $this->routes = array();
    }

    /* 
     * autoloading of controllers and models
     */
    public static function autoload($classname) {
        $appDir = RoughBird::get()->appDir;

        if (file_exists($appDir . '/controller/' . $classname . '.class.php')) {
            require_once($appDir . '/controller/' . $classname . '.class.php');

        } elseif (file_exists($appDir . '/model/' . $classname . '.php')) {
            require_once($appDir . '/model/' . $classname) . '.php';
        }
    }

    /*
     * returns the Roughbird instance
     * if none exists, it will be created on the fly
     */
    public static function get() {
        if (!self::$instance)
            self::$instance = new RoughBird();

        return self::$instance;
    }

    /*
     * the directory containing the application files
     * at least an init.php will be needed
     */
    public function setApp($appDirectory) {
        $this->appDir = rtrim($appDirectory, '/');
        $this->loadAppConfig();
        return $this;
    }

    /*
     * returns the path where the RoughBird.class.php is located
     */
    public function getRoughBirdPath() {
        return dirname(__FILE__);
    }

    /*
     * returns the path the application is located
     */
    public function getAppPath() {
        return str_replace('\\', '/', realpath($this->appDir));
    }

    /*
     * returns the name of the application
     */
    public function getAppName() {
        return basename($this->getAppPath() . '/');
    }

    /*
     * set the document root
     * can be used for prefixing paths in the views
     */
    public function setDocumentRoot($documentRoot) {
        $this->documentRoot = rtrim($documentRoot, '/');
        return $this;
    }

    /*
     * the $appDir/init.php contains the configuration of the current
     * application:
     * * routes
     * * libraries
     * * the setup handler
     */
    private function loadAppConfig() {
        require_once($this->appDir . '/init.php');

        return $this;
    }

    /*
     * debugging mode
     * enables pretty stack traces if something goes wrong
     */
    public function enableDebug() {
        $this->debug = true;
        return $this;
    }

    /*
     * adds a route
     * routes are simple pattern -> controller tuples
     *
     * normally, routes are strings, numeric routes are traditionally used for
     * http error codes (404, 500, ...)
     */
    public function route($route, $handler) {
        $this->routes[$route] = $handler;
        return $this;
    }

    /*
     * returns only routes that are a string
     * because numeric routes are special cases (http-status codes)
     */
    private function filterRouteMatches($arguments) {
        $results = array();
        foreach ($arguments as $key => $value)
            if (is_string($key))
                $results[$key] = $value;

        return $results;
    }

    /*
     * header redirection shortcut
     */
    public static function redirect($target) {
        header('Location: http://' . self::get()->documentRoot . '/' . ltrim($target, '/'), true, 303);
        die();
    }

    /*
     * create an instace of the controller and call it with the arguments
     * derived from the route regexp matches
     */
    private function dispatchController($handler, $method, $arguments) {
        $controllerName = $handler . 'Controller';
        $controller = new $controllerName;
        return call_user_func_array(array($controller, $method), $arguments);
    }

    /*
     * takes a request string, finds the matching route and calls the controller
     *
     * - if there's not matching route, call the special 404 controller
     * - if an error occurs, call the 500
     */
    public function dispatch($url) {
        try {
            // try if routes match, and use the first one found
            foreach ($this->routes as $route => $handler) {
                if (preg_match('/^' . str_replace('/', '\/', $route) . '$/', $url, $matches)) {
                    $arguments  = $this->filterRouteMatches($matches);
                    $method     = $_SERVER['REQUEST_METHOD'];
                    return $this->dispatchController($handler, $method, $arguments);
                }
            }
        } catch (Exception $e) {
            // there was an error, warning or notice somewhere in the handling
            // of this request: status 500 (we're strict even about notices!)
            if ($this->debug) {
                $realpath = realpath($this->appDir);

                // when debugging is turned on, set http response code to 500
                // and show the stacktrace
                header('', '', 500);

                $debugString = include($this->getRoughBirdPath() . '/rbStacktrace.php');
                return $debugString;
                
            } else {
                // if there is a custom error handling page and debugging is
                // turned off, call the 500-handler
                if (isset($this->routes[500])) {
                    return $this->dispatchController($this->routes[500], 'GET', array($url));
                }
            }
        }

        // no route matched and no error happened,
        // so see if theres a 404 handler registered
        if (isset($this->routes[404])) {
            return $this->dispatchController($this->routes[404], 'GET', array($url));
        }

        // if not ... error
        throw new Exception('No matching route found (Hint: Register a 404-Controller)', 001);
        return false;
    }

    /*
     * Libraries are located at the $app/libraries directory
     * if they need the be configured, either create a wrapper or do it in the
     * ($app)Setup class
     */
    public function requireLibrary($name) {
        require_once($this->getAppPath() . '/libraries/' . $name);
        return $this;
    }

    /*
     * Call the apps setup script. It's located at the root of the appDirectory
     * and called setup.php. It must contain a class named "($appName)Setup"
     * that takes no arguments for the constructor.
     */
    public function setup() {
        require_once($this->getAppPath() . '/setup.php');
        $classname = $this->getAppName() . 'Setup';
        $setup = new $classname();

        return $this;
    }
}

?>