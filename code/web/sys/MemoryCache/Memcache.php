<?php
/**
 * Created by PhpStorm.
 * User: mdnob
 * Date: 1/23/2019
 * Time: 2:58 PM
 */

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