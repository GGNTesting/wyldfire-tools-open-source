<?php

/**
 * @author Will Ferrer
 * @copyright (c) 2015, GGN
 * @licensee 2015 developed under license for GGN The ultimate tournament platform
 * @license licensed under the terms of
 * the Open Source LGPL 3.0 license.
 */

namespace WyldFireTools\Traits;
/*
 * A trait that allows inheritance in arrays
 */
trait WyldArrayConfigTrait
{
    /**
     * Error to throw when loop is detected
     * @var string
     * @access protected
     */
    protected $loopErrorMessage = 'Error: ArrayConfigTrait detected an inheritance loop';

    /**
     * Allows an settings under a key in an array inherit settings under a sibling key (like ini inheritance)
     * Example:
     * array(
     * 	'parent'=>array(
     * 		'foo'=>'bar'
     * 	),
     * 	'child'=>array(
     * 		'inherits'=>array('parent'),
     * 	)
     * )
     * @param array $array
     * @param string $key
     * @throws \RuntimeException
     * @return array
     */
    public function parseArrayConfig(array $array, $key) {
        // Merged elements are added to an array so that loop detection can happen
        $merged = array($key);
        // Get inherits list or empty array
        $inherits = (array_key_exists('inherits', $array[$key]))?$array[$key]['inherits']:array();
        // Get starting place for merge
        $mergeResult = $array[$key];
        // Keep merging while still items in the array
        while (count($inherits)) {
            // Get the next one in the inherits list
            $next = array_pop($inherits);
            // Add the next inheritance to the array of what has been merged and if it is already merged then we detect a loop
            $merged[] = $next;
            if(count(array_count_values(array_unique($merged)))<count($merged))
            {
                throw new \RuntimeException();
            }
            // Put in blank array if no inherits
            $array[$next]['inherits'] = (array_key_exists('inherits', $array[$next]))?$array[$next]['inherits']:array();
            // Merge over the next sibling
            $mergeResult = array_merge_recursive($array[$next], $mergeResult);
            // Get the new merged array of inherits
            $inherits = $mergeResult['inherits'];
        }
        return $mergeResult;
    }

    /**
     * @return string
     */
    public function getLoopErrorMessage() {
        return $this->loopErrorMessage;
    }


    /**
     * @param $loopErrorMessage
     * @return $this
     */
    public function setLoopErrorMessage($loopErrorMessage) {
        $this->loopErrorMessage = $loopErrorMessage;
        return $this;
    }


}

?>