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
 * A class that handles configs for WyldCRUD
 */
use WyldFireTools\Traits\EntityManagerTrait;
use WyldFireTools\Traits\WyldArrayConfigTrait;

class WyldCRUDConfigHelper implements WyldCRUDConfigHelpInterface
{
    use EntityManagerTrait, WyldArrayConfigTrait;

    /**
     * Readable columns built from the config
     * @var array|null
     */
    protected $readableColumns;

    /**
     * context of the last call to the class
     * @var string|null
     */
    protected $context;

    /**
     * @return null|string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param null|string $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return array|null
     */
    public function getReadableColumns()
    {
        return $this->readableColumns;
    }

    /**
     * Gets a map of aliases to entities
     * example
     * array(
     * 	'[alias]'=>'[entity name]'
     * )
     * @param array $config
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @return array
     */
    public function buildAliasMap($config=NULL) {
        $em = $this->getEntityManager();
        $result = array();
        $from = $config['read']['from'];
        $innerJoins = (array_key_exists('innerJoins', $config['read']))?$config['read']['innerJoins']:array();
        $leftJoins = (array_key_exists('leftJoins', $config['read']))?$config['read']['leftJoins']:array();
        $joins = array_merge($innerJoins, $leftJoins);
        $result[$from[1]] = $from[0];
        foreach ($joins as $join) {
            $matches = $this->breakDownFieldName($join[0]);
            $joinFromAlias = $matches[0];
            $joinTo = $matches[1];
            $joinFromEntity = $result[$joinFromAlias];
            $associationMap = $em->getClassMetadata($joinFromEntity)->getAssociationMapping($joinTo);
            $joinToAlias = $join[1];
            $targetEntity = $associationMap['targetEntity'];
            $result[$joinToAlias] = $targetEntity;
        }
        return $result;
    }

    /**
     * breaks down a field name into parts and returns it
     * @param string $column
     * @return array
     */
    public function breakDownFieldName($column) {
        $matches = array();
        if (preg_match('/\./', $column)) {
            preg_match('/^(.)+?\.?(.+)$/', $column, $matches);
        } else {
            preg_match('/^.+$/', $column, $matches);
        }
        return $matches;
    }

    /**
     * Gets all the columns that are readable based on the selects and joins used.
     * @param array $config
     * @param array $aliasMapping
     * @return array
     */
    protected function buildReadableColumns (array $config, array $aliasMapping) {
        $selects = $config['selects'];
        $columns = array();
        foreach($selects as $select) {
            $columnsForSelect = preg_split('/,/', $select);
            foreach ($columnsForSelect as $column) {
                $column = preg_replace('/[a-zA-Z]+\(|\)/', '', $column);
                $column = trim($column);
                $columns[] = $column;
            }
        }
        $allReadableColumns = $this->buildAllReadableColumns($aliasMapping);
        $extraColumns = array();
        foreach($columns as $key => $value) {
            //If there is no period in the field then you are reading all the field on a table
            if (!preg_match('/\./', $value)) {
                unset($columns[$key]);
                $fetchedFromAll = (array_key_exists($key, $allReadableColumns))?$allReadableColumns[$key]:array();
                foreach($fetchedFromAll as $newColumn) {
                    $extraColumns[] = $newColumn;
                }
            }
        }
        $columns = array_merge($columns, $extraColumns);
        return $columns;
    }

    /**
     * Gets all the columns that could be read for the from table and the joins
     * @param array $aliasMapping
     * @return array
     * Example:
     * array(
     * 	'alias'=>array(
     * 		[columns]
     * 	)
     * )
     */
    protected function buildAllReadableColumns (array $aliasMapping) {
        $readableColumns = array();
        $firstKey = key($aliasMapping);
        $em = $this->getEntityManager();
        foreach ($aliasMapping as $key => $value) {
            $columns = $em->getClassMetadata($value)->getFieldNames();
            foreach ($columns as $column) {
                if ($key !== $firstKey) {
                    $readableColumns[$key] = $key . '.' . $column;
                } else {
                    $readableColumns[$key] = $column;
                }
            }
        }
        return $readableColumns;
    }


    /**
     * Parses the config file to get it ready to convert into doctrine query builder
     * @param array $defaults
     * @param array $config
     * @param string $entityName
     * @@throws \Doctrine\ORM\Mapping\MappingException
     * @throws \RuntimeException
     * @return array
     */
    public function parseConfig(array $defaults, array $config, $entityName) {
        // TODO: cache config when not in development mode
        $config = $this->getConfigForContext($defaults, $config);
        $config = $this->ensureFrom($config, $entityName);
        //$config = $this->convertSelfToAlias($config); Redundant
        $aliasMapping = $this->buildAliasMap($config);
        $readable = ($this->getReadableColumns() === NULL)?$this->buildReadableColumns($config['read'], $aliasMapping):$this->getReadableColumns();
        $this->setReadableColumns($readable);
        $config = $this->addReadable($config);
        return $config;
    }

    /**
     * @param array|null $readableColumns
     */
    public function setReadableColumns($readableColumns)
    {
        $this->readableColumns = $readableColumns;
    }


    /**
     * Merge and inherit the config in the array
     * @param array $defaults
     * @param array $config
     * @throws \RuntimeException
     * @return array
     */
    protected function getConfigForContext(array $defaults, array $config){
        $context = $this->getContext();
        $config[$context] = array_replace($defaults, $config[$context]);
        return $this->parseArrayConfig($config, $context);
    }

    /**
     * Converts the readable references to the columns that are readable
     * @param array $config
     * @return array
     */
    protected function addReadable(array $config) {
        $config = $this->fillReadableForPath($config, 'create', 'allow');
        $config = $this->fillReadableForPath($config, 'create', 'deny');
        foreach ($config['read']['orderByAllow'] as $key => $value) {
            if (in_array('readable', $value, true)) {
                $config['read'] = $this->fillReadableForPath($config['read'], 'orderByAllow', $key);
                $config['read'] = $this->fillReadableForPath($config['read'], 'orderByDeny', $key);
                $config['read'] = $this->fillReadableForPath($config['read'], 'whereAllow', $key);
                $config['read'] = $this->fillReadableForPath($config['read'], 'whereDeny', $key);
                $config['read'] = $this->fillReadableForPath($config['read'], 'havingAllow', $key);
                $config['read'] = $this->fillReadableForPath($config['read'], 'havingDeny', $key);
            }
        }

        $config = $this->fillReadableForPath($config, 'update', 'allow');
        $config = $this->fillReadableForPath($config, 'update', 'deny');
        return $config;

    }

    /**
     * Fills in readable tags found in path
     * @param array $config
     * @param string $path1
     * @param string $path2
     * @return array
     */
    protected function fillReadableForPath (array $config, $path1, $path2) {
        if (array_key_exists($path2, $config[$path1]) && in_array('readable', $config[$path1][$path2], true)) {
            $config[$path1][$path2] = $this->convertReadable($config[$path1][$path2]);
        }
        return $config;
    }

    /**
     * replaces the keyword readable with an array of readable columns
     * @param array $config
     * @return array
     */
    protected function convertReadable(array $config) {
        $readable = $this->getReadableColumns();
        $addReadable = false;
        foreach ($config as $key => $value) {
            if ($value === 'readable') {
                unset($config[$key]);
                $addReadable = true;
                break;
            }
        }
        if ($addReadable === true) {
            $config = array_merge($config, $readable);
        }
        return $config;
    }

    /**
     * Converts the remaining selfs to what is stored in the from
     * @param array $config
     * @return array
     */
    protected function convertSelfToAlias(array $config) {
        $aliasInFrom = $config['read']['from'][1];
        return array_walk_recursive ( $config, function (&$value) use ($aliasInFrom) {
            if ($value === 'self') {
                $value = $aliasInFrom;
            }
        });
    }

    /**
     * Makes sure the from is populated in read
     * @param array $config
     * @param string $entityName
     * @return array
     */
    protected function ensureFrom(array $config, $entityName) {
        if ($config['read']['from'][0] === 'self') {
            $config['read']['from'] = array($entityName, $this->makeSelfAlias($entityName));
        }
        return $config;
    }

    /**
     * Makes an alias for the enity
     * @param string $entityName
     * @return string
     */
    protected function makeSelfAlias($entityName) {
        return $this->makeAliasForEnityName($entityName);
    }

    /**
     * @param string $entityName
     * @return string
     */
    protected function makeAliasForEnityName($entityName) {
        $alias = preg_replace('/^[A-Za-z]+\\\/', '', $entityName);
        $alias = ucfirst($alias);
        $alias = preg_replace('/[^A-Z]/', '', $alias);
        $alias = strtolower($alias);
        return $alias;
    }




}