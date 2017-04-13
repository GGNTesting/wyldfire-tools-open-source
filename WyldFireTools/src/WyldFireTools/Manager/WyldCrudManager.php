<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace WyldFireTools\Manager;

use Common\DTO\Response;
use Common\Manager\CommonManager;
use WyldFireTools\Traits\ServiceLocatorTrait;
use WyldFireTools\Traits\WyldRestSettingsTrait;


class WyldCrudManager extends CommonManager implements WyldCrudManagerInterface
{
    use WyldRestSettingsTrait, ServiceLocatorTrait;


    /**
     * @param array $query
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     * @return Response
     */
    public function getList(array $query) {
        $em = $this->getServiceLocator()->get($this->getDoctrineEntityManager());
        $qb = $em->getRepository($this->getEntityName())->read($this->getContext(), $query);

        $response = array(
            'result'=>$qb->getQuery()->getArrayResult(),
        );
        if ($this->getGetCount()) {
            $response['count'] = $qb->getQuery()->count();
        }
        return new Response (
            $response
        );
    }


    /**
     * @param $id
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     * @return Response
     */
    public function get($id) {
        $em = $this->getServiceLocator()->get($this->getDoctrineEntityManager());
        $qb = $em->getRepository($this->getEntityName())->read($this->getContext(), array('wheres'=>array('id'=>$id)));

        $response = array(
            'result'=>$qb->getQuery()->getArrayResult(),
        );

        return new Response (
            $response
        );
    }

    /**
     * @param $data
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     * @return Response
     */
    public function create($data) {
        $em = $this->getServiceLocator()->get($this->getDoctrineEntityManager());
        $entity = $em->getRepository($this->getEntityName())->create($this->getContext(), $data);
        $em->flush();
        $response = array(
            'id'=>$entity->getId()
        );

        return new Response (
            $response
        );
    }


    /**
     * @param $id
     * @param $data
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     * @return Response
     */
    public function update($id, $data) {
        $em = $this->getServiceLocator()->get($this->getDoctrineEntityManager());
        $data['id'] = $id;
        $entity = $em->getRepository($this->getEntityName())->update($this->getContext(), $data);
        $em->flush();
        $response = array(
            'id'=>$entity->getId()
        );

        return new Response (
            $response
        );
    }


    /**
     * @param $id integer
     * @param $soft boolean
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     * @return Response
     */
    public function delete($id, $soft=true) {
        $em = $this->getServiceLocator()->get($this->getDoctrineEntityManager());
        $data = array();
        $data['id'] = $id;
        $entity = $em->getRepository($this->getEntityName())->delete($this->getContext(), $data);
        $em->flush();

        $response = array(
            'id'=>$entity->getId()
        );

        return new Response (
            $response
        );
    }
}
