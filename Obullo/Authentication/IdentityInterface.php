<?php

namespace Obullo\Authentication;

use Obullo\Session\SessionInterface as Session;
use Interop\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Obullo\Authentication\Storage\StorageInterface as Storage;

/**
 * Identity Interface
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
interface IdentityInterface
{
    /**
     * Constructor
     *
     * @param object $container container
     * @param object $request   psr7 request
     * @param object $session   storage
     * @param object $storage   auth storage
     * @param object $params    auth config parameters
     */
    public function __construct(Container $container, Request $request, Session $session, Storage $storage, array $params);

    /**
     * Initializer
     *
     * @return void
     */
    public function initialize();

    /**
     * Check user has identity
     *
     * Its ok if returns to true otherwise false
     *
     * @return boolean
     */
    public function check();

    /**
     * Opposite of check() function
     *
     * @return boolean
     */
    public function guest();

    /**
     * Check recaller cookie exists
     *
     * @return string|boolean false
     */
    public function recallerExists();

    /**
     * Returns to "1" if user authenticated on temporary memory block otherwise "0".
     *
     * @return boolean
     */
    public function isTemporary();

    /**
     * Set expire time
     * 
     * @param int $ttl expire
     * 
     * @return void
     */
    public function expire($ttl);

    /**
     * Check user is expired
     *
     * @return boolean
     */
    public function isExpired();

    /**
     * Move permanent identity to temporary block
     * 
     * @return void
     */
    public function makeTemporary();

    /**
     * Move temporary identity to permanent block
     * 
     * @return void
     */
    public function makePermanent();

    /**
     * Checks new identity data available in storage.
     *
     * @return boolean
     */
    public function exists();

    /**
     * Returns to unix microtime value.
     *
     * @return string
     */
    public function getTime();

    /**
     * Get all identity attributes
     *
     * @return array
     */
    public function getArray();

    /**
     * Get the password needs rehash array.
     *
     * @return mixed false|string new password hash
     */
    public function getPasswordNeedsReHash();

    /**
     * Returns to "1" user if used remember me
     *
     * @return integer
     */
    public function getRememberMe();

    /**
     * Returns to remember token
     *
     * @return integer
     */
    public function getRememberToken();

    /**
     * Sets authority of user to "0" don't touch to cached data
     *
     * @return void
     */
    public function logout();

    /**
     * Logout User and destroy cached identity data
     *
     * @return void
     */
    public function destroy();

    /**
     * Update temporary credentials
     * 
     * @param string $key key
     * @param string $val value
     * 
     * @return void
     */
    public function updateTemporary($key, $val);

    /**
     * Update remember token if it exists in the memory and browser header
     *
     * @return int|boolean
     */
    public function updateRememberToken();

    /**
     * Refresh the rememberMe token
     *
     * @param array $credentials credentials
     *
     * @return int|boolean
     */
    public function refreshRememberToken(array $credentials);

    /**
     * Removes rememberMe cookie from user browser
     *
     * @return void
     */
    public function forgetMe();

    /**
     * Kill authority of user using login ids
     * 
     * @param integer $loginId e.g: 87060e89
     * 
     * @return boolean
     */
    public function kill($loginId);

}