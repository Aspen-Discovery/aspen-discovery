<?php
require_once "FedoraDate.php";

class FedoraDateTest extends PHPUnit_Framework_TestCase {

  function testToString() {
    $date = new FedoraDate("2012-03-13T19:15:07.529Z");
    $this->assertEquals("2012-03-13T19:15:07.529Z", (string)$date);
  }

  function testDateTimeSubclass() {
    $date = new FedoraDate("2012-03-13T19:15:07.529Z");
    $this->assertTrue(is_subclass_of($date, "DateTime"));
  }

  function testSerializeDate() {
    $date = new FedoraDate("2012-03-13T19:15:07.529Z");
    $serialized = serialize($date);
    $serialized_date = unserialize($serialized);
    $this->assertEquals((string) $date, (string) $serialized_date);
  }

}
