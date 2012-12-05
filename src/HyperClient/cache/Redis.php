<?php

namespace HyperClient\cache;

/**
 * Redis cache backend
 * @author Fred Alger
 */
class Redis implements \HyperClient\interfaces\iCache
{
    private $redis;

    public function __construct($redis_server="localhost", $redis_port=6379, $db=1) {
        if (!class_exists("Redis")) {
            throw new Exception("CacheRedis needs the phpredis extension. Check your php.ini");
        }

        $this->redis = new \Redis();
        $this->redis->connect($redis_server, $redis_port);
        $this->redis->select($db);
    }

    public function store($key,$value) {
        return $this->redis->set($key, serialize($value));
    }

    public function fetch($key) {
        return unserialize($this->redis->get($key));
    }

    public function exists($key) {
        return $this->redis->exists($key);
    }
}