<?php

namespace Obullo\Http\Response;

use RuntimeException;
use Psr\Http\Message\ResponseInterface;

class SapiEmitter implements EmitterInterface
{
    /**
     * Emits a response for a PHP SAPI environment.
     *
     * Emits the status line and headers via the header() function, and the
     * body content via the output buffer.
     *
     * @param ResponseInterface $response       response
     * @param null|int          $maxBufferLevel Maximum output buffering level to unwrap.
     */
    public function emit(ResponseInterface $response, $maxBufferLevel = null)
    {
        if (headers_sent()) {
            throw new RuntimeException('Unable to emit response; headers already sent');
        }
        $this->emitCookieHeaders($response);  // This is not zend standart require for Obullo
        $this->emitStatusLine($response);
        $this->emitHeaders($response);
        $this->emitBody($response, $maxBufferLevel);
    }

    /**
     * Set cookie headers
     * 
     * @param Response $response Response
     * 
     * @return object ResponseInterface
     */
    protected function emitCookieHeaders(ResponseInterface $response)
    {
        if ($headers = $response->getCookies()) {
            foreach ($headers as $value) {
                header(
                    sprintf("%s: %s", 'Set-Cookie', $value),
                    false
                );
            }
        }
    }

    /**
     * Emit the status line.
     *
     * Emits the status line using the protocol version and status code from
     * the response; if a reason phrase is availble, it, too, is emitted.
     *
     * @param ResponseInterface $response response
     */
    private function emitStatusLine(ResponseInterface $response)
    {
        $reasonPhrase = $response->getReasonPhrase();

        header(
            sprintf(
                'HTTP/%s %d%s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                ($reasonPhrase ? ' ' . $reasonPhrase : '')
            )
        );
    }

    /**
     * Emit response headers.
     *
     * Loops through each header, emitting each; if the header value
     * is an array with multiple values, ensures that each is sent
     * in such a way as to create aggregate headers (instead of replace
     * the previous).
     *
     * @param ResponseInterface $response response
     */
    private function emitHeaders(ResponseInterface $response)
    {
        foreach ($response->getHeaders() as $header => $values) {
            $name  = $this->filterHeader($header);
            $first = true;
            foreach ($values as $value) {
                header(
                    sprintf(
                        '%s: %s',
                        $name,
                        $value
                    ),
                    $first
                );
                $first = false;
            }
        }
    }

    /**
     * Emit the message body.
     *
     * Loops through the output buffer, flushing each, before emitting
     * the response body using `echo()`.
     *
     * @param ResponseInterface $response       response
     * @param int               $maxBufferLevel Flush up to this buffer level.
     */
    private function emitBody(ResponseInterface $response, $maxBufferLevel)
    {
        if (null === $maxBufferLevel) {
            $maxBufferLevel = ob_get_level();
        }

        while (ob_get_level() > $maxBufferLevel) {
            ob_end_flush();
        }

        echo $response->getBody();
    }

    /**
     * Filter a header name to wordcase
     *
     * @param string $header header
     * 
     * @return string
     */
    private function filterHeader($header)
    {
        $filtered = str_replace('-', ' ', $header);
        $filtered = ucwords($filtered);
        return str_replace(' ', '-', $filtered);
    }
}
