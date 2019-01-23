<?php

require_once 'Datastream.php';
require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'TestHelpers.php';

class CopyDatastreamTest extends PHPUnit_Framework_TestCase {

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


    $string3 = FedoraTestHelpers::randomString(10);
    $string4 = FedoraTestHelpers::randomString(10);
    $this->testPid2 = "$string3:$string4";
    $this->new_object = $this->repository->constructObject();
    $this->new_object->id = $this->testPid2;
    $this->new_object->label = 'Sommat';
    $this->new_object->owner = 'us';

    // create a DSID
    $this->testDsid = FedoraTestHelpers::randomCharString(10);
    $this->testDsContents = '<test><xml/></test>';
    $this->api->m->addDatastream($this->testPid, $this->testDsid, 'string', $this->testDsContents, array('controlGroup' => 'M'));
    $this->object = new FedoraObject($this->testPid, $this->repository);
    $this->ds = new FedoraDatastream($this->testDsid, $this->object, $this->repository);
    $this->ds->relationships->add('http://example.org/uri#', 'test-uri', 'http://example.org/a/page.html');
    $this->ds->relationships->add('http://example.org/uri#', 'test-literal', 'some_kinda_literal', 1);

    $temp_dir = sys_get_temp_dir();
    $this->tempfile1 = tempnam($temp_dir, 'test');
    $this->tempfile2 = tempnam($temp_dir, 'test');
  }

  protected function tearDown() {
    $this->api->m->purgeObject($this->testPid);
    $this->api->m->purgeObject($this->testPid2);
    unlink($this->tempfile1);
    unlink($this->tempfile2);
  }

  /**
   * Copy an existing datastream between existing FedoraObjects.
   */
  public function testExistingToExistingIngest() {
    $object = $this->repository->ingestObject($this->new_object);
    $copied_datastream = $object->ingestDatastream($this->object[$this->testDsid]);

    $this->assertNotEquals($this->object->id, $copied_datastream->parent->id, 'Datastream exists on new object.');

    $this->scanProperties($this->object[$this->testDsid], $object[$this->testDsid]);
  }

  /**
   * Copy an existing datastream to a NewFedoraObject.
   *
   * Base case; copy an existing datastream into a NewFedoraObject, and ingest
   * the NewFedoraObject.
   */
  public function testBaseIngest() {
    $this->assertTrue($this->new_object->ingestDatastream($this->object[$this->testDsid]), 'Create datastream entry on new object');
    $object = $this->repository->ingestObject($this->new_object);

    $this->scanProperties($this->object[$this->testDsid], $object[$this->testDsid]);
  }

  /**
   * Copy an existing datastream, triggering copy-on-write mechanism.
   *
   * Test the ingest of an existing datastream, introducing a change (setting
   * the label) which should force the "copy-on-write" mechanism to fire.
   */
  public function testCopiedIngest() {
    $datastream = $this->object[$this->testDsid];
    $this->assertTrue($datastream instanceof FedoraDatastream, 'Datastream initially exists.');
    $this->assertTrue($this->new_object->ingestDatastream($datastream), 'Datastream ingested into new object');
    $this->assertTrue($datastream instanceof NewFedoraDatastream, 'Datastream was copied into a NewFedoraDatastream.');

    $new_label = strrev($this->new_object[$this->testDsid]->label);
    $new_label .= $new_label;

    $this->new_object[$this->testDsid]->label = $new_label;
    $this->assertEquals($datastream->label, $new_label, 'New label accessible through object.');
    $object = $this->repository->ingestObject($this->new_object);

    $this->scanProperties($this->object[$this->testDsid], $object[$this->testDsid], array(
      'label' => $new_label,
    ));
  }

  /**
   * Compare all properties on the given datastreams.
   *
   * Compares two instances of a datastream for similarity.
   *
   * @param AbstractDatastream $alpha
   *   A datastream to compare.
   * @param AbstractDatastream $bravo
   *   Another datastream, possibly with the changes listed in $changed
   *   differentiating it from $alpha.
   * @param array $changed
   *   An array of ways in which the properties of $bravo vary from $alpha,
   *   mapping from properties names to the values on $bravo. Changes to the
   *   'content' property should reference a filename (not $this->tempfile1).
   *   The 'relationship' property may be mapped to NULL if there are changes,
   *   otherwise, relationships between the datastreams are assumed to be
   *   unique.
   */
  protected function scanProperties(AbstractDatastream $alpha, AbstractDatastream $bravo, array $changed = array()) {
    $this->assertNotSame($alpha, $bravo, 'Datastreams being compared are not the same object.');

    $properties = array(
      'id',
      'label',
      'controlGroup',
      'versionable',
      'state',
      'mimetype',
      'format',
      // Size is related to the content...
      //'size',
      'checksum',
      'checksumType',
      // createdDate is not copied.
      //'createdDate',
      // Content is tested separately, using getContent().
      //'content',
      // location is different by definition, since we require the objects
      // to be different, and the location contains the object's PID.
      //'location',
      // Log message is not expected to be the same...
      //'logMessage',
    );

    $similar_properties = array_diff($properties, array_keys($changed));

    foreach ($similar_properties as $property) {
      $this->assertEquals($alpha->$property, $bravo->$property, "'$property' is equal.");
    }
    foreach ($changed as $property => $value) {
      $message = "New value of '$property' is present.";
      if ($property == 'content') {
        $bravo->getContent($this->tempfile1);
        $this->assertFileEquals($this->tempfile1, $value, $message);
      }
      else {
        $this->assertEquals($bravo->$property, $value, $message);
      }
    }

    $gettable_control_groups = array('X', 'M');
    if (!array_key_exists('content', $changed)
      && in_array($alpha->controlGroup, $gettable_control_groups)
      && in_array($bravo->controlGroup, $gettable_control_groups)) {

      $alpha->getContent($this->tempfile1);
      $bravo->getContent($this->tempfile2);
      $this->assertFileEquals($this->tempfile1, $this->tempfile2, 'Datastream contents are equal.');
    }
    elseif (!array_key_exists('url', $changed)
      && in_array($alpha->controlGroup, $gettable_control_groups)
      && in_array($bravo->controlGroup, $gettable_control_groups)) {
      $this->assertEquals($alpha->url, $bravo->url, 'Datastream URLs are equal.');
    }

    if (!array_key_exists('relationships', $changed)) {
      foreach ($alpha->relationships->get() as $relationship) {
        extract($relationship);
        $rels = $bravo->relationships->get($predicate['namespace'],
          $predicate['value'], $object['value'], $object['literal']);
        $this->assertTrue(empty($rels), 'Unique relationships.');
      }
    }
  }
}

