<?php

namespace Oylex\SpamJudgeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TextSample
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Oylex\SpamJudgeBundle\Entity\TextSampleRepository")
 */
class TextSample
{
    const TYPE_SPAM = 1;
    const TYPE_HAM = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="sample", type="text")
     */
    private $sample;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="userAgent", type="string", length=255)
     */
    private $userAgent;

    /**
     * @var string
     *
     * @ORM\Column(name="referrer", type="string", length=255)
     */
    private $referrer;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     */
    private $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="tokenProcessed", type="boolean")
     */
    private $tokenProcessed;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set sample
     *
     * @param string $sample
     * @return TextSample
     */
    public function setSample($sample)
    {
        $this->sample = $sample;

        return $this;
    }

    /**
     * Get sample
     *
     * @return string 
     */
    public function getSample()
    {
        return $this->sample;
    }

    /**
     * Set ip
     *
     * @param string $ip
     * @return TextSample
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string 
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set userAgent
     *
     * @param string $userAgent
     * @return TextSample
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Get userAgent
     *
     * @return string 
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Set referrer
     *
     * @param string $referrer
     * @return TextSample
     */
    public function setReferrer($referrer)
    {
        $this->referrer = $referrer;

        return $this;
    }

    /**
     * Get referrer
     *
     * @return string 
     */
    public function getReferrer()
    {
        return $this->referrer;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param boolean $tokenProcessed
     */
    public function setTokenProcessed($tokenProcessed)
    {
        $this->tokenProcessed = $tokenProcessed;
    }

    /**
     * @return boolean
     */
    public function getTokenProcessed()
    {
        return $this->tokenProcessed;
    }


}
