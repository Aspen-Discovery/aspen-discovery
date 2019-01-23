<?php

require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'TestHelpers.php';
require_once 'tests/ObjectTest.php';

class NewObjectTest extends ObjectTest {

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
    $string3 = FedoraTestHelpers::randomString(9);
    $string4 = FedoraTestHelpers::randomString(9);
    $this->testDsid2 = FedoraTestHelpers::randomCharString(9);
    $this->testPid2 = "$string3:$string4";

    $this->object = $repository->constructObject($this->testPid);
    $ds = $this->object->constructDatastream($this->testDsid);
    $ds->content = "\n<test> test </test>\n";
    $this->object->ingestDatastream($ds);

    $ds = $this->object->constructDatastream('DC');
    $ds->content = '<test> test </test>';
    $this->object->ingestDatastream($ds);

    $this->existing_object = $repository->constructObject($this->testPid2);
    $ds2 = $this->existing_object->constructDatastream($this->testDsid2);
    $ds2->label = 'asdf';
    $ds2->mimetype = 'text/plain';
    $ds2->content = FedoraTestHelpers::randomString(10);
    $this->existing_object->ingestDatastream($ds2);
    $repository->ingestObject($this->existing_object);
  }

  protected function tearDown() {
  }


  public function testObjectIngestXmlDs() {
    $newds = $this->object->constructDatastream('test', 'X');
    $newds->content = '<xml/>';
    $this->object->ingestDatastream($newds);
    $this->assertEquals("<xml/>", $newds->content);
  }

  public function testValuesInFedora() {
  }

  public function testChangeId() {
    $newid = 'new:id';
    $this->object->id = $newid;
    $this->assertEquals($newid, $this->object->id);
  }

  public function testChangeIdWithRelsExt() {
    $newid = 'new:id';
    $this->object->relationships->add(FEDORA_MODEL_URI, 'hasModel', 'pid:woot');
    $this->object->id = $newid;
    $this->assertEquals($newid, $this->object->id);

    $dom = new DOMDocument();
    $dom->loadXml($this->object['RELS-EXT']->content);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('rdf', RDF_URI);

    $results = $xpath->query('/rdf:RDF/rdf:Description/@rdf:about');
    $this->assertEquals(1, $results->length);

    $value = $results->item(0);
    $uri = explode('/', $value->value);

    $this->assertEquals($newid, $uri[1]);
  }

  public function testChangeIdWithRelsInt() {
    $newid = 'new:id';
    $this->object['DC']->relationships->add(ISLANDORA_RELS_INT_URI, 'hasPage', 'some:otherpid');
    $this->object[$this->testDsid]->relationships->add(ISLANDORA_RELS_INT_URI, 'hasWoot', 'awesome:sauce');

    $this->object->id = $newid;
    $this->assertEquals($newid, $this->object->id);

    $dom = new DOMDocument();
    $dom->loadXml($this->object['RELS-INT']->content);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('rdf', RDF_URI);

    $results = $xpath->query('/rdf:RDF/rdf:Description/@rdf:about');
    $this->assertEquals(2, $results->length);

    foreach($results as $result) {
      $value = $result->value;
      $uri = explode('/', $value);
      $this->assertEquals($newid, $uri[1]);
    }
  }

  public function testDatastreamMutation() {
    $datastream = $this->existing_object[$this->testDsid2];

    $this->assertTrue($datastream instanceof FedoraDatastream, 'Datastream exists.');
    $this->assertTrue($this->object->ingestDatastream($datastream) !== FALSE, 'Datastream ingest succeeded.');
    $this->assertTrue($datastream instanceof NewFedoraDatastream, 'Datastream mutated on ingestion.');
  }
}
