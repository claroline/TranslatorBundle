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
use Doctrine\ORM\Query;

class TranslationItemRepository extends EntityRepository
{
	public function findLastTranslations($vendor, $bundle, $commit, $lang, $page)
	{
		$dql = 'SELECT i FROM Claroline\TranslatorBundle\Entity\TranslationItem i
			WHERE i.vendor LIKE :vendor AND
			i.bundle LIKE :bundle AND
			i.commit LIKE :commit AND
			i.lang LIKE :lang  
		';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('vendor', $vendor);
        $query->setParameter('bundle', $bundle);
        $query->setParameter('commit', $commit);
        $query->setParameter('lang', $lang);

        return $query->getResult();
	}
}