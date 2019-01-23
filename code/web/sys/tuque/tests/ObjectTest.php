<?php

require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'TestHelpers.php';

class ObjectTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $repository = new FedoraRepository($this->api, $cache);

    // create an object
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $this->testDsid = FedoraTestHelpers::randomCharString(10);
    $this->testPid = "$string1:$string2";
    $this->api->m->ingest(array('pid' => $this->testPid));
    $this->api->m->addDatastream($this->testPid, $this->testDsid, 'string', '<test> test </test>', NULL);
    $this->object = new FedoraObject($this->testPid, $repository);
  }

  protected function tearDown() {
    $this->api->m->purgeObject($this->testPid);
  }

  protected function getValue($data) {
    $values = $this->api->a->getObjectProfile($this->testPid);
    return $values[$data];
  }

  public function testValuesInFedora() {
    $this->object->label = 'foo';
    $this->assertEquals('foo', $this->getValue('objLabel'));

    $this->object->owner = 'foo';
    $this->assertEquals('foo', $this->getValue('objOwnerId'));

    $this->object->state = 'I';
    $this->assertEquals('I', $this->getValue('objState'));
  }

  public function testObjectLabel() {
    $this->assertEquals('', $this->object->label);

    $this->object->label = 'foo';
    $this->assertEquals('foo', $this->object->label);
    $this->assertTrue(isset($this->object->label));

    unset($this->object->label);
    $this->assertFalse(isset($this->object->label));


    $this->object->label = 'woot';
    $this->assertEquals('woot', $this->object->label);

    $this->object->label = 'aboot';
    $this->assertEquals('aboot', $this->object->label);

    $this->object->label = FedoraTestHelpers::randomString(355);
    $this->assertEquals(255, strlen($this->object->label));
  }

  public function testObjectLabelSerialization() {
    $this->assertEquals('', $this->object->label);
    $this->object->label = 'first';
    $this->assertEquals('first', $this->object->label);
    $this->assertTrue(isset($this->object->label));

    $temp = serialize($this->object);

    // Destroy but leave the connection exiting via the tests reference.
    unset($this->object);

    $this->object = unserialize($temp);

    $this->assertEquals('first', $this->object->label);
    $this->object->label = 'foo';
    $this->assertEquals('foo', $this->object->label);
    $this->assertTrue(isset($this->object->label));
  }

  public function testObjectOwner() {
    $this->assertEquals(FEDORAUSER, $this->object->owner);
    $this->object->owner = 'foo';
    $this->assertEquals('foo', $this->object->owner);
    $this->assertTrue(isset($this->object->owner));

    unset($this->object->owner);
    $this->assertEquals('', $this->object->owner);
    $this->assertFalse(isset($this->object->owner));

    $this->object->owner = 'woot';
    $this->assertEquals('woot', $this->object->owner);

    $this->object->owner = 'aboot';
    $this->assertEquals('aboot', $this->object->owner);
  }

  public function testObjectId() {
    $this->assertEquals($this->object->id, $this->testPid);
    $this->assertTrue(isset($this->object->id));
  }

  /**
   * @depends testObjectIdChangeException
   */
  public function testObjectIdDidntChange() {
    $this->assertEquals($this->object->id, $this->testPid);
  }

  public function testObjectState() {
    $this->assertEquals('A', $this->object->state);

    $this->object->state = 'I';
    $this->assertEquals('I', $this->object->state);
    $this->object->state = 'A';
    $this->assertEquals('A', $this->object->state);
    $this->object->state = 'D';
    $this->assertEquals('D', $this->object->state);

    $this->object->state = 'i';
    $this->assertEquals('I', $this->object->state);
    $this->object->state = 'a';
    $this->assertEquals('A', $this->object->state);
    $this->object->state = 'd';
    $this->assertEquals('D', $this->object->state);

    $this->object->state = 'inactive';
    $this->assertEquals('I', $this->object->state);
    $this->object->state = 'active';
    $this->assertEquals('A', $this->object->state);
    $this->object->state = 'deleted';
    $this->assertEquals('D', $this->object->state);
  }

  public function testObjectDelete() {
    $this->assertEquals('A', $this->object->state);
    $this->object->delete();
    $this->assertEquals('D', $this->object->state);
  }

  public function testObjectGetDs() {
    $this->assertEquals(2, count($this->object));
    $this->assertTrue(isset($this->object['DC']));
    $this->assertTrue(isset($this->object[$this->testDsid]));
    $this->assertFalse(isset($this->object['foo']));
    $this->assertFalse($this->object['foo']);
    $this->assertEquals('DC', $this->object['DC']->id);
    foreach ($this->object as $id => $ds) {
      $this->assertTrue(in_array($id, array('DC', $this->testDsid)));
      $this->assertTrue(in_array($ds->id, array('DC', $this->testDsid)));
    }
    $this->assertEquals("\n<test> test </test>\n", $this->object[$this->testDsid]->content);
  }

  public function testObjectIngestDs() {
    $newds = $this->object->constructDatastream('test', 'M');
    $newds->label = 'I am a new day!';
    $newds->content = 'tro lo lo lo';
    $this->object->ingestDatastream($newds);
    $this->assertEquals('I am a new day!', $newds->label);
    $this->assertEquals('text/xml', $newds->mimetype);
    $this->assertEquals('tro lo lo lo', $newds->content);
  }

  public function testObjectIngestXmlDs() {
    $newds = $this->object->constructDatastream('test', 'X');
    $newds->content = '<xml/>';
    $this->object->ingestDatastream($newds);
    $this->assertEquals("\n<xml></xml>\n", $newds->content);
  }

  public function testObjectIngestDsFile() {
    $temp = tempnam(sys_get_temp_dir(), 'tuque');
    file_put_contents($temp, 'this is a tesssst!');

    $newds = $this->object->constructDatastream('test', 'M');
    $newds->label = 'I am a new day!';
    $newds->setContentFromFile($temp);
    $this->object->ingestDatastream($newds);
    $this->assertEquals('I am a new day!', $newds->label);
    $this->assertEquals('text/xml', $newds->mimetype);
    $this->assertEquals('this is a tesssst!', $newds->content);
    unlink($temp);
  }

  public function testObjectIngestDsChangeFile() {
    $temp = tempnam(sys_get_temp_dir(), 'tuque');
    file_put_contents($temp, 'this is a tesssst!');

    $newds = $this->object->constructDatastream('test', 'M');
    $newds->label = 'I am a new day!';
    $newds->setContentFromFile($temp);
    file_put_contents($temp, 'walla walla');
    $this->object->ingestDatastream($newds);

    $this->assertEquals('I am a new day!', $newds->label);
    $this->assertEquals('text/xml', $newds->mimetype);
    $this->assertEquals('this is a tesssst!', $newds->content);
    unlink($temp);
  }

  public function testObjectModels() {
    $models = $this->object->models;
    $this->assertEquals(array('fedora-system:FedoraObject-3.0'), $models);
    $this->object->relationships->add(FEDORA_MODEL_URI, 'hasModel', 'pid:woot');
    $this->object->relationships->add(FEDORA_MODEL_URI, 'hasModel', 'pid:rofl');
    $models = $this->object->models;
    $this->assertEquals(array('pid:woot', 'pid:rofl', 'fedora-system:FedoraObject-3.0'), $models);
  }

  public function testObjectModelsAdd() {
    $this->object->models = array('router:killah', 'jon:is:great');
    $this->assertEquals(array('router:killah', 'jon:is:great', 'fedora-system:FedoraObject-3.0'), $this->object->models);
  }

  public function testDatastreamMutation() {
    $newds = $this->object->constructDatastream('test', 'M');
    $newds->label = 'I am a new day!';
    $newds->mimetype = 'text/plain';
    $newds->content = 'walla walla';

    $this->assertTrue($newds instanceof NewFedoraDatastream, 'Datastream is new.');
    $this->assertTrue($this->object->ingestDatastream($newds) !== FALSE, 'Datastream ingest succeeded.');
    $this->assertTrue($newds instanceof FedoraDatastream, 'Datastream mutated on ingestion.');
  }

  public function testObjectMutationAfterDatastreamIngestion() {
    $ds = $this->object->constructDatastream('woot');
    $this->object->ingestDatastream($ds);
    $this->object->label = 'foo';
  }

  public function testObjectMutationAfterDatastreamDeletion() {
    $ds = $this->object->constructDatastream('woot');
    $this->object->ingestDatastream($ds);
    $this->object->purgeDatastream($ds->id);
    $this->object->label = 'foo';
  }
}
