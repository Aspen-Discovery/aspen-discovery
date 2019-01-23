<?php
require_once "FedoraRelationships.php";
require_once "RepositoryConnection.php";
require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'TestHelpers.php';

class FedoraRelationshipsExternalTest extends PHPUnit_Framework_TestCase {

  function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $this->repository = new FedoraRepository($this->api, $cache);
    $this->object = $this->repository->constructObject('test:awesome');

    $this->object->relationships->add(ISLANDORA_RELS_EXT_URI, 'hasAwesomeness', 'jonathan:green');
    $this->object->relationships->add(FEDORA_MODEL_URI, 'hasModel', 'islandora:model');
    $this->object->relationships->add(ISLANDORA_RELS_EXT_URI, 'isPage', '22', TRUE);
    $this->object->relationships->add(FEDORA_RELS_EXT_URI, 'isMemberOfCollection', 'theawesomecollection:awesome');
    $this->object->relationships->add(FEDORA_MODEL_URI, 'hasModel', 'islandora:woot');

    $this->object->relationships->add(ISLANDORA_RELS_EXT_URI, 'has_mixed_quotes', '\'xpath"escaping"realy_sucks\'', TRUE);
    $this->object->relationships->add(ISLANDORA_RELS_EXT_URI, 'has_single_quotes', "xpath'escaping'sucks", TRUE);
    $this->object->relationships->add(ISLANDORA_RELS_EXT_URI, 'has_double_quotes', 'xpath"escaping"sucks_doubly', TRUE);

    $this->repository->ingestObject($this->object);
  }

  function tearDown() {
    $this->repository->purgeObject($this->object->id);
  }

  /**
   * Tests that xpaths are escaped for literals.
   */
  function testXpathEscaping() {

    $has_mixed_quotes_rels = $this->object->relationships->get(ISLANDORA_RELS_EXT_URI, 'has_mixed_quotes', '\'xpath"escaping"realy_sucks\'', TRUE);
    $has_single_quotes_rels = $this->object->relationships->get(ISLANDORA_RELS_EXT_URI, 'has_single_quotes', "xpath'escaping'sucks", TRUE);
    $has_double_quotes_rels = $this->object->relationships->get(ISLANDORA_RELS_EXT_URI, 'has_double_quotes', 'xpath"escaping"sucks_doubly', TRUE);

    $this->assertEquals('\'xpath"escaping"realy_sucks\'', $has_mixed_quotes_rels[0]['object']['value']);
    $this->assertEquals("xpath'escaping'sucks", $has_single_quotes_rels[0]['object']['value']);
    $this->assertEquals('xpath"escaping"sucks_doubly', $has_double_quotes_rels[0]['object']['value']);

  }

  function testGetAll() {
    $relationships = $this->object->relationships->get();
    $this->assertEquals(8, count($relationships));
    $this->assertEquals('hasAwesomeness', $relationships[0]['predicate']['value']);
    $this->assertEquals('jonathan:green', $relationships[0]['object']['value']);
    $this->assertEquals('hasModel', $relationships[1]['predicate']['value']);
    $this->assertEquals('islandora:model', $relationships[1]['object']['value']);
    $this->assertEquals('isPage', $relationships[2]['predicate']['value']);
    $this->assertEquals('22', $relationships[2]['object']['value']);
    $this->assertTrue($relationships[2]['object']['literal']);
    $this->assertEquals('isMemberOfCollection', $relationships[3]['predicate']['value']);
    $this->assertEquals('theawesomecollection:awesome', $relationships[3]['object']['value']);
  }

  function testGetOne() {
    $rels = $this->object->relationships->get(FEDORA_MODEL_URI, 'hasModel');
    $this->assertEquals(2, count($rels));
    $this->assertEquals('hasModel', $rels[0]['predicate']['value']);
    $this->assertEquals('islandora:model', $rels[0]['object']['value']);
    $this->assertEquals('hasModel', $rels[1]['predicate']['value']);
    $this->assertEquals('islandora:woot', $rels[1]['object']['value']);
  }

  function testRemovePredicate() {
    $this->object->relationships->remove(FEDORA_MODEL_URI, 'hasModel');
    $rels = $this->object->relationships->get(FEDORA_MODEL_URI, 'hasModel');
    $this->assertEquals(0, count($rels));
  }

  function testRemoveSpecificPredicate() {
    $this->object->relationships->remove(FEDORA_MODEL_URI, 'hasModel', 'islandora:model');
    $rels = $this->object->relationships->get(FEDORA_MODEL_URI, 'hasModel');
    $this->assertEquals(1, count($rels));
    $this->assertEquals('hasModel', $rels[0]['predicate']['value']);
    $this->assertEquals('islandora:woot', $rels[0]['object']['value']);
  }

  function testRemoveObject() {
    $this->object->relationships->remove(NULL, NULL, 'islandora:model');
    $rels = $this->object->relationships->get(FEDORA_MODEL_URI, 'hasModel');
    $this->assertEquals(1, count($rels));
    $this->assertEquals('hasModel', $rels[0]['predicate']['value']);
    $this->assertEquals('islandora:woot', $rels[0]['object']['value']);
  }

  function testPurge() {
    $this->assertTrue($this->object->purgeDatastream('RELS-EXT'));
  }

  function testConvertRelsExtToManaged() {
    $content = $this->object['RELS-EXT']->content;
    $this->assertTrue($this->object->purgeDatastream('RELS-EXT'));
    $model = $this->object->relationships->get(FEDORA_MODEL_URI, 'hasModel');
    $this->assertTrue(empty($model));
    $model = $this->object->models;
    $this->assertEquals(1, count($model));
    $ds = $this->object->constructDatastream('RELS-EXT', 'M');
    $ds->content = $content;
    $this->object->ingestDatastream($ds);
    $this->assertFalse(empty($this->object['RELS-EXT']));
    $this->assertTrue($this->object['RELS-EXT']->controlGroup == 'M');
  }
}
