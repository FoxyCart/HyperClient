<?php

namespace HyperClient\cache;

/**
 * File based implementation of iCache
 * @author Luke Stokes
 * @implements \HyperClient\interfaces\iCache
 */
class File implements \HyperClient\interfaces\iCache
{
    /**
     * This must be passed in via the constructor.
     * IMPORTANT: It must be a writeable directory for the web server but not web accessible. Also, care must be
     * taken to ensure no one else on a shared server can access this folder.
     * @var string
     */
    private $cache_directory;

    function __construct($cache_directory)
    {
        if (is_writable($cache_directory)) {
            $this->cache_directory = $cache_directory;
        } else {
            throw new \Exception('CacheFile Exception: ' . $cache_directory . ' is not writeable.');
        }
    }

    public function getFileName($key)
    {
        return $this->cache_directory . '/' . $key;
    }

    public function exists($key)
    {
        return file_exists($this->getFileName($key));
    }
    public function fetch($key)
    {
        return unserialize(file_get_contents($this->getFileName($key)));
    }
    public function store($key,$value)
    {
        return file_put_contents($this->getFileName($key),serialize($value));
    }
}
