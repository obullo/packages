<?php

namespace Obullo\Application;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Obullo\Container\ParamsAwareInterface;
use League\Container\ContainerAwareInterface;
use League\Container\ImmutableContainerAwareInterface;
use Obullo\Http\Controller\ControllerAwareInterface;
use Obullo\Http\Controller\ImmutableControllerAwareInterface;

use ReflectionClass;
use Obullo\Router\RouterInterface as Router;
use Obullo\Application\MiddlewareStackInterface as Middleware;

/**
 * Http Application
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Http extends Application
{
    /**
     * Dispatch error
     * 
     * @var boolean
     */
    protected $error;

    /**
     * Current controller
     * 
     * @var object
     */
    protected $controller;

    /**
     * Constructor
     *
     * @return void
     */
    public function init()
    {
        $container = $this->getContainer();  // Make global
        $app = $container->get('app');

        include APP .'errors.php';

        $this->registerErrorHandlers();

        include APP .'providers.php';

        $container->share('router', 'Obullo\Router\Router')
            ->withArgument($container)
            ->withArgument($container->get('request'))
            ->withArgument($container->get('logger'));

        $middleware = $container->get('middleware');

        include APP .'middlewares.php';

        $router = $container->get('router');

        include APP .'routes.php';

        $this->boot($router, $middleware);
    }

    /**
     * Register assigned middlewares
     *
     * @param object $router     router
     * @param object $middleware middleware
     * 
     * @return void
     */
    protected function boot(Router $router, Middleware $middleware)
    {
        $router->init();

        include FOLDERS .$router->getPrimaryFolder('/').$router->getFolder('/').$router->getClass().'.php';

        $className = '\\'.$router->getNamespace().$router->getClass();
        $method    = $router->getMethod();

        if (! class_exists($className, false)) {
            $router->clear();  // Fix layer errors.
            $this->error = true;
        } else {
            $this->controller = new $className($this->container);
            $this->controller->setContainer($this->container);

            if ($method == 'setContainer' 
                || $method == 'getContainer' 
                || ! method_exists($this->controller, $method)
                || substr($method, 0, 1) == '_'
            ) {
                $router->clear();  // Fix layer errors.
                $this->error = true;
            }
        }
        $this->bootAnnotations($method);
        $this->bootMiddlewares($router, $middleware);
    }

    /**
     * Boot middlewares
     * 
     * @param object $router     router
     * @param object $middleware middleware
     * 
     * @return void
     */
    protected function bootMiddlewares(Router $router, Middleware $middleware)
    {
        $object = null;
        $request = $this->container->get('request');
        $uriString = $request->getUri()->getPath();

        if ($attach = $router->getAttach()) {

            foreach ($attach->getArray() as $value) {

                $attachRegex = str_replace('#', '\#', $value['attach']);  // Ignore delimiter

                if ($value['route'] == $uriString) {     // if we have natural route match
                    $object = $middleware->add($value['name']);
                } elseif (ltrim($attachRegex, '.') == '*' || preg_match('#'. $attachRegex .'#', $uriString)) {
                    $object = $middleware->add($value['name']);
                }
                if ($object instanceof ParamsAwareInterface && ! empty($value['options'])) {  // Inject parameters
                    $object->setParams($value['options']);
                }
            }
        }
        if ($this->container->get('config')['extra']['debugger']) {  // Boot debugger
            $middleware->add('Debugger');
        }
        $this->inject($middleware);
    }

    /**
     * Inject controller object
     * 
     * @param object $middleware middleware
     * 
     * @return void
     */
    protected function inject(Middleware $middleware)
    {
        foreach ($middleware->getNames() as $name) {
            $object = $middleware->get($name);
            if ($object instanceof ImmutableContainerAwareInterface || $object instanceof ContainerAwareInterface) {
                $object->setContainer($this->getContainer());
            }
            if ($this->controller != null && $object instanceof ImmutableControllerAwareInterface || $object instanceof ControllerAwareInterface) {
                $object->setController($this->controller);
            }
        }
    }

    /**
     * Read controller annotations
     * 
     * @param string $method method
     * 
     * @return void
     */
    protected function bootAnnotations($method)
    {
        if ($this->container->get('config')['extra']['annotations'] && $this->controller != null) {
            $reflector = new ReflectionClass($this->controller);
            if ($reflector->hasMethod($method)) {
                $docs = new \Obullo\Application\Annotations\Controller;
                $docs->setContainer($this->getContainer());
                $docs->setReflectionClass($reflector);
                $docs->setMethod($method);
                $docs->parse();
            }
        }
    }

    /**
     * Execute the controller
     *
     * @param Psr\Http\Message\RequestInterface  $request  request
     * @param Psr\Http\Message\ResponseInterface $response response
     * 
     * @return mixed
     */
    public function call(Request $request, Response $response)
    {
        if ($this->error) {
            return false;
        }
        $this->container->share('response', $response);  // Refresh objects
        $this->container->share('request', $request);

        $router = $this->container->get('router');

        $result = call_user_func_array(
            array(
                $this->controller,
                $router->getMethod()
            ),
            array_slice($this->controller->request->getUri()->getRoutedSegments(), $router->getArgumentFactor())
        );
        if ($result instanceof Response) {
            $response = $result;
        }
        return $response;   
    }

}