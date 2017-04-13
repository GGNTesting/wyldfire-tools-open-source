<?php

/**
 * @author Will Ferrer
 * @copyright (c) 2015, GGN
 * @licensee 2015 developed under license for GGN The ultimate tournament platform
 * @license licensed under the terms of
 * the Open Source LGPL 3.0 license.
 */

namespace WyldFireTools\Traits;
use Zend;

/**
 * Class Zf2EventManagerTrait
 * A trait that attaches a Zf2 Event Manager to the class
 * @package WyldFire
 */
trait Zf2EventManagerTrait
{
    /**
     * @var Zend\EventManager\EventManager
     */
    protected $eventManager;


    /**
     * @return Zend\EventManager\EventManager
     */
    public function getEventManager() {
        if ($this->eventManager === NULL) {
            $this->setEventManager(new Zend\EventManager\EventManager());
        }
        return $this->eventManager;
    }


    /**
     * @param Zend\EventManager\EventManager $eventManager
     * @return $this
     */
    public function setEventManager(Zend\EventManager\EventManager $eventManager) {
        $this->eventManager = $eventManager;
        return $this;
    }


}

?>