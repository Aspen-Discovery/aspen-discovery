<?php
/**
 * This file represents a Fedora Date. It wraps the PHP datetime functions
 * specifically for the date format used in Fedora. This allows the easy
 * comparison of dates for example.
 */

class FedoraDate extends DateTime {

  /**
   * Get the date in a format that Fedora can use.
   *
   * @return string
   *   A fedora formated iso8601 date.
   */
  function __toString() {
    // Fedora will only accept 3 decial places for fractional seconds and PHP
    // returns 6 by default. So we do a little string mangling to make them
    // friends again.
    $string = (string) $this->format("Y-m-d\TH:i:s.u");
    $exploded = explode('.', $string);
    $exploded[1] = substr($exploded[1],0,3);
    $string = implode('.', $exploded);
    $string .= 'Z';
    return $string;
  }

  /**
   * Instantiate FedoraDate.
   *
   * @param string $time
   *   The date as you would pass to create a DateTime object.
   */
  function  __construct($time) {
    // Make sure we have a default timezone set. We need to use the shutup
    // operator because getting the timezone if its not set will actually
    // throw a warning. Ugh.
    date_default_timezone_set(@date_default_timezone_get());
    parent::__construct($time, new DateTimeZone('UTC'));
  }

  /**
   * Serialize this object.
   *
   * @return array
   *   The class members to be serialized.
   */
  public function __sleep(){
    // PHP Date class loses information when serialized so we need to convert
    // it to a string and then reconstruct it.
    $this->serializedDate = (string) $this;
    return array('serializedDate');
  }

  /**
   * Unserialize this object.
   */
  public function __wakeup() {
    // PHP Date class loses information when serialized so we need to convert
    // it to a string and then reconstruct it.
    $this->__construct($this->serializedDate);
  }
}
