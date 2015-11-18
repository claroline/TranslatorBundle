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
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Accessor;

/**
 * @ORM\Entity(repositoryClass="Claroline\TranslatorBundle\Repository\TranslationItemRepository")
 * @ORM\Table(name="claro__git_translation_item")
 */
class TranslationItem
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"translator"})
     * @SerializedName("id")
     */
    private $id;

    /**
     * @ORM\Column(name="translation_key", type="text")
     * @Groups({"translator"})
     * @SerializedName("key")
     */
    protected $key;

    /**
     * @ORM\Column(name="translation_value", type="text")
     * @Groups({"translator", "infos"})     
     * @SerializedName("translation")
     */
    protected $translation;

    /**
     * @ORM\Column(name="domain", type="text")
     * @Groups({"translator"})
     * @SerializedName("domain")
     */
    protected $domain;

    /**
     * @ORM\Column(name="commit_hash", type="text")
     * @Groups({"translator"})
     * @SerializedName("commit")
     */
    protected $commit;

    /**
     * @ORM\Column(name="lang", type="text")
     * @Groups({"translator"})
     * @SerializedName("lang")
     */
    protected $lang;

    /**
     * @ORM\Column(name="creation_date", type="datetime")
     * @Gedmo\Timestampable(on="create")
     * @Groups({"translator", "infos"})
     * @SerializedName("creationDate")
     */
    protected $creationDate;

    /**
     * @ORM\Column(name="vendor", type="text")
     * @Groups({"translator"})
     * @SerializedName("vendor")
     */
    protected $vendor;

    /**
     * @ORM\Column(name="bundle", type="text")
     * @Groups({"translator"})
     * @SerializedName("bundle")
     */
    protected $bundle;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\User",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     */
    protected $creator;

    /**  
     * @SerializedName("author")
     * @Accessor(getter="getAuthor")
     * @Groups({"infos"})
     */
    protected $author;

    /**
     * @ORM\Column(name="user_lock", type="boolean")
     * @Groups({"translator"})
     * @SerializedName("user_lock")
     */
    protected $isUserLocked = false;

    /**
     * @ORM\Column(name="admin_lock", type="boolean")
     * @Groups({"translator"})
     * @SerializedName("admin_lock")
     */
    protected $isAdminLocked = false;

    /**
     * @Groups({"translator"})
     * @SerializedName("idx")
     * @Accessor(getter="getIndex")
     */
    protected $idx;

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

    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    public function getCreator()
    {
        return $this->creator;
    }

    public function getAuthor()
    {
        return $this->creator ? $this->creator->getUsername(): 'claroline';
    }

    public function setIsAdminLocked($boolean)
    {
        $this->isAdminLocked = $boolean;
    }

    public function isAdminLocked()
    {
        return $this->isAdminLocked;
    }

    public function setIsUserLocked($boolean)
    {
        $this->isUserLocked = $boolean;
    }

    public function isUserLocked()
    {
        return $this->isUserLocked;
    }

    public function changeUserLock()
    {
        $this->isUserLocked = !$this->isUserLocked;
    }

    public function changeAdminLock()
    {
        $this->isAdminLocked = !$this->isAdminLocked;
    }

    public function getIndex()
    {
        return $this->getVendor() . 
            $this->getBundle() . 
            $this->getCommit() . 
            $this->getDomain() . 
            $this->getKey();
    }
} 