<?php

namespace Obullo\Cli\Task;

use Obullo\Cli\Console;
use Obullo\Cli\Controller;

class App extends Controller
{
    /**
     * Execute command
     * 
     * @return boolean
     */
    public function index()
    {
        $this->help();
    }

    /**
     * Print logo
     * 
     * @return string
     */
    public function logo()
    {
        echo Console::logo("Welcome to Application Manager (c) 2016");
        echo Console::newline(1);
    }

    /**
     * Enter the maintenance mode
     *
     * @param string $name domain
     * 
     * @return void
     */
    public function down($name = null)
    {
        $uri = $this->request->getUri();
        $name = (empty($name)) ? $uri->argument('name', 'root') : $name;
        $this->isEmpty($name);

        $newArray = $this->config->get('maintenance');
        $newArray[$name]['maintenance'] = 'down';

        $this->config->write('maintenance', $newArray);

        echo Console::fail("Application ".Console::foreground($name, 'red')." down for maintenance.");
    }

    /**
     * Leave from maintenance mode
     *
     * @param string $name domain
     * 
     * @return void
     */
    public function up($name = null)
    {
        $uri = $this->request->getUri();
        $name = (empty($name)) ? $uri->argument('name', 'root') : $name;
        $this->isEmpty($name);

        $newArray = $this->config->get('maintenance');
        $newArray[$name]['maintenance'] = 'up';

        $this->config->write('maintenance', $newArray);

        echo Console::success("Application ".Console::foreground($name, 'green')." up.");
    }

    /**
     * Check --name is empty
     * 
     * @param string $name route name
     * 
     * @return void
     */
    protected function isEmpty($name)
    {
        if (empty($name)) {
            echo Console::fail('Application "--name" can\'t be empty.');
            exit;
        }
        $maintenance = $this->config->get('maintenance');
        if (! isset($maintenance[$name])) {
            echo Console::fail('Application name "'.ucfirst($name).'" must be defined in your maintenance.php config file.');
            die;
        }
    }

    /**
     * Cli help
     * 
     * @return void
     */
    public function help()
    {
        $this->logo();

echo Console::newline(1);
echo Console::help("Help:", true);
echo Console::newline(1);
echo Console::help("
Available Commands

    down     : Sets domain down to enter maintenance mode.
    up       : Sets domain up to leaving from maintenance mode.

Available Arguments

    name   : Sets domain name."
);
echo Console::newline(2);
echo Console::help("Usage:", true);
echo Console::newline(2);
echo Console::help("php task app [command] name");
echo Console::newline(2);
echo Console::help("Description:", true);
echo Console::newline(2);
echo Console::help("Manages domain maintenances which are defined in your maintenance.php file.");
echo Console::newline(2);
    }
}