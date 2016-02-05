<?php

namespace Obullo\Authentication\User;

use Obullo\Authentication\Token;
use Obullo\Authentication\Recaller;

use Obullo\Authentication\AbstractIdentity;
use Obullo\Session\SessionInterface as Session;
use Interop\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Obullo\Authentication\Storage\StorageInterface as Storage;

/**
 * User Identity
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Identity extends AbstractIdentity
{
    /**
     * Auth configuration params
     * 
     * @var array
     */
    protected $params;

    /**
     * Session
     * 
     * @var object
     */
    protected $session;

    /**
     * Memory Storage
     *
     * @var object
     */
    protected $storage;

    /**
     * Request
     * 
     * @var object
     */
    protected $request;

    /**
     * Container
     * 
     * @var object
     */
    protected $container;

    /**
     * Keeps unique session login ids to destroy them
     * in destruct method.
     * 
     * @var array
     */
    protected $killSignal = array();

    /**
     * Constructor
     *
     * @param object $container container
     * @param object $request   psr7 request
     * @param object $session   storage
     * @param object $storage   auth storage
     * @param object $params    auth config parameters
     */
    public function __construct(Container $container, Request $request, Session $session, Storage $storage, array $params)
    {
        $this->params = $params;
        $this->request = $request;
        $this->session = $session;
        $this->storage = $storage;
        $this->container = $container;

        $this->initialize();

        if ($rememberToken = $this->recallerExists()) {   // Remember the user if recaller cookie exists

            $recaller = new Recaller($container, $storage, $container->get('auth.model'), $this, $params);
            $recaller->recallUser($rememberToken);

            $this->initialize();  // We need initialize again otherwise ignoreRecaller() does not work in Login class.
        }
        if ($this->params['middleware']['unique.session']) {
            
            register_shutdown_function(array($this, 'close'));
        }
    }

    /**
     * Initializer
     *
     * @return void
     */
    public function initialize()
    {
        if ($this->attributes = $this->storage->getCredentials('__permanent')) {
            $this->__isTemporary = 0;                   // Refresh memory key expiration time
            $this->setCredentials($this->attributes);
            if ($this->isExpired()) {
                $this->destroy();
            }
            return;
        }
        $this->attributes = $this->storage->getCredentials('__temporary');
    }

    /**
     * Check user has identity
     *
     * Its ok if returns to true otherwise false
     *
     * @return boolean
     */
    public function check()
    {        
        if ($this->get('__isAuthenticated') == 1) {
            return true;
        }
        return false;
    }

    /**
     * Opposite of check() function
     *
     * @return boolean
     */
    public function guest()
    {
        if ($this->check()) {
            return false;
        }
        return true;
    }

    /**
     * Check recaller cookie exists 
     * 
     * WARNING : To test this function remove "Auth/Identifier" value from session 
     * or use "$this->user->identity->destroy()" method.
     *
     * @return string|boolean false
     */
    public function recallerExists()
    {
        if ($this->session->get('Auth/IgnoreRecaller') == 1) {
            $this->session->remove('Auth/IgnoreRecaller');
            return false;
        }
        $name  = $this->params['login']['rememberMe']['cookie']['name'];
        $cookies = $this->request->getCookieParams();
        $token = isset($cookies[$name]) ? $cookies[$name] : false;

        if ($this->guest() && ctype_alnum($token) && strlen($token) == 32) {  // Check recaller cookie value is alfanumeric
            return $token;
        }
        return false;
    }

    /**
     * Returns to "1" if user authenticated on temporary memory block otherwise "0".
     *
     * @return boolean
     */
    public function isTemporary()
    {
        return $this->get('__isTemporary');
    }

    /**
     * Set time to live
     * 
     * @param int $ttl expire
     * 
     * @return void
     */
    public function expire($ttl)
    {
        $data = $this->storage->getCredentials('__permanent');
        $data['__expire'] = time() + $ttl;
        $this->storage->setCredentials($data, null, '__permanent');
    }

    /**
     * Check identity is expired
     * 
     * @return boolean
     */
    protected function isExpired()
    {
        if ($this->get('__expire') < time()) {
            return true;
        }
        return false;
    }

    /**
     * Move permanent identity to temporary block
     * 
     * @return void
     */
    public function makeTemporary() 
    {
        $this->storage->makeTemporary();
    }

    /**
     * Move temporary identity to permanent block
     * 
     * @return void
     */
    public function makePermanent() 
    {
        $this->storage->makePermanent();
    }

    /**
     * Check user is verified after succesfull login
     *
     * @return boolean
     */
    public function isVerified()
    {
        if ($this->get('__isVerified') == 1) {
            return true;
        }
        return false;
    }

    /**
     * Checks new identity data available in storage.
     *
     * @return boolean
     */
    public function exists()
    {
        if ($this->get('__isAuthenticated') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Returns to unix microtime value.
     *
     * @return string
     */
    public function getTime()
    {
        return $this->get('__time');
    }

    /**
     * Get all identity attributes
     *
     * @return array
     */
    public function getArray()
    {
        if (is_array($this->attributes)) {
            ksort($this->attributes);
        }
        return $this->attributes;
    }

    /**
     * Get the password needs rehash array.
     *
     * @return mixed false|string new password hash
     */
    public function getPasswordNeedsReHash()
    {
        return $this->has('__passwordNeedsRehash') ? $this->get('__passwordNeedsReHash')['hash'] : false;
    }

    /**
     * Returns to "1" user if used remember me
     *
     * @return integer
     */
    public function getRememberMe()
    {
        return $this->has('__rememberMe') ? $this->get('__rememberMe') : 0;
    }

    /**
     * Returns to remember token
     *
     * @return integer
     */
    public function getRememberToken()
    {
        return $this->has('__rememberToken') ? $this->get('__rememberToken') : false;
    }

    /**
     * Sets authority of user to "0" don't touch to cached data
     *
     * @return void
     */
    public function logout()
    {
        $credentials = $this->storage->getCredentials('__permanent');
        $credentials['__isAuthenticated'] = 0;        // Sets memory auth to "0".

        $this->updateRememberToken();
        $this->storage->setCredentials($credentials, null, '__permanent');
    }

    /**
     * Logout User and destroy cached identity data
     *
     * @param string $block block
     * 
     * @return void
     */
    public function destroy($block = '__permanent')
    {
        $this->updateRememberToken();
        $this->storage->deleteCredentials($block);
    }

    /**
     * Update temporary credentials
     * 
     * @param string $key key
     * @param string $val value
     * 
     * @return void
     */
    public function updateTemporary($key, $val)
    {
        $this->storage->update($key, $val, '__temporary');
    }

    /**
     * Update remember token if it exists in the memory and browser header
     *
     * @return int|boolean
     */
    public function updateRememberToken()
    {
        if ($this->getRememberMe() == 1) {  // If user checked rememberMe option

            $rememberMeCookie = $this->params['login']['rememberMe']['cookie'];
            $rememberToken    = $this->container->get('cookie')->get($rememberMeCookie['name'], $rememberMeCookie['prefix']);

            $credentials = [
                $this->params['db.identifier'] => $this->getIdentifier(),
                '__rememberToken' => $rememberToken
            ];
            $this->setCredentials($credentials);

            return $this->refreshRememberToken($credentials);
        }
    }

    /**
     * Refresh the rememberMe token
     *
     * @param array $credentials credentials
     *
     * @return int|boolean
     */
    public function refreshRememberToken(array $credentials)
    {
        $token = Token::getRememberToken($this->container->get('cookie'), $this->params);

        return $this->container->get('auth.model')->updateRememberToken($token, $credentials); // refresh rememberToken
    }

    /**
     * Removes rememberMe cookie from user browser
     *
     * @return void
     */
    public function forgetMe()
    {
        $this->container->get('cookie')->delete($this->params['login']['rememberMe']['cookie']);  // Delete rememberMe cookie if exists
    }

    /**
     * Public function
     * 
     * Validate a user against the given credentials.
     * 
     * @param array $credentials user credentials
     * 
     * @return bool
     */
    public function validate(array $credentials)
    {
        $password = $this->params['db.password'];
        $plain    = $credentials[$password];

        return password_verify($plain, $this->getPassword());
    }

    /**
     * Kill authority of user using auth id
     * 
     * @param integer $loginId e.g: 87060e89
     * 
     * @return boolean
     */
    public function killSignal($loginId)
    {
        $this->killSignal[$loginId] = $loginId;
    }

    /**
     * Do finish operations
     * 
     * @return void
     */
    public function close()
    {
        if (empty($this->killSignal)) {
            return;
        }
        foreach ($this->killSignal as $loginId) {
            $this->storage->killSession($loginId);
        }
    }
}