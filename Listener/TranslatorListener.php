<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\TranslatorBundle\Listener;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Claroline\CoreBundle\Event\PluginOptionsEvent;

/**
 * @DI\Service("claroline.listener.translator_listener")
 */
class TranslatorListener extends ContainerAware
{
    /**
     * @DI\InjectParams({
     *     "container" = @DI\Inject("service_container")
     * })
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @DI\Observe("plugin_options_translatorbundle")
     */
    public function onOpenAdministration(PluginOptionsEvent $event)
    {
        $requestStack = $this->container->get('request_stack');
        $httpKernel = $this->container->get('http_kernel');
        $request = $requestStack->getCurrentRequest();
        $params = array('_controller' => 'ClarolineTranslatorBundle:Translator:AdminOpen');
        $subRequest = $request->duplicate(array(), null, $params);
        $response = $httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $event->setResponse($response);
        $event->stopPropagation();
    }
}
