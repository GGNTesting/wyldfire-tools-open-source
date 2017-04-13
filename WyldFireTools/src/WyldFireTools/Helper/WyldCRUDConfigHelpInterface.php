<?php
/**
 * @author Will Ferrer
 * @copyright (c) 2015, GGN
 * @licensee 2015 developed under license for GGN The ultimate tournament platform
 * @license licensed under the terms of
 * the Open Source LGPL 3.0 license.
 */
namespace WyldFireTools\Helper;
use Doctrine\ORM\EntityManagerInterface;

/*
 * Interface WyldCrudConfigHelpers
 */
interface WyldCRUDConfigHelpInterface
{

    public function buildAliasMap($config=NULL);

    public function breakDownFieldName($column);

    public function parseConfig(array $defaults, array $config, $entityName);

    public function getEntityManager();

    public function setEntityManager(EntityManagerInterface $em);

}


?>