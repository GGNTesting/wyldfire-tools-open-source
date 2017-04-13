<?php

/**
 * @author Will Ferrer
 * @copyright (c) 2015, GGN
 * @licensee 2015 developed under license for GGN The ultimate tournament platform
 * @license licensed under the terms of
 * the Open Source LGPL 3.0 license.
 */

namespace WyldFireTools\Traits;
use Doctrine\ORM\EntityManagerInterface;
/**
 * Class Zf2EventManagerTrait
 * A trait that attaches entity managers to the class
 * @package WyldFire
 */
trait EntityManagerTrait
{

    /**
     * @var EntityManagerInterface $em
     */
    protected $entityManager;

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @return EntityManagerTrait
     */
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        return $this;
    }




}

?>