<?php

namespace Obullo\Authentication\Middleware;

trait UniqueSessionTrait
{
     /**
     * Terminates multiple sessions.
     * 
     * @return void
     */
    public function killSessions()
    {
        $container = $this->getContainer();
        $sessions  = $container->get('user')->storage->getUserSessions();

        if (empty($sessions) || sizeof($sessions) == 1) {  // If user have more than one session continue to destroy old sessions.
            return;
        }
        $sessionKeys = array();  
        foreach ($sessions as $key => $val) {       // Keep the last session
            $sessionKeys[$val['__time']] = $key;
        }
        $lastSession = max(array_keys($sessionKeys));   // Get the highest integer time
        $protectedSession = $sessionKeys[$lastSession];
        unset($sessions[$protectedSession]);            // Don't touch the current session

        foreach (array_keys($sessions) as $loginID) {   // Destroy all other sessions
            $container->get('user')->identity->kill($loginID);
        }
    }

}