<?php
/**
 * @author Will Ferrer
 * @copyright (c) 2015, GGN
 * @licensee 2015 developed under license for GGN The ultimate tournament platform
 * @license licensed under the terms of
 * the Open Source LGPL 3.0 license.
 */
namespace WyldFireTools\Manager;
use Common\DTO\Response;
/*
 * Interface WyldCrudConfigHelpers
 */
interface WyldCrudManagerInterface
{

    /**
     * @return string|NULL
     */
    public function getContext();

    /**
     * @return string|NULL
     */
    public function getEntityName();

    /**
     * @return boolean
     */
    public function getGetCount();
    /**
     * @return string
     */
    public function getDoctrineEntityManager();

    /**
     * @param null|string $context
     */
    public function setContext($context);
    /**
     * @param null|string $entityName
     */
    public function setEntityName($entityName);

    /**
     * @param boolean $getCount
     */
    public function setGetCount($getCount);

    /**
     * @param string $doctrineEntityManager
     */
    public function setDoctrineEntityManager($doctrineEntityManager);

    /**
     * @param array $query
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     * @return Response
     */
    public function getList(array $query);


    /**
     * @param $id
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     * @return Response
     */
    public function get($id);

    /**
     * @param $data
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     * @return Response
     */
    public function create($data);


    /**
     * @param $id
     * @param $data
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     * @return Response
     */
    public function update($id, $data);


    /**
     * @param $id
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     * @return Response
     */
    public function delete($id);
}


?>