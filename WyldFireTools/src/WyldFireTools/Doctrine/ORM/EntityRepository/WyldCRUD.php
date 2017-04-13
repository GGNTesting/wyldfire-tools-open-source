<?php

namespace WyldFireTools\Doctrine\ORM\EntityRepository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use WyldFireTools\Helper\WyldCRUDConfigHelper;
use WyldFireTools\Helper\WyldCRUDConfigHelpInterface;
use WyldFireTools\Helper\WyldCRUDQueryBuilderHelper;
use WyldFireTools\Helper\WyldCRUDQueryBuilderHelperInterface;
use WyldFireTools\Traits\BootstrapTrait;
use WyldFireTools\Traits\Zf2EventManagerTrait;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Stdlib\JsonSerializable;

/**
 * @author Will Ferrer
 * @copyright (c) 2015, GGN
 * @licensee 2015 developed under license for GGN The ultimate tournament platform
 * @license licensed under the terms of
 * the Open Source LGPL 3.0 license.
 */

/*
 * A trait that handle common crud functionality via a simple config
 * TODO: Methods to integrate with WyldForm.
 * TODO: Add cache support for configs
 * TODO: Add cache support for queries
 * @abstract
 */
abstract class WyldCRUD extends EntityRepository implements ListenerAggregateInterface {
    use BootstrapTrait, Zf2EventManagerTrait;



    /**
     * last params sent to the class
     * @var array|null
     */
    protected $params;

    /**
     * The last entity that was created, updated or deleted
     * @var JsonSerializable|null
     */
    protected $lastEntity;

    /**
     * Error messages to display
     * @var array
     */
    static protected $errorMessages = array(
        'conditionNotAllowed'=>'Error: Your % condition of %s %s is not allowed',
        'limitOverMax'=>'Error: limit is over max %s > %s',
        'columnNotAllowed'=>'Error: column %s is not allowed',
        'canNotUpdate'=>'Error: Can not find entity to update'
    );


    /**
     * Defaults that are used and merged over by any context config
     * @var array
     */
    static protected $CRUDDefaultConfig = array(
        'create'=>array(
            'allow'=>array('readable'),
            'deny'=>array()
        ),
        'read'=>array(
            'selects'=>array('self'),
            'from'=>array('self'),
            'orderByAllow'=>array('readable'),
            'whereAllow'=>array(
                'all'=>array(
                    'readable'
                )
            ),
            'havingAllow'=>array(
                'all'=>array(
                    'readable'
                )
            ),
            'orderByDeny'=>array(),
            'whereDeny'=>array(),
            'havingDeny'=>array(),
            'max'=>500
        ),
        'update'=>array(
            'allow'=>array('readable'),
            'deny'=>array()
        ),
        'delete'=>array(
            'soft'=>true
        )
    );


    /**
     * The config that manages the WyldCrud features. Anything not set will be copied from the $CRUDDefaultConfig, this means all options are optional
     * A single option can be used instead of an array by assigning a string instead of an array to that key
     * Example
     * array(
     * 	'[context name]'=>array( // CRUD methods take a context as an argument that pulls the config from the corresponding context in this array
     * 		'inherits'=>array(
     * 			'[parent context names]' // An array of other contexts to copy configs from
     * 		)
     * 		'create'=>array(
     * 			'methods'=>array('[method names available on the repo]'), // You can add any methods you like available on the repo to run in the context, you may also put in arrays that contain a class / a class name and a method to call without having it on the repo
     * 			'allow'=>array( // An array of columns that are allowed for a create method in this context
     * 				'readable', // A magic word that means any columns which are available to the a read method call are also included here
     * 				'[columns go here]'
     * 			),
     * 			'deny'=>array( // An array of columns that are denied for a create method in this context
     * 				'readable', // A magic word that means any columns which are available to the a read method call are also included here
     * 				'[columns go here]'
     * 			)
     * 		),
     * 		'read'=>array(
     * 			'selects'=>array('[doctrine selects, or self]'), // An array of selects to put in the query builder, self will pull the entity from the from in here
     * 			'from'=>array(['Doctrine from | self']), // defaults to self, and that will cause the from to come from the entity class associated with this repo
     * 			'innerJoins'=>array(['doctrine inner joins']),
     * 			'leftJoins'=>array(['doctrine left joins']),
     * 			'orderBys'=>array(['doctrine orderBys']),
     * 			'wheres'=>array(array('type'=>'[and | or]', 'value'=>['doctrine wheres'])),
     * 			'having'=>array(array('type'=>'[and | or]', 'value'=>['doctrine having'])),
     * 			'methods'=>array('[method names available on the repo]'), // You can add any methods you like available on the repo to run in the context
     * 			'orderByAllow'=>array( // An array of columns that are allowed in requests from the front end for orderbys
     * 				'readable', // A magic word that means any columns which are available to the a read method call are also included here
     * 				'[columns go here]'
     * 			),
     * 			'orderByDeny'=>array( // An array of columns that are denied in requests from the front end for orderbys
     * 				'readable', // A magic word that means any columns which are available to the a read method call are also included here
     * 				'[columns go here]'
     * 			),
     * 			'whereAllow'=>array( // An array of columns that are allowed in requests from the front end for where
     * 				'[operator name or all]'=>array( // an operator name to define the rules for or all to set the rule for all operators
     * 					'readable', // A magic word that means any columns which are available to the a read method call are also included here
     * 					'[columns go here]'
     * 				)
     * 			),
     * 			'whereDeny'=>array( // An array of columns that are denied in requests from the front end for where
     * 				'[operator name or all]'=>array( // an operator name to define the rules for or all to set the rule for all operators
     * 					'readable', // A magic word that means any columns which are available to the a read method call are also included here
     * 					'[columns go here]'
     * 				)
     * 			),
     * 			'havingAllow'=>array( // An array of columns that are allowed in requests from the front end for where
     * 				'[operator name or all]'=>array( // an operator name to define the rules for or all to set the rule for all operators
     * 					'readable', // A magic word that means any columns which are available to the a read method call are also included here
     * 					'[columns go here]'
     * 				)
     * 			),
     * 			'havingDeny'=>array( // An array of columns that are denied in requests from the front end for where
     * 				'[operator name or all]'=>array( // an operator name to define the rules for or all to set the rule for all operators
     * 					'readable', // A magic word that means any columns which are available to the a read method call are also included here
     * 					'[columns go here]'
     * 				)
     * 			),
     * 			'max'=>[a number for the max numbers of rows that can be read]
     * 		),
     * 		'update'=>array(
     * 			'methods'=>array('[method names available on the repo]'), // You can add any methods you like available on the repo to run in the context
     * 			'allow'=>array( // An array of columns that are allowed for a update method in this context
     * 				'readable', // A magic word that means any columns which are available to the a read method call are also included here
     * 				'[columns go here]'
     * 			),
     * 			'deny'=>array( // An array of columns that are denied for a update method in this context
     * 				'readable', // A magic word that means any columns which are available to the a read method call are also included here
     * 				'[columns go here]'
     * 			)
     * 		),
     * 		'delete'=>array(
     * 			'methods'=>array('[method names available on the repo]'), // You can add any methods you like available on the repo to run in the context
     * 		)
     * 	)
     * )
     * @var array
     */
    protected $CRUDConfig = array(
        'default'=>array()
    );


    /**
     * The config after it has been parsed and ready to turn into query builder
     * @var array|null
     */
    protected $parsedConfig;

    /**
     * The columns that are readable with the current config
     * @var array|null
     */
    protected $readableColumns;

    /**
     * Association map taken from array
     * @var array|null
     * example
     * array(
     * 	'[alias name]'=>'[entity name]'
     * )
     */
    protected $aliasMap;


    /**
     * list of allow opperators
     * @var array
     */
    static protected $operators = array('=','!=','<','<=','>','>=','is null','is not null','in','not in','between','like');

    /**
     * Plugable config helper to parse the config
     *
     *@var \WyldFireTools\Helper\WyldCRUDConfigHelpInterface|null
     */
    protected $configHelper;

    /**
     * Plugable query builder helper to build our the query builder query based on the passed values
     *
     *@var \WyldFireTools\Helper\WyldCRUDQueryBuilderHelperInterface|null
     */
    protected $queryBuilderHelper;


    /**
     * triggers the create event
     * @param $context
     * @param array $params
     * @return JsonSerializable
     * @throws \Zend\EventManager\Exception\InvalidCallbackException
     * @throws \UnexpectedValueException
     */
    public function create($context, array $params) {
        $this->bootstrap();
        $this->getEventManager()->trigger('init', $this, array('context'=>$context, 'params'=>$params));
        $this->getEventManager()->trigger('create', $this, $params);
        return $this->getLastEntity();
    }

    /**
     * triggers the update event
     * @param string $context
     * @param array $params Contains the params for the entity being created
     * @param JsonSerializable|null $entity
     * @return JsonSerializable
     * @throws \Zend\EventManager\Exception\InvalidCallbackException
     * @throws \UnexpectedValueException
     */
    public function update($context, array $params, JsonSerializable $entity = NULL) {
        $this->bootstrap();
        $this->getEventManager()->trigger('init', $this, array('context'=>$context, 'params'=>$params));
        if ($entity !== NULL) {
            $this->setLastEntity($entity);
        }
        $this->getEventManager()->trigger('update', $this, $params);
        return $this->getLastEntity();
    }

    /**
     * triggers the delete event
     * @param string $context
     * @param array $params Contains the params for the entity being created
     * @param JsonSerializable|null $entity
     * @return JsonSerializable
     * @throws \Zend\EventManager\Exception\InvalidCallbackException
     * @throws \UnexpectedValueException
     */
    public function delete($context, array $params, $entity = NULL) {
        $this->bootstrap();
        $this->getEventManager()->trigger('init', $this, array('context'=>$context, 'params'=>$params));
        if ($entity !== NULL) {
            $this->setLastEntity($entity);
        }
        $this->getEventManager()->trigger('delete', $this, $params);
        return $this->getLastEntity();
    }

    /**
     * Does the work of making a create
     * @param EventInterface $e
     */
    public function onCreate(EventInterface $e) {
        $target = $e->getTarget();
        $params = $e->getParams();
        $config = $target->getConfig();
        $target->createEntity($config, $params);
        // NOTE: remember you can do set params on the event to change them for the next listener
    }

    /**
     * Does the work of making a create
     * @param EventInterface $e
     */
    public function onUpdate(EventInterface $e) {
        $target = $e->getTarget();
        $params = $e->getParams();
        $config = $target->getConfig();
        $target->updateEntity($config, $params);
        // NOTE: remember you can do set params on the event to change them for the next listener
    }

    /**
     * Does the work of making a create
     * @param EventInterface $e
     */
    public function onDelete(EventInterface $e) {
        $target = $e->getTarget();
        $params = $e->getParams();
        $config = $target->getConfig();
        $target->deleteEntity($config, $params);
        // NOTE: remember you can do set params on the event to change them for the next listener
    }

    /**
     * Gets a target entity either from that was passed to crud or via an id in the params
     * @param array $params
     * @throws \RuntimeException
     * @return JsonSerializable
     */
    protected function getTargetEntity (array $params) {
        if ($this->getLastEntity() !== NULL) {
            $entity = $this->getLastEntity();
        } else if (array_key_exists('id', $params)) {
            $entity = $this->findOneBy(array('id'=>$params['id']));
        } else {
            throw new \RuntimeException(static::getErrorMessages()['canNotUpdate']);
        }
        return $entity;
    }

    /**
     * cretaes an entity using the config and the params passed
     * @param array $config
     * @param array $params
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @return JsonSerializable
     */
    protected function createEntity(array $config, array $params) {
        $entityName = $this->getEntityName();
        $entity = new $entityName();
        $this->processEntity($entity, $config['create'], $params);
        if (array_key_exists('methods', $config['create'][])) {
            $this->runMethods($config['create']['methods'], $params);
        }
        return $entity;
    }


    /**
     * Updates an entity using the config and the params passed
     * @param array $config
     * @param array $params
     * @throws \RuntimeException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @return JsonSerializable
     */
    protected function updateEntity(array $config, array $params) {
        $entity = $this->getTargetEntity($params);
        $this->processEntity($entity, $config['update'], $params);
        if (array_key_exists('methods', $config['update'])) {
            $this->runMethods($config['update']['methods'], $params);
        }
        return $entity;
    }

    /**
     * Delete an entity using the config and the params passed
     * @param array $config
     * @param array $params
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \RuntimeException
     * @return JsonSerializable
     */
    protected function deleteEntity(array $config, array $params) {
        $entity = $this->getTargetEntity($params);
        $this->getEntityManager()->remove($entity);
        if (array_key_exists('methods', $config['delete'])) {
            $this->runMethods($config['delete']['methods'], $params);
        }
        return $entity;
    }

    /**
     * Validates the passed params againest the config and then binds the params to the entity
     * @param JsonSerializable $entity
     * @param array $config
     * @param array $params
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @return JsonSerializable
     */
    protected function processEntity($entity, array $config, array $params) {
        if (array_key_exists('id', $params)) {
            unset($params['id']);
        }

        foreach ($params as $key => $value) {
            $this->getQueryBuilderHelper()->validateFromParams($config['allowed'], $config['deny'], $key);
        }

        $this->bind($entity, $config, $params);
        $this->setLastEntity($entity);
        $this->getEntityManager()->persist($entity);
        return $entity;
    }

    /**
     * Auto magically binds values to the entity, returns an array of fields that could not be bound so an extended version of the class can deal with these additional bindings
     * @param JsonSerializable $entity
     * @param array $config
     * @param array $params
     * @return array
     */
    protected function bind($entity, array $config, array $params) {
        $associationMap = $this->getOrBuildAliasMap($config);
        $failedFields = array();
        $targetEntity = NULL;
        $bindValue = NULL;
        foreach ($params as $key => $value) {
            $parts = $this->getConfigHelper()->breakDownFieldName($key);
            if (count($parts)>1) {
                $setName = $parts[1];
                if (array_key_exists($parts[0], $associationMap)) {
                    $targetEntity = $associationMap[$parts[0]][1];
                    $bindValue = $this->findOneBy(array('id'=>$value));
                } else {
                    $targetEntity = NULL;
                }
            } else {
                $setName = $parts[0];
                $bindValue = $value;
            }
            if ($targetEntity === NULL || !method_exists($entity, $setName)) {
                $failedFields[] = $key;
            } else {
                $setName = 'set' . ucfirst($setName);
                $entity->$setName($bindValue);
            }
        }
        return $failedFields;
    }

    /**
     * Gets a map of aliases to entities
     * @param array $config
     * @return array
     * Example
     * array(
     * 	'[alias]'=>'[entity name]'
     * )
     */
    public function getOrBuildAliasMap(array $config=NULL) {
        $this->aliasMap = ($this->aliasMap !== NULL)?$this->aliasMap:$this->getConfigHelper()->buildAliasMap($config);
        return $this->aliasMap;
    }

    /**
     * Runs any methods in the methods array. If the method is an array it runs it as if the array were array([object or class name], '[method name]').
     * Other wise it treats the method as a method name for the current object
     * Sends the method is calls arguments: ($this, $params, $qb)
     * @param array $methods
     * @param array $params
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function runMethods (array $methods, array $params, $qb = NULL) {
        foreach ($methods as $method) {
            if (is_array($method)) {
                call_user_func_array($method, array($this, $params, $qb));
            } else {
                call_user_func_array(array($this, $method), array($this, $params, $qb));
            }

        }
        return $qb;
    }

    /**
     * triggers the read event
     * @param string $context
     * @param array $params
     * Example:
     * array(
     * 	'[wheres | havings]'=>array(
     * 			array(
     * 				'field'=>'[field name]',
     * 				'type'=>'[and | or]',
     * 				'value'=>'[value for comparison]',
     * 				'operator'=>'[=|!=|<|<=|>|>=|is null|is not null|in|not in|between|like]',
     * 				'value2'=>'[second value for a between]' // optional for betweens
     * 			)
     *		),
     *	'orderBys'=>array(
     *		'field'=>'[field name]',
     *		'direction'=>[asc|desc]
     *	)
     *	'limit'=>[number for limit],
     *	'offset'=>[number offset]
     * )
     * @throws \Zend\EventManager\Exception\InvalidCallbackException
     * @throws \UnexpectedValueException
     * @return QueryBuilder
     */
    public function read($context, array $params) {
        $this->bootstrap();
        $this->getEventManager()->trigger('init', $this, array('context'=>$context, 'params'=>$params));
        $this->getEventManager()->trigger('read', $this, $params);
        return $this->getQueryBuilderHelper()->getQueryBuilderQuery();
    }

    /**
     * Does the work of making a read qb query
     * @param EventInterface $e
     */
    public function onRead(EventInterface $e) {
        $target = $e->getTarget();
        $params = $e->getParams();
        $config = $target->getConfig();
        $qb = $target->getQueryBuilderHelper()->buildReadQb($config, $params);
        $target->setQueryBuilderQuery($qb);
        if (array_key_exists('methods', $config['read'][])) {
            $this->runMethods($config['read']['methods'], $params);
        }
        // NOTE: remember you can do set params on the event to change them for the next listener
    }

    /**
     * Init,  bootstraps the ZendX_Bootstrap_Bootstrapable components and sets up all needed properties of the class for it to function.
     * @param EventInterface $e
     */
    public function onInit(EventInterface $e) {
        $target = $e->getTarget();
        $params = $e->getParams();
        $context = $params['context'];
        $params = $params['params'];
        $target->plugHelpers($context);
        $target->setParams($params);
        $target->clearReadableColumns();
        $target->clearAliasMap();
        $target->clearLastEntity();
        $config = $target->getConfigHelper()->parseConfig(static::getCRUDDefaultConfig(), $this->getCRUDConfig(), $this->getEntityName());
        $target->setParsedConfig($config);

    }

    /**
     * sets up the default helpers for the class, can be overiden to plug in new helpers
     * @param string $context
     */
    protected function plugHelpers($context) {
        if ($this->getConfigHelper() === NULL) {
            $helper = new WyldCRUDConfigHelper();
            $helper->setEntityManager($this->getEntityManager());
            $helper->setContext($context);
            $this->setConfigHelper($helper);
        }
        if ($this->getQueryBuilderHelper() === NULL) {
            $this->setQueryBuilderHelper(new WyldCRUDQueryBuilderHelper());
        }
    }

    /**
     * wire up the events system, fired from ZendX/Bootstrap/BootstrapTrait
     * @throws \Zend\EventManager\Exception\InvalidArgumentException
     */
    protected function _initWyldCRUD() {
        $eventManager = $this->getEventManager();
        $eventManager->attachAggregate($this);
    }

    /**
     * Attach the listeners
     * @param EventManagerInterface $em
     */
    public function attach(EventManagerInterface $em) {
        $em->attach('preInit', array($this, 'onPreInit'));
        $em->attach('preCreate', array($this, 'onPreCreate'));
        $em->attach('preRead', array($this, 'onPreRead'));
        $em->attach('preUpdate', array($this, 'onPreUpdate'));
        $em->attach('preDelete', array($this, 'onPreDelete'));

        $em->attach('init', array($this, 'onInit'));
        $em->attach('create', array($this, 'onCreate'));
        $em->attach('read', array($this, 'onRead'));
        $em->attach('update', array($this, 'onUpdate'));
        $em->attach('delete', array($this, 'onDelete'));

        $em->attach('postInit', array($this, 'onPostInit'));
        $em->attach('postCreate', array($this, 'onPostCreate'));
        $em->attach('postRead', array($this, 'onPostRead'));
        $em->attach('postUpdate', array($this, 'onPostUpdate'));
        $em->attach('postDelete', array($this, 'onPostDelete'));
    }

    /**
     * Stub to be overridden for easy access to events system
     * @param EventInterface $e
     */
    public function onPreInit(EventInterface $e) {}

    /**
     * Stub to be overridden for easy access to events system
     * @param EventInterface $e
     */
    public function onPreCreate(EventInterface $e) {}

    /**
     * Stub to be overridden for easy access to events system
     * @param EventInterface $e
     */
    public function onPreRead(EventInterface $e) {}

    /**
     * Stub to be overridden for easy access to events system
     * @param EventInterface $e
     */
    public function onPreUpdate(EventInterface $e) {}

    /**
     * Stub to be overridden for easy access to events system
     * @param EventInterface $e
     */
    public function onPreDelete(EventInterface $e) {}

    /**
     * Stub to be overridden for easy access to events system
     * @param EventInterface $e
     */
    public function onPostInit(EventInterface $e) {}

    /**
     * Stub to be overridden for easy access to events system
     * @param EventInterface $e
     */
    public function onPostCreate(EventInterface $e) {}

    /**
     * Stub to be overridden for easy access to events system
     * @param EventInterface $e
     */
    public function onPostRead(EventInterface $e) {}

    /**
     * Stub to be overridden for easy access to events system
     * @param EventInterface $e
     */
    public function onPostUpdate(EventInterface $e) {}

    /**
     * Stub to be overridden for easy access to events system
     * @param EventInterface $e
     */
    public function onPostDelete(EventInterface $e) {}

    /**
     * @param EventManagerInterface $em
     */
    public function detach(EventManagerInterface $em) {

    }

    /**
     * @return array|NULL
     */
    public function getParams() {
        return $this->params;
    }


    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params) {
        $this->params = $params;
        return $this;
    }

    /**
     * @return JsonSerializable|NULL
     */
    public function getLastEntity() {
        return $this->lastEntity;
    }

    /**
     * @param $lastEntity
     * @return $this
     */
    protected function setLastEntity($lastEntity) {
        $this->lastEntity = $lastEntity;
        return $this;
    }


    /**
     * @return $this
     */
    protected function clearLastEntity() {
        $this->lastEntity = NULL;
        return $this;
    }

    /**
     * @return array
     */
    static public function getErrorMessages() {
        return static::$errorMessages;
    }


    /**
     * @param array $errorMessages
     */
    static public function setErrorMessages(array $errorMessages) {
        static::$errorMessages = $errorMessages;
    }

    /**
     * @return array|NULL
     */
    static public function getCRUDDefaultConfig() {
        return static::$CRUDDefaultConfig;
    }


    /**
     * @param array $CRUDDefaultConfig
     */
    static public function setCRUDDefaultConfig(array $CRUDDefaultConfig) {
        static::$CRUDDefaultConfig = $CRUDDefaultConfig;
    }

    /**
     * @return array|NULL
     */
    public function getCRUDConfig() {
        return $this->CRUDConfig;
    }


    /**
     * @param array $CRUDConfig
     * @return $this
     */
    public function setCRUDConfig(array $CRUDConfig) {
        $this->CRUDConfig = $CRUDConfig;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getParsedConfig() {
        return $this->parsedConfig;
    }

    /**
     * @param array $parsedConfig
     * @return $this
     */
    protected function setParsedConfig(array $parsedConfig) {
        $this->parsedConfig = $parsedConfig;
        return $this;
    }


    /**
     * @return $this
     */
    public function clearReadableColumns() {
        $this->readableColumns = NULL;
        return $this;
    }


    /**
     * @param array $aliasMap
     * @return $this
     */
    public function setAliasMap(array $aliasMap) {
        $this->aliasMap = $aliasMap;
        return $this;
    }


    /**
     * @return $this
     */
    public function clearAliasMap() {
        $this->aliasMap = NULL;;
        return $this;
    }

    /**
     * @return array
     */
    static public function getOperators() {
        return static::$operators;
    }

    /**
     * @param array $operators
     */
    public function setOperators(array $operators) {
        static::$operators = $operators;
    }


    /**
     * @return null|\WyldFireTools\Helper\WyldCRUDConfigHelpInterface
     */
    public function getConfigHelper() {
        return $this->configHelper;
    }


    /**
     * @param \WyldFireTools\Helper\WyldCRUDConfigHelpInterface $configHelper
     * @return $this
     */
    public function setConfigHelper(WyldCRUDConfigHelpInterface $configHelper) {
        $this->configHelper = $configHelper;
        return $this;
    }


    /**
     * @return null|WyldCRUDQueryBuilderHelperInterface
     */
    public function getQueryBuilderHelper() {
        return $this->queryBuilderHelper;
    }


    /**
     * @param WyldCRUDQueryBuilderHelperInterface $queryBuilderHelper
     * @return $this
     */
    public function setQueryBuilderHelper(WyldCRUDQueryBuilderHelperInterface $queryBuilderHelper) {
        $this->queryBuilderHelper = $queryBuilderHelper;
        return $this;
    }


}