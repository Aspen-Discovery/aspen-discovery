<?php

/**
 * @file
 * This file contains the class MagicProperty
 */

/**
 * This abstract class allows us to implement PHP magic properties by defining
 * a private method in the class that entends it. It attemtps to make the magic
 * properties behave as much like normal PHP properties as possible.
 *
 * This code lets the user define a new method that will be called when a
 * property is accessed. Any method that ends in MagicProperty is code that
 * implements a magic property.
 *
 * Usage Example
 * @code
 * class MyClass extends MagicProperty {
 *   private $secret;
 *
 *   protected function myExampleMagicProperty($function, $value) {
 *     switch($function) {
 *       case 'set':
 *         $secret = $value;
 *         return;
 *       case 'get':
 *         return $secret;
 *       case 'isset':
 *         return isset($secret);
 *       case 'unset':
 *         return unset($secret);
 *     }
 *   }
 * }
 *
 * $test = new MyClass();
 * $test->myExample = 'woot';
 * print($test->myExample);
 * @endcode
 */
abstract class MagicProperty {

  /**
   * Returns the name of the magic property. Makes it easy to change what we
   * use as the name.
   */
  protected function getGeneralMagicPropertyMethodName($name) {
    $method = $name . 'MagicProperty';
    return $method;
  }

  /**
   * This implements the PHP __get function which is utilized for reading data
   * from inaccessible properties. It wraps it by calling the appropriatly named
   * method in the inherteting class.
   * http://php.net/manual/en/language.oop5.overloading.php
   *
   * @param string $name
   *   The name of the function being called.
   *
   * @return void
   *   The data returned from the property.
   */
  public function __get($name) {
    $generalmethod = $this->getGeneralMagicPropertyMethodName($name);
    $specificmethod = $generalmethod . 'Get';
    if (method_exists($this, $specificmethod)) {
      return $this->$specificmethod();
    }
    elseif (method_exists($this, $generalmethod)) {
      return $this->$generalmethod('get',NULL);
    }
    else {
      // We trigger an error like php would. This helps with debugging.
      $trace = debug_backtrace();
      $class = get_class($trace[0]['object']);
      trigger_error(
        'Undefined property: ' . $class . '::$' . $name .
        ' in ' . $trace[0]['file'] .
        ' on line ' . $trace[0]['line'] . ' triggered via __get',
        E_USER_NOTICE);
      return NULL;
    }
  }

  /**
   * This implements the PHP __isset function which is utilized for testing if
   * data in inaccessable properties is set. This function calls the
   * approprietly named method in the inhereting class.
   * http://php.net/manual/en/language.oop5.overloading.php
   *
   * @param string $name
   *   The name of the function being called.
   *
   * @return boolean
   *   If the variable is set.
   */
  public function __isset($name) {
    $generalmethod = $this->getGeneralMagicPropertyMethodName($name);
    $specificmethod = $generalmethod . 'Isset';
    if (method_exists($this, $specificmethod)) {
      return $this->$specificmethod();
    }
    elseif (method_exists($this, $generalmethod)) {
      return $this->$generalmethod('isset',NULL);
    }
    else {
      return FALSE;
    }
  }

  /**
   * This implements the PHP __set function which is utilized for setting
   * inaccessable properties.
   * http://php.net/manual/en/language.oop5.overloading.php
   *
   * @param string $name
   *   The property to set.
   * @param void $value
   *   The value it should be set with.
   */
  public function __set($name, $value) {
    $generalmethod = $this->getGeneralMagicPropertyMethodName($name);
    $specificmethod = $generalmethod . 'Set';
    if (method_exists($this, $specificmethod)) {
      return $this->$specificmethod($value);
    }
    elseif (method_exists($this, $generalmethod)) {
      $this->$generalmethod('set', $value);
    }
    else {
      // Else we allow it to be set like a normal property.
      $this->$name = $value;
    }
  }

  /**
   * This implements the PHP __unset function which is utilized for unsetting
   * inaccessable properties.
   * http://php.net/manual/en/language.oop5.overloading.php
   *
   * @param string $name
   *   The property to unset
   */
  public function __unset($name) {
    $generalmethod = $this->getGeneralMagicPropertyMethodName($name);
    $specificmethod = $generalmethod . 'Unset';
    if (method_exists($this, $specificmethod)) {
      return $this->$specificmethod();
    }
    elseif (method_exists($this, $generalmethod)) {
      return $this->$generalmethod('unset',NULL);
    }
  }

  /**
   * Test if a property appears to be magical.
   *
   * @param string $name
   *   The name of a property to test.
   *
   * @return bool
   *   TRUE if the property appears to be magically implemented; otherwise,
   *   FALSE.
   */
  protected function propertyIsMagical($name) {
    $generalmethod = $this->getGeneralMagicPropertyMethodName($name);
    if (method_exists($this, $generalmethod)) {
      return TRUE;
    }
    $ops = array('set', 'isset', 'unset', 'get');
    foreach ($ops as $op) {
      if (method_exists($this, "{$generalmethod}{$op}")) {
        return TRUE;
      }
    }
    return FALSE;
  }
}
