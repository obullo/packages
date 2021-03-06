<?php

namespace Obullo\Debugger;

use DOMDocument;
use RuntimeException;
use Interop\Container\ContainerInterface as Container;

/**
 * Debugger websocket 
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Websocket
{
    /**
     * Host
     * 
     * @var string
     */
    protected $host;

    /**
     * Port
     * 
     * @var int
     */
    protected $port;

    /**
     * App log data ( lines )
     *  
     * @var string
     */
    protected $lines;

    /**
     * Web socket
     * 
     * @var object
     */
    protected $socket;

    /**
     * App output
     * 
     * @var string
     */
    protected $output;

    /**
     * Websocket connect
     * 
     * @var boolean
     */
    protected $connect;

    /**
     * Container
     * 
     * @var object
     */
    protected $container;

    /**
     * Current uriString
     * 
     * @var object
     */
    protected $uriString;

    /**
     * Constructor
     * 
     * @param container $container app
     * @param array     $config    config
     */
    public function __construct(Container $container, array $config)
    {
        $this->container = $container;
        $this->uriString = $container->get('app')->request->getUri()->getPath();

        if (false == preg_match(
            '#(ws:\/\/(?<host>(.*)))(:(?<port>\d+))(?<url>.*?)$#i', 
            $config['socket'], 
            $matches
        )) {
            throw new RuntimeException(
                "Debugger socket connection error, example web socket configuration: ws://127.0.0.1:9000"
            );
        }
        $this->host = $matches['host'];
        $this->port = $matches['port'];
    }

    /**
     * Connecto debugger server
     * 
     * @return void
     */
    public function connect()
    {
        if (isset($_SERVER['argv'][0]) && substr($_SERVER['argv'][0], -4) == 'task') {  // Ignore for php task commands
            return;                                                                     // we use substr() for Windows and linux support
        }
        $this->socket  = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->connect = @socket_connect(
            $this->socket,
            $this->host,
            $this->port
        );
        if ($this->connect == false) {
            $uri = $this->container->get('app')->request->getUri();
            $allowedRoutes = [
                '/debugger/ping',
                '/debugger/disconnect',
            ];
            if (in_array($uri->getPath(), $allowedRoutes)) {
                return;
            }
            $message = "Debugger seems enabled. Run debug server or disable it from your config.";
            if ($this->container->get('app')->request->isAjax()) {
                $message = strip_tags($message);
            }
            throw new RuntimeException($message);
        }  
    }

    /**
     * Emit http request data for debugger
     *
     * @param string $output  output
     * @param string $payload log data
     * 
     * @return void
     */
    public function emit($output = null, $payload = array())
    {
        $this->output = $output;

        $formatter = new LogFormatter();      // Log debug handler
        $this->lines = $formatter->format($payload);

        if ($this->container->get('app')->request->isAjax()) {

            $cookies = $this->container->get('app')->request->getCookieParams();

            if (isset($cookies['o_debugger_active_tab']) && $cookies['o_debugger_active_tab'] != 'obulloDebugger-environment') {
                setcookie('o_debugger_active_tab', "obulloDebugger-ajax-log", 0, '/');  // Select ajax tab
            } elseif (! isset($cookies['o_debugger_active_tab'])) {
                setcookie('o_debugger_active_tab', "obulloDebugger-ajax-log", 0, '/'); 
            }
            $this->handshake('Ajax');
        } elseif (defined('STDIN')) { 
            $this->handshake('Cli');
        } else {
            $this->handshake('Http');
        }
    }

    /**
     * Retuns to encoded html output of current page
     * 
     * @return string
     */
    public function getOutput()
    {
        return htmlentities($this->output);
    }

    /**
     * Send interface request to websocket
     * 
     * @param string $type Ajax or Cli 
     * 
     * @return void
     */
    protected function handshake($type = 'Ajax') 
    {
        $env = new Environment(
            $this->container->get('app')->request,
            $this->container->get('session'),
            $this->getOutput()
        );
        $base64EnvData = base64_encode($env->printHtml());
        $base64LogData = base64_encode($this->lines);

        $upgrade = "Request: $type\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "Environment-data: ".$base64EnvData."\r\n".
        "Page-uri: ".md5($this->uriString)."\r\n".
        "Msg-id: ".uniqid()."\r\n";

        $css = self::parseCss($this->output);
        if (! empty($css)) {
            $upgrade.= "Page-css: ".base64_encode($css)."\r\n";
        }
        $upgrade.= "Log-data: ".$base64LogData."\r\n" .
        "WebSocket-Origin: $this->host\r\n" .
        "WebSocket-Location: ws://$this->host:$this->port\r\n";

        if ($this->socket === false || $this->connect == false) {
            return;
        }
        socket_write($this->socket, $upgrade, strlen($upgrade));
        socket_close($this->socket);
    }

    /**
     * Parse css files & get encoded contents of css
     * 
     * @param string $html pure html
     * 
     * @return mixed css content or null
     */
    protected static function parseCss($html)
    {
        // Preg match version
        // (<link .*)(type=(.*))(href="(.*?)")
    
        //  Dom version
        //  
        $doc = new DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_use_internal_errors(false);

        $doc->preserveWhiteSpace = false; 
        foreach ($doc->childNodes as $item) {
            if ($item->nodeType == XML_PI_NODE) {
                $doc->removeChild($item);
            }
        }
        $doc->encoding = 'UTF-8';
        $head = $doc->getElementsByTagName('head');

        if ($head->length == 0) { // If page has not got head tags return to null
            return;
        }
        $link = $head->item(0)->getElementsByTagName('link');
        $css = '';
        if ($link->length > 0) {
            foreach ($link as $linkRow) { 
                $href = $linkRow->getAttribute('href');
                if (! empty($href) && substr($href, -3) == 'css') {  // Check file type
                    $css.= '<link href="'.$href.'" type="text/css" rel="stylesheet">';
                }
            }
        }
        $style = $head->item(0)->getElementsByTagName('style'); // Include inline styles

        $inlineCss = '';
        if ($style->length > 0) {
            foreach ($style as $styleRow) {
                $inlineCss.= $styleRow->nodeValue."\n";
            }
            $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
            $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
        }
        return $css;
    }

}