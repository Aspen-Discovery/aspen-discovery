<?php

class Memcache
{
    private $vars = array();

    public function get($name){
        if (array_key_exists($name, $this->vars)) {
            return $this->vars[$name];
        }
        return false;
    }

    public function set($name, $value, $flag, $timeout) {
        $this->vars[$name] = $value;
    }

    public function delete($name){
        unset($this->vars[$name]);
    }
}