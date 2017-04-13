<?php

/**
 * @author Will Ferrer
 * @copyright (c) 2015, GGN
 * @licensee 2015 developed under license for GGN The ultimate tournament platform
 * @license licensed under the terms of
 * the Open Source LGPL 3.0 license.
 */

namespace WyldFireTools\Helper;

use Doctrine\ORM\QueryBuilder;
use WyldFireTools\Doctrine\ORM\EntityRepository\WyldCRUD;
use WyldFireTools\Traits\EntityManagerTrait;
use Zend\Math\Rand;

/*
 * A class that handles query builders for WyldCRUD
 */
class WyldCRUDQueryBuilderHelper implements WyldCRUDQueryBuilderHelperInterface
{
    use EntityManagerTrait;

    /**
     * @var QueryBuilder|null
     */
    protected $queryBuilderQuery;


    /**
     * Builds the qb query from the config and the params
     * @param array $config
     * @param array $params
     * @throws \Exception
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function buildReadQb (array $config, array $params) {
        $qb = ($this->getQueryBuilderQuery() !== NULL)?$this->getQueryBuilderQuery():$this->getEntityManager()->createQueryBuilder();
        $this->setQueryBuilderQuery($qb);

        $this->buildReadQbFromParams($config, $params);

        $this->buildReadQbFromConfig($config);
        return $qb;
    }

    /**
     * Validates that order by meet with the rules in the config and throws exceptions if not
     * @param array $allow
     * @param array $deny
     * @param array $column
     * @throws \UnexpectedValueException
     */
    public function validateFromParams(array $allow, array $deny, array $column) {
        if ((!in_array($column, $allow, true) || in_array($column, $deny, true))) {
            throw new \UnexpectedValueException(sprintf(WyldCRUD::getErrorMessages()['columnNotAllowed'], array($column)));
        }
    }

    /**
     * Builds selects from the array passed
     * @param array $selects
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function buildSelects(array $selects) {
        $qb = $this->getQueryBuilderQuery();
        foreach ($selects as $select) {
            $qb->select($select);
        }
        return $qb;
    }

    /**
     * Builds from from the passed in array
     * @param array $from
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function buildFrom(array $from) {
        $qb = $this->getQueryBuilderQuery();
        $qb->from($from[0], $from[1]);
        return $qb;
    }

    /**
     * Build the inner joins from the array passed
     * @param array $joins
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function buildInnerJoins(array $joins) {
        $qb = $this->getQueryBuilderQuery();
        foreach ($joins as $join) {
            $qb->innerJoin($join[0], $join[1]);
        }
        return $qb;
    }

    /**
     * Build the left joins from the array passed
     * @param array $joins
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function buildLeftJoins(array $joins) {
        $qb = $this->getQueryBuilderQuery();
        foreach ($joins as $join) {
            $qb->leftJoin($join[0], $join[1]);
        }
        return $qb;
    }

    /**
     * Build wheres from passed array
     * @param array $wheres
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function buildWheres(array $wheres) {
        $qb = $this->getQueryBuilderQuery();
        foreach ($wheres as $where) {
            if ($where['type'] === 'and') {
                $qb->andWhere($where['value']);
            } else {
                $qb->orWhere($where['value']);
            }

        }
        return $qb;
    }

    /**
     * Build having from passed array
     * @param array $havings
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function buildHaving(array $havings) {
        $qb = $this->getQueryBuilderQuery();
        foreach ($havings as $having) {
            if ($having['type'] === 'and') {
                $qb->andHaving($having['value']);
            } else {
                $qb->orHaving($having['value']);
            }

        }
        return $qb;
    }


    /**
     * Build order bys from passed config
     * @param array $orderBys
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function buildOrderBys(array $orderBys) {
        $qb = $this->getQueryBuilderQuery();
        foreach ($orderBys as $orderBy) {
            $qb->addOrderBy($orderBy);
        }
        return $qb;
    }

    /**
     * Takes the config and applies it to the query builder query
     * @param array $config
     */
    protected function buildReadQbFromConfig(array $config) {
        if (array_key_exists('selects', $config['read'])) {
            $this->buildSelects($config['read']['selects']);
        }

        if (array_key_exists('from', $config['read'])) {
            $this->buildFrom($config['read']['from']);
        }

        if (array_key_exists('innerJoins', $config['read'])) {
            $this->buildInnerJoins($config['read']['innerJoins']);
        }

        if (array_key_exists('leftJoins', $config['read'])) {
            $this->buildLeftJoins($config['read']['leftJoins']);
        }

        if (array_key_exists('wheres', $config['read'])) {
            $this->buildWheres($config['read']['wheres']);
        }

        if (array_key_exists('havings', $config['read'])) {
            $this->buildHaving($config['read']['havings']);
        }

        if (array_key_exists('orderBys', $config['read'])) {
            $this->buildOrderBys($config['read']['orderBys']);
        }
    }

    /**
     * Validates that wheres or havings meet with the rules in the config and throws exceptions if not
     * @param array $allow
     * @param array $deny
     * @param array $config
     * @throws \RuntimeException
     */
    protected function validateConditionFromParams(array $allow, array $deny, array $config) {
        $allowList = array();
        $denyList = array();
        if (array_key_exists($config['operator'], $allow)) {
            $allowList = $allow[$config['operator']];
        } else if (array_key_exists('all', $allow)) {
            $allowList = $allow['all'];
        }

        if (array_key_exists($config['operator'], $deny)) {
            $denyList = $deny[$config['operator']];
        } else if (array_key_exists('all', $deny)) {
            $denyList = $deny['all'];
        }

        if (!array_key_exists('operator', $config) || !in_array($config['operator'], WyldCRUD::getOperators(), true)) {
            $operator = (array_key_exists('operator', $config))?$config['operator']:'NULL';
            throw new \RuntimeException(sprintf(WyldCRUD::getErrorMessages()['conditionNotAllowed'], array($operator, $config['value'])));
        }

        if ((!in_array($config['value'], $allowList, true) || in_array($config['value'], $denyList, true))) {
            throw new \RuntimeException(sprintf(WyldCRUD::getErrorMessages()['conditionNotAllowed'], array($config['operator'], $config['value'])));
        }

        if (array_key_exists('value2', $config) && (!in_array($config['value2'], $allowList, true) || in_array($config['value2'], $denyList, true))) {
            throw new \RuntimeException(sprintf(WyldCRUD::getErrorMessages()['conditionNotAllowed'], array($config['operator'], $config['value2'])));
        }
    }

    /**
     * Builds wheres and havings from params
     * @param array $permissions
     * @param array $wheres
     * @param boolean $having
     * @throws \RuntimeException
     * @throws \Zend\Math\Exception\DomainException
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function buildWheresFromParams(array $permissions, array $wheres, $having=false) {
        $whereAllow = $permissions['allow'];
        $whereDeny = $permissions['deny'];
        $qb = $this->getQueryBuilderQuery();
        foreach ($wheres as $whereFromParams) {
            $this->validateConditionFromParams($whereAllow, $whereDeny, $whereFromParams);
            $key = $this->generateKey();
            $key2 = $this->generateKey();

            if ($having) {
                $addWhereMethod = (array_key_exists('type', $whereFromParams) && $whereFromParams['type'] === 'or')?'orHaving':'andHaving';
            } else {
                $addWhereMethod = (array_key_exists('type', $whereFromParams) && $whereFromParams['type'] === 'or')?'orWhere':'andWhere';
            }

            if ($whereFromParams['operator'] !== 'between') {
                if ($whereFromParams['operator'] === 'is null' || $whereFromParams['operator'] === 'is not null') {
                    $whereString = $whereFromParams['field'] . ' ' . $whereFromParams['operator'];
                } else if ($whereFromParams['operator'] === 'in' || $whereFromParams['operator'] === 'not in') {
                    $whereString = $whereFromParams['field'] . ' ' . $whereFromParams['operator'] . ' :(' . $key . ')';
                } else {
                    $whereString = $whereFromParams['field'] . ' ' . $whereFromParams['operator'] . ' :' . $key;
                }
            } else {
                $whereString = $whereFromParams['field'] . ' ' . $whereFromParams['operator'] . ' :' . $key . ' AND ' . ' :' . $key2;
                $qb->setParameter($key2, $whereFromParams['value2']);
            }
            $qb->$addWhereMethod($whereString);
            $qb->setParameter($key, $whereFromParams['value']);
        }
        return $qb;
    }

    /**
     * generates a random key
     * @throws \Zend\Math\Exception\DomainException
     * @return string
     */
    protected function generateKey() {
        return Rand::getString(32);
    }

    /**
     * Builds the order bys from params
     * @param array $permissions
     * @param array $orderBys
     * @throws \UnexpectedValueException
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function buildOrderBysFromParams(array $permissions, array $orderBys) {
        $allow = $permissions['allow'];
        $deny = $permissions['deny'];
        $qb = $this->getQueryBuilderQuery();
        foreach ($orderBys as $orderBy) {
            $this->validateFromParams($allow, $deny, $orderBy['field']);
            $direction = ($orderBy['direction']==='asc')?'asc':'desc';
            $field = $orderBy['field'];
            $qb->addOrderBy($field, $direction);
        }
        return $qb;
    }

    /**
     * Builds the limit for the query builder query and throws and exception if it's over the max in the config
     * @param integer $limit
     * @param integer $max
     * @throws \UnexpectedValueException
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function buildLimitFromParams($limit, $max) {
        $qb = $this->getQueryBuilderQuery();
        if ($limit > $max) {
            throw new \UnexpectedValueException(sprintf(WyldCRUD::getErrorMessages()['limitOverMax'], array($limit, $max)));
        }
        $qb->setMaxResults($limit);
        return $qb;
    }

    /**
     * @param integer $offset
     * @throws \Exception
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function buildOffsetFromParams($offset) {
        $qb = $this->getQueryBuilderQuery();
        $qb->setFirstResult($offset);
        return $qb;

    }

    /**
     * Builds the qb query parts from the params
     * @param array $config
     * @param array $params
     * @throws \RuntimeException
     * @throws \Zend\Math\Exception\DomainException
     * @throws \UnexpectedValueException
     * @throws \Exception
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function buildReadQbFromParams(array $config, array $params) {
        if (array_key_exists('wheres', $params)) {


            $this->buildWheresFromParams(array('allow'=> $config['read']['whereAllow'], 'deny'=>$config['read']['whereDeny']), $params['wheres'], false);
        }

        if (array_key_exists('havings', $params)) {
            $this->buildWheresFromParams(array('allow'=> $config['read']['havingAllow'], 'deny'=>$config['read']['havingDeny']), $params['havings'], true);
        }

        if (array_key_exists('orderBys', $params)) {
            $this->buildOrderBysFromParams(array('allow'=> $config['read']['orderByAllow'], 'deny'=>$config['read']['orderByDeny']), $params['orderBy']);
        }

        if (array_key_exists('limit', $params)) {
            $this->buildLimitFromParams($params['limit'], $config['read']['max']);
        }

        if (array_key_exists('offset', $params)) {
            $this->buildOffsetFromParams($params['offset']);
        }
    }

    /**
     *
     * @return null|QueryBuilder
     */
    public function getQueryBuilderQuery()
    {
        return $this->queryBuilderQuery;
    }

    /**
     * @param null|QueryBuilder $queryBuilderQuery
     */
    public function setQueryBuilderQuery($queryBuilderQuery)
    {
        $this->queryBuilderQuery = $queryBuilderQuery;
    }



}


?>