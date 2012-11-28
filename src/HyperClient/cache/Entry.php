<?php

namespace HyperClient\cache;

/**
 * A simple class for structuring the value we're going to serialize and cache
 * @author Luke Stokes
 */
class Entry
{
    public $etag = '';
    public $header = '';
    public $body = '';
    public $content_type = '';
}
