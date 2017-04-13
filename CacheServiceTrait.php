<?php
/**
 * @author Will Ferrer
 * @copyright (c) 2016, GGN
 * @licensee 2016 developed under license for GGN The ultimate tournament platform
 * @license licensed under the terms of
 * the Open Source LGPL 3.0 license.
 */
namespace Common\Traits;

use Zend\Cache\Storage\Adapter\AbstractAdapter;

/**
 * Trait CacheServiceTrait
 * A trait that can be applied to a service in order to give it enhanced caching capabilities, including the use of "PusedoTags"
 * @package Common\Traits
 */
trait CacheServiceTrait
{
    /**
 * A prefix to put in front of every tag when not storing disjunction
 * @var string
 * @access protected
 */
    protected $psuedoTagPrefixSingle = 'psuedoTagSingle_';

    /**
     * A prefix to put in front of every tag when storing disjunction
     * @var string
     * @access protected
     */
    protected $psuedoTagPrefixDisjunction = 'psuedoTagDisjunction_';

    /**
     * The separator to use when making a psuedo tag key
     * @var string
     * @access protected
     */
    protected $psuedoTagSeparator = '<-(pts)->';

    /**
     * A cache object that will be used defaultly by the methods
     * @var AbstractAdapter $cache
     * @access protected
     */
    protected $cache;

    /**
     * A key to hash the psuedo tags keys with. Set to null to prevent hashing
     * @var string
     */
    protected $psuedoTagHashKey = 'psuedoTagHashKey';


    /**
     * Adds tags in the cache to store the passed key associated with the tags. If disjunction option is used then the keys passed can be retrieved by matching only 1 tag, if disjunction is used in the get method call.
     * @param string $key
     * @param string[] $tags
     * @param bool $disjunction
     * @param AbstractAdapter $cache optional cache object to pass to use instead of the base cache stored on the class
     * @throws \Zend\Cache\Exception\ExceptionInterface
     * @throws \Exception
     */
    public function setPsuedoTags($key, array $tags, $disjunction = false, AbstractAdapter $cache = null){
        $this->setPsuedoTagsSingle($key, $tags, false, $cache);
        if ($disjunction === true) {
            $this->setPsuedoTagsDisjunction($key, $tags, $cache);
        }
    }

    /**
     * Adds tags to the cache, 1 for each tag passed, and associated the key to each one. This lets the data be retrieved by matching just 1 tag when $disjunction is used
     * @param string $key
     * @param string[] $tags
     * @param AbstractAdapter $cache optional cache object to pass to use instead of the base cache stored on the class
     * @throws \Zend\Cache\Exception\ExceptionInterface
     * @throws \Exception
     */
    protected function setPsuedoTagsDisjunction($key, array $tags, AbstractAdapter $cache = null) {
        foreach ($tags as $tag) {
            $this->setPsuedoTagsSingle($key, [$tag], true, $cache);
        }
    }


    /**
     * Adds a tag in the cache to store the passed key associated with that tag.
     *
     * @param string $key
     * @param string[] $tags
     * @param bool $disjunction
     * @param AbstractAdapter $cache optional cache object to pass to use instead of the base cache stored on the class
     * @throws \Zend\Cache\Exception\ExceptionInterface
     * @throws \Exception
     * @return boolean
     */
    protected function setPsuedoTagsSingle($key, array $tags, $disjunction = false, AbstractAdapter $cache = null) {
        $cache = $cache===null?$this->getCache():$cache;
        $psuedoTagKey = $this->createPsuedoTagKey($tags, $disjunction);
        $storedKeys = $cache->getItem($psuedoTagKey);
        if ($storedKeys===null) {
            $storedKeys = [$key];
        } else {
            $storedKeys[] = $key;
        }

        return $cache->setItem($psuedoTagKey, $storedKeys);
    }

    /**
     * Get's the items associated with the psuedo tags passed. If disjunction option used then just 1 tag needs to match, if the tags were also saved using the disjunction option.
     *
     * @param string[] $tags
     * @param bool $disjunction
     * @param AbstractAdapter $cache optional cache object to pass to use instead of the base cache stored on the class
     * @return string[]
     * @throws \Zend\Cache\Exception\ExceptionInterface
     * @throws \Exception
     * @returns array
     */
    public function getItemsByPsuedoTags(array $tags, $disjunction = false, AbstractAdapter $cache = null) {
        $cache = $cache===null?$this->getCache():$cache;
        $storedKeys = $this->getKeysByPsuedoTags($tags, $disjunction, true, $cache);
        $returnedIetms = [];
        foreach ($storedKeys as $key) {
            $returnedIetms[$key] = $cache->getItem($key);
        }
        return $returnedIetms;
    }

    /**
     * Get's the keys associated with the psuedo tags passed. If disjunction option used then just 1 tag needs to match, if the tags were also saved using the disjunction option.
     *
     * @param string[] $tags
     * @param bool $disjunction
     * @param bool $clearEmpties
     * @param AbstractAdapter $cache optional cache object to pass to use instead of the base cache stored on the class
     * @return string[]
     * @throws \Zend\Cache\Exception\ExceptionInterface
     * @throws \Exception
     */
    public function getKeysByPsuedoTags(array $tags, $disjunction = false, $clearEmpties = true, AbstractAdapter $cache = null) {
        $storedKeys = $this->getKeysByPsuedoTagSingle($tags, false, $clearEmpties, $cache);
        if ($disjunction === true) {
            $newKeys = $this->getKeysByPsuedoDisjunction($tags, $clearEmpties, $cache);
            $storedKeys = array_merge($storedKeys, $newKeys);
        }
        $storedKeys = array_unique($storedKeys);
        return $storedKeys;
    }

    /**
     * Get's the keys that are associated with a psuedo tag by comparing each tag passed separately. In order to retrive tags this way they must have also been saved with the disjunction option.
     * @param array $tags
     * @param bool $clearEmpties
     * @param AbstractAdapter|null $cache
     * @return array
     * @throws \Zend\Cache\Exception\ExceptionInterface
     * @throws \Exception
     * @access protected
     */
    protected function getKeysByPsuedoDisjunction (array $tags, $clearEmpties = true, AbstractAdapter $cache = null) {
        $storedKeys = [];
        foreach ($tags as $tag) {
            $newKeys = $this->getKeysByPsuedoTagSingle([$tag], true, $clearEmpties, $cache);
            $storedKeys = array_merge($storedKeys, $newKeys);
        }
        return $storedKeys;
    }

    /**
     * Get's the keys that are associated with a psuedo tag
     * @param array $tags
     * @param bool $disjunction
     * @param bool $clearEmpties
     * @param AbstractAdapter|null $cache
     * @return array
     * @throws \Zend\Cache\Exception\ExceptionInterface
     * @throws \Exception
     * @access protected
     */
    protected function getKeysByPsuedoTagSingle (array $tags, $disjunction = false, $clearEmpties = true, AbstractAdapter $cache = null) {
        $cache = $cache===null?$this->getCache():$cache;
        $psuedoTagKey = $this->createPsuedoTagKey($tags, $disjunction);
        $storedKeys = $cache->getItem($psuedoTagKey);
        $filteredKeys = [];
        $resavePsuedoTag = false;
        if ($storedKeys !== null) {
            foreach($storedKeys as $key) {
                if ($cache->hasItem($key)) {
                    $filteredKeys[] = $key;
                } else {
                    $resavePsuedoTag = true;
                }
            }
        }

        if ($clearEmpties === true) {
            if ($resavePsuedoTag) {
                $cache->setItem($psuedoTagKey, $filteredKeys);
            }

            if (count($filteredKeys) === 0) {
                $cache->removeItem($psuedoTagKey);
            }
        }

        return $filteredKeys;
    }

    /**
     * Remove items matching given tags.
     *
     * If $disjunction only one of the given tags must match
     * else all given tags must match.
     *
     * @param string[] $tags
     * @param  bool  $disjunction
     * @param AbstractAdapter $cache optional cache object to pass to use instead of the base cache stored on the class
     * @throws \Zend\Cache\Exception\ExceptionInterface
     * @throws \Exception
     */
    public function clearByPsuedoTags(array $tags, $disjunction = false, AbstractAdapter $cache = null){
        $cache = $cache===null?$this->getCache():$cache;
        $storedKeys = $this->getKeysByPsuedoTags($tags, $disjunction, true, $cache);
        foreach ($storedKeys as $key) {
            $cache->removeItem($key);
        }
    }


    /**
     * Creates a key for the tags passed. The key will always be the same no matter what order the tags were passed in
     * @param string[] $tags
     * @param boolean $disjunction
     * @access protected
     * @return string
     */
    protected function createPsuedoTagKey(array $tags, $disjunction){
        $tagPrefix = $this->getPsuedoTagPrefix($disjunction);
        $separator = $this->getPsuedoTagSeparator();
        sort($tags);
        $key = $tagPrefix . implode($separator, $tags);
        $key = $this->getPsuedoTagHashKey() !== null?md5($key . $this->getPsuedoTagHashKey()):$key;
        return $key;

    }

    /**
     * setter method
     * @param AbstractAdapter $cache
     */
    public function setCache(AbstractAdapter $cache)
    {
        $this->cache = $cache;
    }

    /**
     * setter method
     * @return \Zend\Cache\Storage\Adapter\AbstractAdapter
     * @throws \Exception
     */
    public function getCache()
    {
        if ($this->cache === null){
            throw new \RuntimeException('Error: $this->cache should be initialized in factory first!');
        }
        return $this->cache;
    }

    /**
     * getter method
     * @param boolean $disjunction
     * @return string
     * @access protected
     */
    protected function getPsuedoTagPrefix($disjunction=false)
    {
        return $disjunction===false?$this->getPsuedoTagPrefixSingle():$this->getPsuedoTagPrefixDisjunction();
    }


    /**
     * getter method
     * @return string
     * @access protected
     */
    protected function getPsuedoTagSeparator()
    {
        return $this->psuedoTagSeparator;
    }


    /**
     * getter  method
     * @return string
     * @access protected
     */
    protected function getPsuedoTagPrefixSingle()
    {
        return $this->psuedoTagPrefixSingle;
    }

    /**
     * getter  method
     * @return string
     * @access protected
     */
    protected function getPsuedoTagPrefixDisjunction()
    {
        return $this->psuedoTagPrefixDisjunction;
    }

    /**
     * getter  method
     * @return string
     * @access protected
     */
    protected function getPsuedoTagHashKey()
    {
        return $this->psuedoTagHashKey;
    }


}