<?php
require_once "Cache.php";

class SimpleCacheTest extends PHPUnit_Framework_TestCase {

  function testAdd() {
    SimpleCache::resetCache();
    $cache = new SimpleCache();
    $result = $cache->add('test', 'data');
    $this->assertTrue($result);
    return $cache;
  }

  /**
   * @depends testAdd
   */
  function testGet($cache) {
    $result = $cache->get('test');
    $this->assertEquals('data', $result);
    return $cache;
  }

  /**
   * @depends testGet
   */
  function testSetAlreadySet($cache) {
    $result = $cache->set('test', 'woot');
    $this->assertTrue($result);
    $result = $cache->get('test');
    $this->assertEquals('woot', $result);
    return $cache;
  }

  /**
   * @depends testSetAlreadySet
   */
  function testSetNotSet($cache) {
    $result = $cache->set('test2', 'awesomesauce');
    $this->assertTrue($result);
    $result = $cache->get('test2');
    $this->assertEquals('awesomesauce', $result);
    return $cache;
  }

  /**
   * @depends testSetNotSet
   */
  function testDeleteDoesntExist($cache) {
    $result = $cache->delete('nothing');
    $this->assertFalse($result);
    return $cache;
  }

  /**
   * @depends testDeleteDoesntExist
   */
  function testDeleteDoesExist($cache) {
    $result = $cache->delete('test');
    $this->assertTrue($result);
    $result = $cache->get('test');
    $this->assertFalse($result);
    $result = $cache->get('test2');
    $this->assertEquals('awesomesauce', $result);
    return $cache;
  }
  
  function testAddNull() {
    SimpleCache::resetCache();
    $cache = new SimpleCache();
    $result = $cache->add('test', NULL);
    $this->assertTrue($result);
    return $cache;
  }

  /**
   * @depends testAddNull
   */
  function testAddNullAgain($cache) {
    $result = $cache->add('test', 'NULL');
    $this->assertFalse($result);
    return $cache;
  }

  /**
   * @depends testAddNullAgain
   */
  function testGetNull($cache) {
    $result = $cache->get('test');
    $this->assertEquals(NULL, $result);
    return $cache;
  }

  /**
   * @depends testGetNull
   */
  function testDeleteNull($cache) {
    $result = $cache->delete('test');
    $this->assertTrue($result);
  }

  function testCacheSize() {
    SimpleCache::resetCache();
    SimpleCache::setCacheSize(2);
    $cache = new SimpleCache();
    $cache->add('test1', 'woot1');
    $cache->add('test2', 'woot2');
    $cache->add('test3', 'woot3');
    $cache->add('test4', 'woot4');
    $this->assertFalse($cache->get('test1'));
    $this->assertFalse($cache->get('test2'));
    $this->assertEquals('woot3', $cache->get('test3'));
    $this->assertEquals('woot4', $cache->get('test4'));
    return $cache;
  }

  /**
   * @depends testCacheSize
   */
  function testCacheSizeDelete($cache) {
    $this->assertFalse($cache->delete('test1'));
    $this->assertFalse($cache->delete('test2'));
    $this->assertTrue($cache->delete('test3'));
    $this->assertTrue($cache->delete('test4'));
    $this->assertFalse($cache->get('test3'));
    $this->assertFalse($cache->get('test4'));
    return $cache;
  }

  /**
   * @depends testCacheSizeDelete
   */
  function testCacheAfterEviction($cache) {
    $cache->add('test1', 'woot1');
    $cache->add('test2', 'woot2');
    $cache->add('test3', 'woot3');
    $cache->add('test4', 'woot4');
    $this->assertFalse($cache->get('test1'));
    $this->assertFalse($cache->get('test2'));
    $this->assertEquals('woot3', $cache->get('test3'));
    $this->assertEquals('woot4', $cache->get('test4'));
    return $cache;
  }

}
