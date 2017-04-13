<?php
/**
 * @author Will Ferrer
 * @copyright (c) 2015, GGN
 * @licensee 2015 developed under license for GGN The ultimate tournament platform
 * @license licensed under the terms of
 * the Open Source LGPL 3.0 license.
 */
namespace WyldFireTools\Helper;

/*
 * Interface WyldCRUDQueryBuilderHelperInterface
 */
use Doctrine\ORM\EntityManagerInterface;

interface WyldCRUDQueryBuilderHelperInterface
{
    public function buildReadQb (array $config, array $params);

    public function validateFromParams(array $allow, array $deny, array $column);

    public function getEntityManager();

    public function setEntityManager(EntityManagerInterface $em);

    public function getQueryBuilderQuery();

    public function setQueryBuilderQuery($queryBuilderQuery);

}


?>