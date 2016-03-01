<?php

namespace Obullo\Log\Pusher;

use Exception;
use League\Container\ImmutableContainerAwareTrait;
use League\Container\ImmutableContainerAwareInterface;

/**
 * Send log data to queue to listen log events from "app/classes/Workers/Logger" class.
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Queue implements PusherInterface, ImmutableContainerAwareInterface
{
    use ImmutableContainerAwareTrait;

    /**
     * Push the data
     * 
     * @param array $data log data
     * 
     * @return void
     */
    public function push(array $data)
    {
        try {

            $container = $this->getContainer();
            $params = $container->get('logger.params');

            $container->get('queue')->push(
                'Workers@Logger',
                $params['queue']['job'],
                $data,
                $params['queue']['delay']
            );
        
        } catch (Exception $e) {

            $exception = new \Obullo\Error\Exception;
            echo $exception->make($e);
        }
    }

}
