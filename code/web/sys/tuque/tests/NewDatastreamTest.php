<?php

require_once 'Datastream.php';
require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'TestHelpers.php';

class NewDatastreamTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $this->repository = new FedoraRepository($this->api, $cache);

    // create an object
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $this->testPid = "$string1:$string2";
    $this->api->m->ingest(array('pid' => $this->testPid));
    $this->object = new FedoraObject($this->testPid, $this->repository);
    $this->x = new NewFedoraDatastream('one', 'X', $this->object, $this->repository);
    $this->m = new NewFedoraDatastream('two', 'M', $this->object, $this->repository);
    $this->e = new NewFedoraDatastream('three', 'E', $this->object, $this->repository);
    $this->r = new NewFedoraDatastream('four', 'R', $this->object, $this->repository);
  }

  protected function tearDown() {
    $this->api->m->purgeObject($this->testPid);
  }

  /**
   * @expectedException PHPUnit_Framework_Error
   */
  public function testConstructor() {
    $x = new NewFedoraDatastream('foo', 'zap', $this->object, $this->repository);
  }

  public function testGetControlGroup() {
    $this->assertEquals('X', $this->x->controlGroup);
    $this->assertEquals('M', $this->m->controlGroup);
    $this->assertEquals('E', $this->e->controlGroup);
    $this->assertEquals('R', $this->r->controlGroup);
    $this->assertTrue(isset($this->r->controlGroup));
    $this->assertTrue(isset($this->e->controlGroup));
    $this->assertTrue(isset($this->m->controlGroup));
    $this->assertTrue(isset($this->x->controlGroup));
  }

  /**
   * @expectedException PHPUnit_Framework_Error
   */
  public function testUnsetControlGroup() {
    unset($this->x->controlGroup);
  }

  /**
   * @expectedException PHPUnit_Framework_Error
   */
  public function testSetControlGroup() {
    $this->x->controlGroup = 'M';
  }

  public function testId() {
    $this->assertEquals('one', $this->x->id);
    $this->assertEquals('two', $this->m->id);
    $this->assertEquals('three', $this->e->id);
    $this->assertEquals('four', $this->r->id);
  }

  public function testState() {
    $this->assertEquals('A', $this->m->state);
    $this->m->state = 'deleted';
    $this->assertEquals('D', $this->m->state);
  }

  public function testSetContentMString() {
    $this->m->content = 'foo';
    $this->assertEquals('foo', $this->m->content);
    $temp = tempnam(sys_get_temp_dir(), 'tuque');
    $this->m->getContent($temp);
    $this->assertEquals('foo', file_get_contents($temp));
    unlink($temp);
  }

  public function testSetContentXString() {
    $this->x->content = 'foo';
    $this->assertEquals('foo', $this->x->content);
    $temp = tempnam(sys_get_temp_dir(), 'tuque');
    $this->x->getContent($temp);
    $this->assertEquals('foo', file_get_contents($temp));
    unlink($temp);
  }
}