<?php

namespace WyldFireTools\Mvc\Controller;

use WyldFireTools\Manager\WyldCrudManagerInterface;
use WyldFireTools\Traits\WyldRestSettingsTrait;
use Zend\Mvc\Controller\AbstractRestfulController;

/**
 *
 * @author Will Ferrer
 * @copyright (c) 2015, GGN
 * @license e 2015 developed under license for GGN The ultimate tournament platform
 * @license licensed under the terms of
 *          the Open Source LGPL 3.0 license.
 */

/*
 * A trait that handles interfacing restul actions with other wyld libaries
 */
abstract class WyldRestControllerAbstract extends AbstractRestfulController {
    use WyldRestSettingsTrait;

    /**
     * @var WyldCrudManagerInterface
     */
    protected $wyldCrudManager;


    public function __construct(WyldCrudManagerInterface $wyldCrudManager)
    {
        $wyldCrudManager->setContext($this->getContext());
        $wyldCrudManager->setDoctrineEntityManager($this->getDoctrineEntityManager());
        $wyldCrudManager->setEntityName($this->getEntityName());
        $wyldCrudManager->setGetCount($this->getGetCount());
        $this->setWyldCrudManager($wyldCrudManager);
    }

    /*
     * Extended version to interface with wyld crud
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractRestfulController::getList()
     */
    public function getList() {
        $query = (is_string($this->getRequest()->getQuery()->query))?json_decode($this->getRequest()->getQuery()->query, true):array();

        return $this->getWyldCrudManager()->getList($query);
    }

    /*
     * Extended version to interface with wyld crud
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractRestfulController::get()
     */
    public function get($id) {
        return $this->getWyldCrudManager()->get($id);
    }

    /*
     * Extended version to interface with wyld crud
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractRestfulController::create()
     */
    public function create($data) {
        return $this->getWyldCrudManager()->create($data);
    }

    /*
     * Extended version to interface with wyld crud
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractRestfulController::update()
     */
    public function update($id, $data) {
        return $this->getWyldCrudManager()->update($id, $data);
    }

    /*
     * Extended version to interface with wyld crud
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractRestfulController::delete()
     */
    public function delete($id) {
        return $this->getWyldCrudManager()->delete($id);
    }

    /**
     * @return WyldCrudManagerInterface
     */
    public function getWyldCrudManager()
    {
        return $this->wyldCrudManager;
    }

    /**
     * @param WyldCrudManagerInterface $wyldCrudManager
     * @return WyldRestControllerAbstract
     */
    public function setWyldCrudManager($wyldCrudManager)
    {
        $this->wyldCrudManager = $wyldCrudManager;
        return $this;
    }
}

?>