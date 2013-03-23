<?php

abstract class BaseCache
{
    abstract public function get($key);
    abstract public function set($key, $value, $expire);
    abstract public function delete($key);
    abstract public function clear();
}
