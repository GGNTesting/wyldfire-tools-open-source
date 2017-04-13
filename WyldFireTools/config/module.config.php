<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace WyldFireTools;

use WyldFireTools\Manager\WyldCrudManager;

return array(
    'service_manager' => array(
        'invokables' => array(
           'WyldFireTools\Manager\WyldCrudManagerInterface'=> WyldCrudManager::class
        )
    )
);
