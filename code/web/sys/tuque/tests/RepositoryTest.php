<?php

require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'TestHelpers.php';
require_once 'RepositoryConnection.php';
require_once 'FedoraApi.php';

class RepositoryTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $this->repository = new FedoraRepository($this->api, $cache);
  }

  public function testIngestNamespace() {
    $namespace = FedoraTestHelpers::randomString(10);
    $object = $this->repository->constructObject($namespace);
    $object->label = 'foo';
    $object->state = 'd';
    $object->owner = 'woot';
    $this->repository->ingestObject($object);

    $id_array = explode(':', $object->id);
    $this->assertEquals($namespace, $id_array[0]);

    $this->assertTrue($object instanceof FedoraObject);
    $this->assertEquals('foo', $object->label);
    $this->assertEquals('D', $object->state);
    $this->assertEquals('woot', $object->owner);

    $this->repository->purgeObject($object->id);
  }

  public function testIngestNoParams() {
    $object = $this->repository->constructObject();
    $id = $object->id;
    $object->label = 'foo';
    $object->state = 'd';
    $object->owner = 'woot';
    $this->repository->ingestObject($object);

    $this->assertTrue($object instanceof FedoraObject);
    $this->assertEquals($id, $object->id);
    $this->assertEquals('foo', $object->label);
    $this->assertEquals('D', $object->state);
    $this->assertEquals('woot', $object->owner);

    $this->repository->purgeObject($object->id);
  }

  public function testIngestFullPid() {
    $namespace = FedoraTestHelpers::randomString(10);
    $localid = FedoraTestHelpers::randomString(10);
    $id = "$namespace:$localid";
    $object = $this->repository->constructObject($id);
    $object->label = 'foo';
    $object->state = 'd';
    $object->owner = 'woot';
    $this->repository->ingestObject($object);

    $this->assertTrue($object instanceof FedoraObject);
    $this->assertEquals($id, $object->id);
    $this->assertEquals('foo', $object->label);
    $this->assertEquals('D', $object->state);
    $this->assertEquals('woot', $object->owner);

    $this->repository->purgeObject($object->id);
  }

  public function testIngestUuid() {
    $object = $this->repository->constructObject(NULL, TRUE);
    $object->label = 'foo';
    $object->state = 'd';
    $object->owner = 'woot';
    $this->repository->ingestObject($object);

    $id_array = explode(':', $object->id);
    $is_uuid = preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-(:?8|9|a|b)[a-f0-9]{3}-[a-f0-9]{12}\z/',
                          $id_array[1]);

    $this->assertEquals(TRUE, $is_uuid);
    $this->assertTrue($object instanceof FedoraObject);
    $this->assertEquals('foo', $object->label);
    $this->assertEquals('D', $object->state);
    $this->assertEquals('woot', $object->owner);

    $this->repository->purgeObject($object->id);
  }

  public function testIngestUuidNamespaced() {
    $namespace = FedoraTestHelpers::randomString(10);
    $object = $this->repository->constructObject($namespace, TRUE);
    $object->label = 'foo';
    $object->state = 'd';
    $object->owner = 'woot';
    $this->repository->ingestObject($object);

    $id_array = explode(':', $object->id);
    $is_uuid = preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-(:?8|9|a|b)[a-f0-9]{3}-[a-f0-9]{12}\z/',
                          $id_array[1]);

    $this->assertEquals($namespace, $id_array[0]);
    $this->assertEquals(TRUE, $is_uuid);
    $this->assertTrue($object instanceof FedoraObject);
    $this->assertEquals('foo', $object->label);
    $this->assertEquals('D', $object->state);
    $this->assertEquals('woot', $object->owner);

    $this->repository->purgeObject($object->id);
  }

  public function testNextIDUuidNamespaced() {
    $namespace = FedoraTestHelpers::randomString(10);
    $id = $this->repository->getNextIdentifier($namespace, TRUE);

    $id_array = explode(':', $id);
    $is_uuid = preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-(:?8|9|a|b)[a-f0-9]{3}-[a-f0-9]{12}\z/',
                          $id_array[1]);

    $this->assertEquals($namespace, $id_array[0]);
    $this->assertEquals(TRUE, $is_uuid);
  }

  public function testNextIDUuid() {
    $id = $this->repository->getNextIdentifier(NULL, TRUE);

    $id_array = explode(':', $id);
    $is_uuid = preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-(:?8|9|a|b)[a-f0-9]{3}-[a-f0-9]{12}\z/',
                          $id_array[1]);

    $this->assertEquals(TRUE, $is_uuid);
  }

  public function testNextIDNamespaced() {
    $namespace = FedoraTestHelpers::randomString(10);
    $id = $this->repository->getNextIdentifier($namespace, true);

    $id_array = explode(':', $id);

    $this->assertEquals($namespace, $id_array[0]);
  }

  public function testNextIDGetTwo() {
    $ids = $this->repository->getNextIdentifier(NULL, FALSE, 2);
    $this->assertEquals(2, count($ids));
  }

}
