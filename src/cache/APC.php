<?php

namespace HyperClient\cache;

/**
 * APC implementation of iCache
 * @author Luke Stokes
 * @implements \HyperClient\interfaces\iCache
 */
class APC implements \HyperClient\interfaces\iCache
{
    public function exists($key)
    {
        return apc_exists($key);
    }
    public function fetch($key)
    {
        return unserialize(apc_fetch($key));
    }
    public function store($key,$value)
    {
        return apc_store($key,serialize($value));
    }
}