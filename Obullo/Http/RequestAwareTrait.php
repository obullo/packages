<?php

namespace Obullo\Http;

trait RequestAwareTrait
{
    /**
     * Request
     * 
     * @var array
     */
    protected $request;

    /**
     * Set params
     *
     * @param object $request Psr\Http\Message\ServerRequestInterface;
     * 
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get params
     *
     * @return array
     */
    public function getRequest()
    {
        return $this->request;
    }
}
