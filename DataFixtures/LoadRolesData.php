<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\TranslatorBundle;

use Claroline\CoreBundle\DataFixtures\Required\RequiredFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Platform roles data fixture.
 */
class LoadRolesData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $repo = $manager->getRepository('ClarolineCoreBundle:Role');
        $roleManager = $this->container->get('claroline.manager.role_manager');
        if (!$repo->findByName('ROLE_TRANSLATOR')) $roleManager->createBaseRole('ROLE_TRANSLATOR', 'translator');
        if (!$repo->findByName('ROLE_TRANSLATOR_ADMIN')) $roleManager->createBaseRole('ROLE_TRANSLATOR_ADMIN', 'role_translator_admin');
    }

    public function getOrder()
    {
        return 1;
    }
}
