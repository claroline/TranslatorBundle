<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\TranslatorBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity()
 * @ORM\Table(name="claro__git_translation_item")
 */
class TranslationItem
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="translation_key", type="text")
     */
    protected $key;

    /**
     * @ORM\Column(name="translation_value", type="text")
     */
    protected $translation;

    /**
     * @ORM\Column(name="domain", type="text")
     */
    protected $domain;

    /**
     * @ORM\Column(name="commit_hash", type="text")
     */
    protected $commit;

    /**
     * @ORM\Column(name="lang", type="text")
     */
    protected $lang;

    /**
     * @ORM\Column(name="creation_date", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $creationDate;

    /**
     * @ORM\Column(name="vendor", type="text")
     */
    protected $vendor;

    /**
     * @ORM\Column(name="bundle", type="text")
     */
    protected $bundle;

    public function getId()
    {
        return $this->id;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setTranslation($translation)
    {
        $this->translation = $translation;
    }

    public function getTranslation()
    {
        return $this->translation;
    }

    public function setCommit($commit)
    {
        $this->commit = $commit;
    }

    public function getCommit()
    {
        return $this->commit;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function setCreationDate(\DateTime $creationDate)
    {
        $this->creationDate = $creationDate;
    }

    public function getCreationDate()
    {
        return $this->creationDate;
    }

    public function setBundle($bundle)
    {
        $this->bundle = $bundle;
    }

    public function getBundle()
    {
        return $this->bundle;
    }

    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
    }

    public function getVendor()
    {
        return $this->vendor;
    }
} 