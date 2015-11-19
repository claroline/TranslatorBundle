<?php

namespace Claroline\TranslatorBundle;

use Claroline\CoreBundle\Library\PluginBundle;
use Claroline\KernelBundle\Bundle\ConfigurationBuilder;
use Claroline\TranslatorBundle\Installation\AdditionalInstaller;

/**
 * Bundle class.
 * Uncomment if necessary.
 */
class ClarolineTranslatorBundle extends PluginBundle
{
    public function getConfiguration($environment)
    {
        $config = new ConfigurationBuilder();
        return $config->addRoutingResource(__DIR__ . '/Resources/config/routing.yml', null, 'translator');
    }

    public function hasMigrations()
    {
        return true;
    }

    public function getRequiredFixturesDirectory($environment)
    {
        return $environment !== 'test' ? 'DataFixtures' : null;
    }

}
