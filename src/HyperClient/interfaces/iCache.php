<?php

namespace HyperClient\interfaces;

/**
 * An interface for defining the methods our HyperClient caching mechanisms should support.
 * @author Luke Stokes
 *
 */
interface iCache
{
    /**
     * Check if a value exists for this key
     * similar to apc_exists
     * @param string $key
     */
    public function exists($key);

    /**
     * Fetch a value for this key. The value will be unserialized() before returning it.
     * similar to apc_fetch
     * @param string $key
     * @return mixed
     */
    public function fetch($key);

    /**
     * Store a value for this key. The value will be seralized() before storing it.
     * similar to apc_store
     * @param string $key
     * @param mixed $value
     */
    public function store($key,$value);
}
