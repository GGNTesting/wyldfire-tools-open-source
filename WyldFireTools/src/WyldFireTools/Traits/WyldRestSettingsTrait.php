<?php


/**
 * @author Will Ferrer
 * @copyright (c) 2015, GGN
 * @licensee 2015 developed under license for GGN The ultimate tournament platform
 * @license licensed under the terms of
 * the Open Source LGPL 3.0 license.
 */

namespace WyldFireTools\Traits;
/**
 * Class Zf2EventManagerTrait
 * A trait that attaches entity managers to the class
 * @package WyldFire
 */
trait WyldRestSettingsTrait
{

    /**
     * The context to pass to wyld crud
     *
     * @var string|null
     */
    protected $context;

    /**
     * The entity used for wyld crud
     *
     * @var string|null
     */
    protected $entityName;

    /**
     * Whether or not to report the count in getList
     *
     * @var boolean
     */
    protected $getCount = true;

    /**
     * The doctrine entity manager name to get from the service locator
     *
     * @var string
     */
    protected $doctrineEntityManager = 'doctrine.entitymanager.orm_default';

    /**
     * @return string|NULL
     */
    public function getContext() {
        return $this->context;
    }

    /**
     * @return string|NULL
     */
    public function getEntityName() {
        return $this->entityName;
    }

    /**
     * @return boolean
     */
    public function getGetCount() {
        return $this->getCount;
    }

    /**
     * @return string
     */
    public function getDoctrineEntityManager() {
        return $this->doctrineEntityManager;
    }

    /**
     * @param null|string $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @param null|string $entityName
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * @param boolean $getCount
     */
    public function setGetCount($getCount)
    {
        $this->getCount = $getCount;
    }

    /**
     * @param string $doctrineEntityManager
     */
    public function setDoctrineEntityManager($doctrineEntityManager)
    {
        $this->doctrineEntityManager = $doctrineEntityManager;
    }
}

?>