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
	public function findLastTranslations($vendor, $bundle, $commit, $lang, $showAll = true)
	{

		$dql = 'SELECT i, t FROM Claroline\TranslatorBundle\Entity\TranslationItem i
			LEFT JOIN i.translations t
			WHERE i.vendor LIKE :vendor AND
			i.bundle LIKE :bundle AND
			i.commit LIKE :commit AND
			i.lang LIKE :lang';

		if (!$showAll) {
			$dql .= ' AND i.isAdminLocked = false';
		}

		$dql .= ' ORDER BY i.key';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('vendor', $vendor);
        $query->setParameter('bundle', $bundle);
        $query->setParameter('commit', $commit); 
        $query->setParameter('lang', $lang);

        return $query->getResult();
	}

	public function searchLastTranslations($vendor, $bundle, $commit, $lang, $search, $showAll = true)
	{
		$dql = 'SELECT i, t FROM Claroline\TranslatorBundle\Entity\Translation t
			LEFT JOIN t.translationItem i
			WHERE i.vendor LIKE :vendor AND
			i.bundle LIKE :bundle AND
			i.commit LIKE :commit AND
			i.lang LIKE :lang AND
			(
				t.translation LIKE :search OR 
				i.key LIKE :search
			) AND NOT EXISTS (
				SELECT t2 FROM Claroline\TranslatorBundle\Entity\Translation t2
				LEFT JOIN t2.translationItem i2 
				WHERE t2.id > t.id
				AND i2.id = i.id
			)
		';

		if (!$showAll) {
			$dql .= ' AND i.isAdminLocked = false';
		}

		$dql .= ' ORDER BY i.key';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('vendor', $vendor);
        $query->setParameter('bundle', $bundle);
        $query->setParameter('commit', $commit);
        $query->setParameter('lang', $lang);
        $query->setParameter('search', "%{$search}%");

        return $query->getResult();
	}
}