<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\TranslatorBundle\Repository;

use Doctrine\ORM\EntityRepository;

class TranslationItemRepository extends EntityRepository
{/*
	public function getTranslationsToCommit($vendor, $bundle, $commit)
	{
        $dql = '
            SELECT i FROM Claroline\TranslatorBundle\Entity\TranslationItem i
            WHERE u.vendor LIKE :vendor
            AND i.bundle LIKE :bundle
            AND i.commit LIKE :commit
        ';
        $query = $this->_em->createQuery($dql);
        $query->setParameter('vendor', "%$vendor%");
        $query->setParameter('bundle', "%$bundle%");
        $query->setParameter('commit', "%$commit%");

        $items = $query->getResults();

        return $items;
	}*/
}
