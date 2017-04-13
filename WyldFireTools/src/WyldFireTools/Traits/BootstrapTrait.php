<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Bootstrap
 * @subpackage Bootstrap
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: BootstrapAbstract.php 23775 2011-03-01 17:25:24Z ralph $
 */

namespace WyldFireTools\Traits;
/**
 * Abstract base class for bootstrap classes
 *
 * @category   Zend
 * @package    Zend_Application
 * @subpackage Bootstrap
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
trait BootstrapTrait
{

    /**
     * @var array Internal resource methods (resource/method pairs)
     */
    protected $_classResources;

    /**
     * @var array Initializers that have been run
     */
    protected $_run = array();

    /**
     * @var array Initializers that have been started but not yet completed (circular dependency detection)
     */
    protected $_started = array();

    /**
     * Get class resources (as resource/method pairs)
     *
     * Uses get_class_methods() by default, reflection on prior to 5.2.6,
     * as a bug prevents the usage of get_class_methods() there.
     *
     * @return array
     */
    public function getClassResources()
    {
        if (null === $this->_classResources) {
            $methodNames = get_class_methods($this);

            $this->_classResources = array();
            foreach ($methodNames as $method) {
                if (5 < strlen($method) && '_init' === substr($method, 0, 5)) {
                    $this->_classResources[strtolower(substr($method, 5))] = $method;
                }
            }
        }

        return $this->_classResources;
    }

    /**
     * Get class resource names
     *
     * @return array
     */
    public function getClassResourceNames()
    {
        $resources = $this->getClassResources();
        return array_keys($resources);
    }


    /**
     * Bootstrap individual, all, or multiple resources
     *
     * Marked as final to prevent issues when subclassing and naming the
     * child class 'Bootstrap' (in which case, overriding this method
     * would result in it being treated as a constructor).
     *
     * If you need to override this functionality, override the
     * {@link _bootstrap()} method.
     *
     * @param  null|string|array $resource
     * @return Object
     * @throws \UnexpectedValueException
     */
    final public function bootstrap($resource = null)
    {
        $this->_bootstrap($resource);
        return $this;
    }

    /**
     * Bootstrap implementation
     *
     * This method may be overridden to provide custom bootstrapping logic.
     * It is the sole method called by {@link bootstrap()}.
     *
     * @param  null|string|array $resource
     * @return void
     * @throws \UnexpectedValueException
     */
    protected function _bootstrap($resource = null)
    {
        if (null === $resource) {
            foreach ($this->getClassResourceNames() as $resource) {
                $this->_executeResource($resource);
            }

        } elseif (is_string($resource)) {
            $this->_executeResource($resource);
        } elseif (is_array($resource)) {
            foreach ($resource as $r) {
                $this->_executeResource($r);
            }
        } else {
            throw new \UnexpectedValueException('Invalid argument passed to ' . __METHOD__);
        }
    }

    /**
     * Execute a resource
     *
     * Checks to see if the resource has already been run. If not, it searches
     * first to see if a local method matches the resource, and executes that.
     * If not, it checks to see if a plugin resource matches, and executes that
     * if found.
     *
     * Finally, if not found, it throws an exception.
     *
     * @param  string $resource
     * @return void
     * @throws \UnexpectedValueException( When resource not found
     */
    protected function _executeResource($resource)
    {
        $resourceName = strtolower($resource);

        if (in_array($resourceName, $this->_run, true)) {
            return;
        }

        if (array_key_exists($resourceName, $this->_started) && $this->_started[$resourceName]) {
            throw new \UnexpectedValueException('Circular resource dependency detected');
        }

        $classResources = $this->getClassResources();
        if (array_key_exists($resourceName, $classResources)) {
            $this->_started[$resourceName] = true;
            $method = $classResources[$resourceName];
            $this->$method();
            unset($this->_started[$resourceName]);
            $this->_markRun($resourceName);

            return;
        }

        throw new \UnexpectedValueException('Resource matching "' . $resource . '" not found');
    }


    /**
     * Mark a resource as having run
     *
     * @param  string $resource
     * @return void
     */
    protected function _markRun($resource)
    {
        if (!in_array($resource, $this->_run, true)) {
            $this->_run[] = $resource;
        }
    }

}
?>