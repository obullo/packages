<?php

namespace Obullo\Layer;

use stdClass;
use Obullo\Http\Controller;
use Psr\Log\LoggerInterface as Logger;
use Interop\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Layers is a programming technique that delivers you to "Multitier Architecture" 
 * to scale your applications.
 * 
 * Derived from Java HMVC pattern 2009 Named as "Layers" in Obullo 2015
 * 
 * @author Ersin Guvenc <eguvenc@gmail.com>
 */

/**
 * Layer
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Layer
{
    const CACHE_KEY = 'Layer:';

    /**
     * Layer uri string
     * 
     * @var string
     */
    protected $uri;

    /**
     * Process flag
     * 
     * @var boolean
     */
    protected $done = false;

    /**
     * Config
     * 
     * @var array
     */
    protected $params = array();

    /**
     * Unique connection string.
     *  
     * @var string
     */
    protected $hashString = null;

    /**
     * Layer error
     * 
     * @var null
     */
    protected $error = null;

    /**
     * Container
     * 
     * @var object
     */
    protected $container = null;

    protected $controller;
    protected $request;
    protected $router;
    protected static $path = array();

    /**
     * Constructor
     * 
     * @param object $container \Obullo\Container\ContainerInterface
     * @param array  $params    config parameters
     */
    public function __construct(Container $container, array $params)
    {
        $this->params = $params;
        $this->container = $container;

        register_shutdown_function(array($this, 'close'));  // Close current layer
    }

    /**
     * Create new http request
     * 
     * @param ServerRequestInterface $request psr7 request
     * @param string                 $uri     request uri
     * @param string                 $method  request method
     * @param array                  $data    any possible data
     * 
     * @return void
     */
    public function newRequest(ServerRequestInterface $request, $uri, $method = 'GET', $data = array())
    {
        $uri = '/'.trim($uri, '/');  // Normalize uri
        $this->hashString = '';      // Reset hash string otherwise it causes unique id errors.
        $this->setHash($uri);

        $this->controller = Controller::$instance;      // We need get backup object of main controller
        $this->request = clone Controller::$instance->request;
        $this->router = clone Controller::$instance->router;  // Create copy of original Router class.

        static::$path[] = $this->request->getUri()->getPath();

        if (static::$path[0] == $this->request->getUri()->getPath()) { // Only create global objects if the request is first.
            $this->container->share('request.0', $this->request);
            $this->container->share('router.0', $this->router);
        }
        $this->createUri($request, $uri);
        $this->setMethod($method, $data); // Must be at the end otherwise POST GET data does not work
    }

    /**
     * Create uri string
     * 
     * @param object $request request
     * @param string $uri     uri
     * 
     * @return void
     */
    protected function createUri(ServerRequestInterface $request, $uri)
    {
        // Create new request object

        $this->container->share('request', $request);
        
        $this->container->get('request')->getUri()->clear();     // Reset uri objects we will reuse it for layer
        $this->container->get('request')->getUri()->setPath($uri);

        $this->container->get('router')->clear();   // Reset router objects we will reuse it for layer
        $this->container->get('router')->init();
    }

    /**
     * Set Layer Request Method
     *
     * @param string $method layer method
     * @param array  $data   params
     * 
     * @return void
     */
    public function setMethod($method = 'GET', $data = array())
    {
        $this->setHash($data);
        $request = $this->container->get('request')->withMethod(strtoupper($method));

        if (empty($data)) {
            return;
        }
        if ($method == 'POST') {
            $request = $request->withParsedBody($data);
            $this->container->share('request', $request);
        } 
        if ($method == 'GET') {
            $request = $request->withQueryParams($data);
            $this->container->share('request', $request);
        }
    }

    /**
     * Execute layer
     * 
     * @return string
     */
    public function execute()
    {
        $id  = $this->getId();
        $uri = $this->container->get('request')->getUri();

        $this->uri = $uri->getPath();
        $uri->setPath($this->uri. '/' .$id); //  Create unique uri
        
        $router = $this->container->get('router');

        $directory = $router->getFolder();
        $className = $router->getClass();
        $method    = $router->getMethod();

        $class = FOLDERS .$router->getAncestor('/') .$directory.'/'.$className. '.php';
        $className = '\\'.$router->getNamespace().$className;

        if (! class_exists($className, false)) {
            include $class;
        }
        if (! is_file($class)) {
            return $this->show404($method);
        }
        $controller = new $className;
        $controller->container = $this->container;

        if (! method_exists($controller, $method)
        ) {
            return $this->show404($method);
        }
        ob_start();
        call_user_func_array(array($controller, $method), array_slice($uri->getRoutedSegments(), $router->getArity()));
        
        return ob_get_clean();
    }

    /**
     * Show404 output and reset layer variables
     * 
     * @param string $method current method
     * 
     * @return string 404 message
     */
    protected function show404($method)
    {   
        $this->reset();
        $this->setError(
            [
                'code' => '404',
                'error' => 'request not found',
                'uri' => $this->getUri().'/'.$method
            ]
        );
        return $this->getError();
    }

    /**
     * Reset router for mutiple layer requests
     * and close the layer connections.
     *
     * @return void
     */
    protected function reset()
    {
        if (! isset($_SERVER['LAYER_REQUEST'])) { // If no layer header return to null;
            return;
        }
        $this->clear();  // Reset all Layer variables.
    }

    /**
     * Reset all variables for multiple layer requests.
     *
     * @return void
     */
    public function clear()
    {
        $this->error = null;    // Clear variables otherwise all responses of layer return to same error.
        $this->done  = false;
    }

    /**
     * Restore original controller objects
     * 
     * @return void
     */
    public function restore()
    {
        $this->reset();
        $this->container->share('request', $this->request);
        $this->container->share('router', $this->router);

        Controller::$instance = $this->controller;
        Controller::$instance->router = $this->router;
        Controller::$instance->request = $this->request;
        $this->done = true;
    }

    /**
     * Create layer connection string next we will convert it to connection id.
     *
     * @param mixed $resource string
     *
     * @return void
     */
    protected function setHash($resource)
    {
        if (is_array($resource)) {
            if (sizeof($resource) > 0) {
                $this->hashString .= str_replace('"', '', json_encode($resource));
            }
            return;
        } 
        $this->hashString .= $resource;
    }

    /**
     * Returns to Cache key ( layer id ).
     * 
     * @return string
     */
    public function getId()
    {
        $hashString = trim($this->hashString);
        return self::CACHE_KEY. sprintf("%u", crc32((string)$hashString));
    }

    /**
     * Get last Layer uri
     * 
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set last response error
     *
     * @param array $error data
     * 
     * @return object
     */
    public function setError(array $error)
    {
        $this->error = $error;
        return $this;
    }

    /**
     * Get last response error
     * 
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Close Layer Connections
     * 
     * If we have any possible Layer exceptions
     * reset the router variables and restore all objects
     * to complete Layer process. Otherwise we see uncompleted request errors.
     * 
     * @return void
     */
    public function close()
    {
        if ($this->done == false) {  // If "done == true" we understand process completed successfully.
            $this->restore();        // otherwise process is failed and we need to restore variables.
            return;
        }
        $this->done = false;
    }
    
}