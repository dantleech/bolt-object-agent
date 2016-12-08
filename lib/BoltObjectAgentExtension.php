<?php

namespace Psi\Bridge\ObjectAgent\Bolt;

use Bolt\Extension\AbstractExtension;
use Silex\ServiceProviderInterface;
use Silex\Application;

class BoltObjectAgentExtension extends AbstractExtension implements ServiceProviderInterface
{
    public function registerNutCommands(Application $app)
    {
    }

    public function register(Application $app) 
    {
    }

    public function boot(Application $app)
    {
    }

    public function getServiceProviders()
    {
        return [ $this ];
    }
}
