<?php

namespace Obullo\Container\ServiceProvider;

class User extends AbstractServiceProvider
{
    /**
     * The provides array is a way to let the container
     * know that a service is provided by this service
     * provider. Every service that is registered via
     * this service provider must have an alias added
     * to this array or it will be ignored.
     *
     * @var array
     */
    protected $provides = [
        'user',
        'auth.storage',
        'auth.identity',
        'auth.login',
        'auth.model',
        'auth.adapter'
    ];

    /**
     * This is where the magic happens, within the method you can
     * access the container and register or retrieve anything
     * that you need to, but remember, every alias registered
     * within this method must be declared in the `$provides` array.
     *
     * @return void
     */
    public function register()
    {
        $container = $this->getContainer();
        $params    = $this->getConfiguration('user')->getParams();

        $container->share('user', 'Obullo\Authentication\User')
            ->withArgument($container);

        $container->share('auth.storage', $params['cache']['storage'])
            ->withArgument($container)
            ->withArgument($container->get('request'))
            ->withArgument($container->get('session'))
            ->withArgument($params);

        $container->share('auth.identity', 'Auth\Identity')
            ->withArgument($container)
            ->withArgument($container->get('request'))
            ->withArgument($container->get('session'))
            ->withArgument($container->get('auth.storage'))
            ->withArgument($params);

        $container->share('auth.login', 'Obullo\Authentication\User\Login')
            ->withArgument($container)
            ->withArgument($container->get('auth.storage'))
            ->withArgument($params);

        $container->share('auth.model', $params['db.model'])
            ->withArgument($params)
            ->withMethodCall('setContainer', [$container])
            ->withMethodCall('connect');

        $container->share('auth.adapter', $params['db.adapter'])
            ->withArgument($container)
            ->withArgument($container->get('session'))
            ->withArgument($container->get('auth.storage'))
            ->withArgument($params);

    }
}