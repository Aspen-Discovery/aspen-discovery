<?php
/**
 * @file
 * A set of test classes that test the FedoraApi.php file
 */

require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';
require_once 'TestHelpers.php';
require_once 'FedoraDate.php';

class FedoraApiIngestTest extends PHPUnit_Framework_TestCase {
  protected $pids = array();
  protected $files = array();

  protected function setUp() {
    $this->connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->serializer = new FedoraApiSerializer();

    $this->apim = new FedoraApiM($this->connection, $this->serializer);
    $this->apia = new FedoraApiA($this->connection, $this->serializer);
  }

  protected function tearDown() {
    if (isset($this->pids) && is_array($this->pids)) {
      while ($pid = array_pop($this->pids)) {
        try {
          $this->apim->purgeObject($pid);
        }
        catch (RepositoryException $e) {}
      }
    }

    if (isset($this->files) && is_array($this->files)) {
      while ($file = array_pop($this->files)) {
        unlink($file);
      }
    }
  }

  public function testUserAttributes() {
      $attributes = $this->apia->userAttributes();
      $this->assertArrayHasKey('role', $attributes);
      $this->assertArrayHasKey('fedoraRole', $attributes);
  }

  public function testDescribeRepository() {
    $describe = $this->apia->describeRepository();
    $this->assertArrayHasKey('repositoryName', $describe);
    $this->assertArrayHasKey('repositoryBaseURL', $describe);
    $this->assertArrayHasKey('repositoryPID', $describe);
    $this->assertArrayHasKey('PID-namespaceIdentifier', $describe['repositoryPID']);
    $this->assertArrayHasKey('PID-delimiter', $describe['repositoryPID']);
    $this->assertArrayHasKey('PID-sample', $describe['repositoryPID']);
    $this->assertArrayHasKey('retainPID', $describe['repositoryPID']);
    $this->assertArrayHasKey('repositoryOAI-identifier', $describe);
    $this->assertArrayHasKey('OAI-namespaceIdentifier', $describe['repositoryOAI-identifier']);
    $this->assertArrayHasKey('OAI-delimiter', $describe['repositoryOAI-identifier']);
    $this->assertArrayHasKey('OAI-sample', $describe['repositoryOAI-identifier']);
    $this->assertArrayHasKey('sampleSearch-URL', $describe);
    $this->assertArrayHasKey('sampleAccess-URL', $describe);
    $this->assertArrayHasKey('sampleOAI-URL', $describe);
    $this->assertArrayHasKey('adminEmail', $describe);
  }

  public function testIngestNoPid() {
    $pid = $this->apim->ingest();
    $this->pids[] = $pid;
    $results = $this->apia->findObjects('query', "pid=$pid");
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($pid, $results['results'][0]['pid']);
  }

  public function testIngestRandomPid() {
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $expected_pid = "$string1:$string2";
    $actual_pid = $this->apim->ingest(array('pid' => $expected_pid));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
    $results = $this->apia->findObjects('query', "pid=$expected_pid");
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($expected_pid, $results['results'][0]['pid']);
  }

  public function testIngestStringFoxml() {
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $expected_pid = "$string1:$string2";
    $expected_label = FedoraTestHelpers::randomString(15);
    $foxml = <<<FOXML
<?xml version="1.0" encoding="UTF-8"?>
<foxml:digitalObject
  xmlns:foxml="info:fedora/fedora-system:def/foxml#"
  xmlns="info:fedora/fedora-system:def/foxml#"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  VERSION="1.1"
  PID="$expected_pid"
  xsi:schemaLocation="info:fedora/fedora-system:def/foxml#
  http://www.fedora.info/definitions/1/0/foxml1-1.xsd">
  <foxml:objectProperties>
    <foxml:property NAME="info:fedora/fedora-system:def/model#state" VALUE="A"/>
    <foxml:property NAME="info:fedora/fedora-system:def/model#label" VALUE="$expected_label"/>
  </foxml:objectProperties>
</foxml:digitalObject>
FOXML;

    $actual_pid = $this->apim->ingest(array('string' => $foxml));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
    $results = $this->apia->findObjects('query', "pid=$expected_pid", NULL, array('pid', 'label'));
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($expected_pid, $results['results'][0]['pid']);
    $this->assertEquals($expected_label, $results['results'][0]['label']);
  }

  public function testIngestFileFoxml() {
    $file_name = tempnam(sys_get_temp_dir(),'fedora_fixture');
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $expected_pid = "$string1:$string2";
    $expected_label = FedoraTestHelpers::randomString(15);
    $foxml = <<<FOXML
<?xml version="1.0" encoding="UTF-8"?>
<foxml:digitalObject
  xmlns:foxml="info:fedora/fedora-system:def/foxml#"
  xmlns="info:fedora/fedora-system:def/foxml#"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  VERSION="1.1"
  PID="$expected_pid"
  xsi:schemaLocation="info:fedora/fedora-system:def/foxml#
  http://www.fedora.info/definitions/1/0/foxml1-1.xsd">
  <foxml:objectProperties>
    <foxml:property NAME="info:fedora/fedora-system:def/model#label" VALUE="$expected_label"/>
  </foxml:objectProperties>
</foxml:digitalObject>
FOXML;
    file_put_contents($file_name, $foxml);
    $this->files[] = $file_name;

    $actual_pid = $this->apim->ingest(array('file' => $file_name));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
    $results = $this->apia->findObjects('query', "pid=$expected_pid", NULL, array('pid', 'label'));
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($expected_pid, $results['results'][0]['pid']);
    $this->assertEquals($expected_label, $results['results'][0]['label']);
  }

  public function testIngestLabel() {
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $pid = "$string1:$string2";
    $expected_label = FedoraTestHelpers::randomString(15);
    $pid = $this->apim->ingest(array('pid' => $pid, 'label' => $expected_label));
    $this->pids[] = $pid;
    $results = $this->apia->findObjects('query', "pid=$pid", NULL, array('pid', 'label'));
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($pid, $results['results'][0]['pid']);
    $this->assertEquals($expected_label, $results['results'][0]['label']);
  }

  public function testIngestLogMessage() {
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $pid = "$string1:$string2";
    $expected_log_message = FedoraTestHelpers::randomString(15);
    $pid = $this->apim->ingest(array('pid' => $pid, 'logMessage' => $expected_log_message));
    $this->pids[] = $pid;

    // Check the audit trail.
    $xml = $this->apim->export($pid);
    $dom = new DomDocument();
    $dom->loadXml($xml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('audit', 'info:fedora/fedora-system:def/audit#');
    $result = $xpath->query('//audit:action[.="ingest"]/../audit:justification');
    $this->assertEquals(1, $result->length);
    $tag = $result->item(0);
    $this->assertEquals($expected_log_message, $tag->nodeValue);
  }

  public function testIngestNamespace() {
    $expected_namespace = FedoraTestHelpers::randomString(10);
    $pid = $this->apim->ingest(array('namespace' => $expected_namespace));
    $this->pids[] = $pid;
    $pid_parts = explode(':', $pid);
    $this->assertEquals($expected_namespace, $pid_parts[0]);
  }

  /**
   * @todo fix this test
   */
  public function testIngestOwnerId() {
    $this->markTestIncomplete();
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $pid = "$string1:$string2";
    $expected_owner = FedoraTestHelpers::randomString(15);
    $pid = $this->apim->ingest(array('pid' => $pid, 'ownerId' => $expected_owner));
    $this->pids[] = $pid;
    $results = $this->apia->findObjects('query', "pid=$pid", NULL, array('pid', 'ownerId'));
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($pid, $results['results'][0]['pid']);
    $this->assertEquals($expected_owner, $results['results'][0]['ownerId']);
  }

  /**
   * @todo finish this test
   * @todo we need some documents with different character encoding for this
   *   to work.
   */
  public function testIngestEncoding() {
    $this->markTestIncomplete();
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $expected_pid = "$string1:$string2";

    $actual_pid = $this->apim->ingest(array('string' => $foxml));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
  }

  /**
   * we need some files to ingest to test this
   */
  public function testIngestFormat() {
    $this->markTestIncomplete();
  }
}


class FedoraApiFindObjectsTest extends PHPUnit_Framework_TestCase {

  public $apim;
  public $apia;
  public $namespace;
  public $fixtures;
  public $display;
  public $pids;

  static $purge = TRUE;
  static $saved;

  protected function sanitizeObjectProfile($profile) {
    $profile['objDissIndexViewURL'] = parse_url($profile['objDissIndexViewURL'], PHP_URL_PATH);
    $profile['objItemIndexViewURL'] = parse_url($profile['objItemIndexViewURL'], PHP_URL_PATH);
    return $profile;
  }

  protected function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $serializer = new FedoraApiSerializer();

    $this->apim = new FedoraApiM($connection, $serializer);
    $this->apia = new FedoraApiA($connection, $serializer);

    if(self::$purge == FALSE) {
      $this->fixtures = self::$saved;
      return;
    }

    $this->namespace = FedoraTestHelpers::randomString(10);
    $pid1 = $this->namespace . ":" . FedoraTestHelpers::randomString(10);
    $pid2 = $this->namespace . ":" . FedoraTestHelpers::randomString(10);

    $this->fixtures = array();
    $this->pids = array();
    $this->pids[] = $pid1;
    $this->pids[] = $pid2;

    // Set up some arrays of data for the fixtures.
    $string = file_get_contents('tests/test_data/fixture1.xml');
    $string = preg_replace('/\%PID\%/', $pid1, $string);
    $pid = $this->apim->ingest(array('string' => $string));
    $urlpid = urlencode($pid);
    $this->fixtures[$pid] = array();
    $this->fixtures[$pid]['xml'] = $string;
    $this->fixtures[$pid]['findObjects'] = array( 'pid' => $pid1,
      'label' => 'label1', 'state' => 'I', 'ownerId' => 'owner1',
      'cDate' => '2012-03-12T15:22:37.847Z', 'dcmDate' => '2012-03-13T14:12:59.272Z',
      'title' => 'title1', 'creator' => 'creator1', 'subject' => 'subject1',
      'description' => 'description1', 'publisher' => 'publisher1',
      'contributor' => 'contributor1', 'date' => 'date1', 'type' => 'type1',
      'format' => 'format1',
      //'identifier' => $pid,
      'source' => 'source1',
      'language' => 'language1', 'relation' => 'relation1', 'coverage' => 'coverage1',
      'rights' => 'rights1',
    );
    $this->fixtures[$pid]['getObjectHistory'] = array('2012-03-13T14:12:59.272Z',
      '2012-03-13T17:40:29.057Z', '2012-03-13T18:09:25.425Z',
      '2012-03-13T19:15:07.529Z');
    $this->fixtures[$pid]['getObjectProfile'] = array(
      'objLabel' => $this->fixtures[$pid]['findObjects']['label'],
      'objOwnerId' => $this->fixtures[$pid]['findObjects']['ownerId'],
      'objModels' => array('info:fedora/fedora-system:FedoraObject-3.0',
        'info:fedora/testnamespace:test'),
      'objCreateDate' => $this->fixtures[$pid]['findObjects']['cDate'],
      'objDissIndexViewURL' => "http://localhost:8080/fedora/objects/$urlpid/methods/fedora-system%3A3/viewMethodIndex",
      'objItemIndexViewURL' => "http://localhost:8080/fedora/objects/$urlpid/methods/fedora-system%3A3/viewItemIndex",
      'objState' => $this->fixtures[$pid]['findObjects']['state'],
    );
    $this->fixtures[$pid]['listDatastreams'] = array(
      '2012-03-13T14:12:59.272Z' => array (
        'DC' => Array (
            'label' => 'Dublin Core Record for this object',
            'mimetype' => 'text/xml',
        ),
      ),
      '2012-03-13T17:40:29.057Z' => array (
        'DC' => Array(
                'label' => 'Dublin Core Record for this object',
                'mimetype' => 'text/xml',
            ),
        'fixture' => Array(
                'label' => 'label',
                'mimetype' => 'image/png',
            ),
      ),
      '2012-03-13T18:09:25.425Z' => Array(
        'DC' => Array(
                'label' => 'Dublin Core Record for this object',
                'mimetype' => 'text/xml',
            ),
        'fixture' => Array(
                'label' => 'label',
                'mimetype' => 'image/png',
            ),
      ),
      '2012-03-13T19:15:07.529Z' => Array(
        'DC' => Array(
                'label' => 'Dublin Core Record for this object',
                'mimetype' => 'text/xml',
            ),
        'fixture' => Array(
                'label' => 'label',
                'mimetype' => 'image/png',
            ),
        'RELS-EXT' => Array(
                'label' => 'Fedora Relationships Metadata',
                'mimetype' => 'text/xml',
            ),
      ),
    );
    $this->fixtures[$pid]['dsids'] = array(
      'DC' => array (
        'data' => array(
          'dsLabel' => 'Dublin Core Record for this object',
          'dsVersionID' => 'DC.1',
          'dsCreateDate' => '2012-03-13T14:12:59.272Z',
          'dsState' => 'A',
          'dsMIME' => 'text/xml',
          'dsFormatURI' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
          'dsControlGroup' => 'X',
          'dsSize' => '860',
          'dsVersionable' => 'true',
          'dsLocation' => "$pid+DC+DC.1",
          'dsLocationType' => '',
          'dsChecksumType' => 'DISABLED',
          'dsChecksum' => 'none',
        ),
        'count' => 1,
      ),
      'fixture' => array(
        'data' => array(
          'dsLabel' => 'label',
          'dsVersionID' => 'fixture.4',
          'dsCreateDate' => '2012-03-13T18:09:25.425Z',
          'dsState' => 'A',
          'dsMIME' => 'image/png',
          'dsFormatURI' => 'format',
          'dsControlGroup' => 'M',
          'dsSize' => '68524',
          'dsVersionable' => 'true',
          'dsLocation' => "$pid+fixture+fixture.4",
          'dsLocationType' => 'INTERNAL_ID',
          'dsChecksumType' => 'DISABLED',
          'dsChecksum' => 'none',
        ),
        'count' => 2,
      ),
      'RELS-EXT' => array(
        'data' => array(
          'dsLabel' => 'Fedora Relationships Metadata',
          'dsVersionID' => 'RELS-EXT.0',
          'dsCreateDate' => '2012-03-13T19:15:07.529Z',
          'dsState' => 'A',
          'dsMIME' => 'text/xml',
          'dsFormatURI' => '',
          'dsControlGroup' => 'X',
          'dsSize' => '540',
          'dsVersionable' => 'true',
          'dsLocation' => "$pid+RELS-EXT+RELS-EXT.0",
          'dsLocationType' => 'INTERNAL_ID',
          'dsChecksumType' => 'DISABLED',
          'dsChecksum' => 'none',
        ),
        'count' => 1,
      ),
    );

    // second fixture
    $string = file_get_contents('tests/test_data/fixture2.xml');
    $pid = $this->apim->ingest(array('pid' => $pid2, 'string' => $string));
    $urlpid = urlencode($pid);
    $this->fixtures[$pid] = array();
    $this->fixtures[$pid]['xml'] = $string;
    $this->fixtures[$pid]['findObjects'] = array(
      'pid' => $pid,
      'label' => 'label2',
      'state' => 'A',
      'ownerId' => 'owner2',
      'cDate' => '2000-03-12T15:22:37.847Z',
      'dcmDate' => '2010-03-13T14:12:59.272Z',
      'title' => 'title2',
      'creator' => 'creator2',
      'subject' => 'subject2',
      'description' => 'description2',
      'publisher' => 'publisher2',
      'contributor' => 'contributor2',
      'date' => 'date2',
      'type' => 'type2',
      'format' => 'format2',
      //'identifier' => array('identifier2', $pid),
      'source' => 'source2',
      'language' => 'language2',
      'relation' => 'relation2',
      'coverage' => 'coverage2',
      'rights' => 'rights2',
    );
    $this->fixtures[$pid]['getObjectHistory'] = array('2010-03-13T14:12:59.272Z');
    $this->fixtures[$pid]['getObjectProfile'] = array(
      'objLabel' => $this->fixtures[$pid]['findObjects']['label'],
      'objOwnerId' => $this->fixtures[$pid]['findObjects']['ownerId'],
      'objModels' => array('info:fedora/fedora-system:FedoraObject-3.0'),
      'objCreateDate' => $this->fixtures[$pid]['findObjects']['cDate'],
      'objDissIndexViewURL' => "http://localhost:8080/fedora/objects/$urlpid/methods/fedora-system%3A3/viewMethodIndex",
      'objItemIndexViewURL' => "http://localhost:8080/fedora/objects/$urlpid/methods/fedora-system%3A3/viewItemIndex",
      'objState' => $this->fixtures[$pid]['findObjects']['state'],
    );
    $this->fixtures[$pid]['listDatastreams'] = array(
      '2010-03-13T14:12:59.272Z' => array (
        'DC' => Array (
            'label' => 'Dublin Core Record for this object',
            'mimetype' => 'text/xml',
        ),
      ),
    );
    $this->fixtures[$pid]['dsids'] = array(
      'DC' => array(
        'data' => array(
          'dsLabel' => 'Dublin Core Record for this object',
          'dsVersionID' => 'DC.1',
          'dsCreateDate' => '2010-03-13T14:12:59.272Z',
          'dsState' => 'A',
          'dsMIME' => 'text/xml',
          'dsFormatURI' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
          'dsControlGroup' => 'X',
          'dsSize' => '905',
          'dsVersionable' => 'true',
          'dsLocation' => "$pid+DC+DC.1",
          'dsLocationType' => '',
          'dsChecksumType' => 'DISABLED',
          'dsChecksum' => 'none',
        ),
        'count' => 1,
      ),
    );

    $this->display = array( 'pid', 'label', 'state', 'ownerId', 'cDate', 'mDate',
      'dcmDate', 'title', 'creator', 'subject', 'description', 'publisher',
      'contributor', 'date', 'type', 'format', 'identifier', 'source',
      'language', 'relation', 'coverage', 'rights'
    );
  }

  protected function tearDown()
  {
    if(self::$purge) {
      foreach ($this->fixtures as $key => $value) {
        try {
          $this->apim->purgeObject($key);
        }
        catch (RepositoryException $e) {}
      }
    }
    else {
      self::$saved = $this->fixtures;
    }
  }

  public function testDescribeRepository() {
    $describe = $this->apia->describeRepository();
    $this->assertArrayHasKey('repositoryName', $describe);
    $this->assertArrayHasKey('repositoryBaseURL', $describe);
    $this->assertArrayHasKey('repositoryPID', $describe);
    $this->assertArrayHasKey('PID-namespaceIdentifier', $describe['repositoryPID']);
    $this->assertArrayHasKey('PID-delimiter', $describe['repositoryPID']);
    $this->assertArrayHasKey('PID-sample', $describe['repositoryPID']);
    $this->assertArrayHasKey('retainPID', $describe['repositoryPID']);
    $this->assertArrayHasKey('repositoryOAI-identifier', $describe);
    $this->assertArrayHasKey('OAI-namespaceIdentifier', $describe['repositoryOAI-identifier']);
    $this->assertArrayHasKey('OAI-delimiter', $describe['repositoryOAI-identifier']);
    $this->assertArrayHasKey('OAI-sample', $describe['repositoryOAI-identifier']);
    $this->assertArrayHasKey('sampleSearch-URL', $describe);
    $this->assertArrayHasKey('sampleAccess-URL', $describe);
    $this->assertArrayHasKey('sampleOAI-URL', $describe);
    $this->assertArrayHasKey('adminEmail', $describe);
  }

  function testFindObjectsTerms() {
    // Test all of the possible values.
    $namespace = $this->namespace;
    $result = $this->apia->findObjects('terms', "{$namespace}:*", 1, $this->display);
    $this->assertEquals(1,count($result['results']));
    $pid = $result['results'][0]['pid'];
    // Make sure we have the modified date key. But we unset it because we can't
    // test it, since it changes every time.
    $this->assertArrayHasKey('mDate', $result['results'][0]);
    unset($result['results'][0]['mDate']);
    unset($result['results'][0]['identifier']);
    $this->assertEquals($this->fixtures[$pid]['findObjects'],$result['results'][0]);

    // Test that we have a session key
    $this->assertArrayHasKey('session', $result);
    $this->assertArrayHasKey('token', $result['session']);
    self::$purge = FALSE;
    return $result['session']['token'];
  }

  /**
   * @depends testFindObjectsTerms
   */
  function testFindObjectsTermsResume($token) {
    self::$purge = TRUE;
    $result = $this->apia->resumeFindObjects($token);
    $this->assertEquals(1,count($result['results']));
    $this->assertArrayNotHasKey('session', $result);
    $pid = $result['results'][0]['pid'];
    // Make sure we have the modified date key. But we unset it because we can't
    // test it, since it changes every time.
    $this->assertArrayHasKey('mDate', $result['results'][0]);
    unset($result['results'][0]['mDate']);
    unset($result['results'][0]['identifier']);
    $this->assertEquals($this->fixtures[$pid]['findObjects'],$result['results'][0]);
  }

  function testFindObjectsQueryWildcard() {
    $namespace = $this->namespace;
    $result = $this->apia->findObjects('query', "pid~{$namespace}:*", NULL, $this->display);
    $this->assertEquals(2,count($result['results']));
    foreach($result['results'] as $results) {
      $this->assertArrayHasKey('mDate', $results);
      unset($results['mDate']);
      unset($results['identifier']);
      $this->assertEquals($this->fixtures[$results['pid']]['findObjects'], $results);
    }
  }

  function testFindObjectsQueryEquals() {
    $display = array_diff($this->display, array('mDate'));
    foreach($this->fixtures as $pid => $fixtures) {
      $data = $fixtures['findObjects'];
      foreach($data as $key => $array) {
        if(!is_array($array)) {
          $array = array($array);
        }
        foreach($array as $value) {
          switch($key) {
            case 'cDate':
            case 'mDate':
            case 'dcmDate':
              $query = "pid=$pid ${key}=$value";
              break;
            default:
              $query = "pid=$pid ${key}~$value";
          }
          $result = $this->apia->findObjects('query', $query, NULL, $display);
          $this->assertEquals(1,count($result['results']));
          unset($result['results'][0]['identifier']);
          $this->assertEquals($this->fixtures[$pid]['findObjects'], $result['results'][0]);
        }
      }
    }
  }

  function testGetDatastreamDissemination() {
    $expected = file_get_contents('tests/test_data/fixture1_fixture_newest.png');
    $actual = $this->apia->getDatastreamDissemination($this->pids[0], 'fixture');
    $this->assertEquals($expected, $actual);
  }

  function testGetDatastreamDisseminationAsOfDate() {
    $expected = file_get_contents('tests/test_data/fixture1_fixture_oldest.png');
    $actual = $this->apia->getDatastreamDissemination($this->pids[0], 'fixture', '2012-03-13T17:40:29.057Z');
    $this->assertEquals($expected, $actual);
  }

  function testGetDatastreamDisseminationToFile() {
    $expected = file_get_contents('tests/test_data/fixture1_fixture_newest.png');
    $file = tempnam(sys_get_temp_dir(), "test");
    $return = $this->apia->getDatastreamDissemination($this->pids[0], 'fixture', NULL, $file);
    $this->assertTrue($return);
    $this->assertEquals($expected, file_get_contents($file));
    unlink($file);
  }

  function testGetDissemination() {
    $this->markTestIncomplete();
  }

  function testGetObjectHistory() {
    foreach ($this->fixtures as $pid => $fixture) {
      $actual = $this->apia->getObjectHistory($pid);
      $this->assertEquals($fixture['getObjectHistory'], $actual);
    }
  }

  // This one is interesting because the flattendocument function doesn't
  // work on it. So we have to handparse it. So we test to make sure its okay.
  // @todo Test the second arguement to this
  function testGetObjectProfile() {
    foreach ($this->fixtures as $pid => $fixture) {
      $expected = $fixture['getObjectProfile'];
      $actual = $this->apia->getObjectProfile($pid);
      $this->assertArrayHasKey('objLastModDate', $actual);
      unset($actual['objLastModDate']);
      // The content models come back in an undefined order, so we need
      // to test them individually.
      $this->assertArrayHasKey('objModels', $actual);
      $this->assertEquals(count($expected['objModels']), count($actual['objModels']));
      foreach($actual['objModels'] as $model) {
        $this->assertTrue(in_array($model, $actual['objModels']));
      }
      unset($actual['objModels']);
      unset($expected['objModels']);
      $expected = $this->sanitizeObjectProfile($expected);
      $actual = $this->sanitizeObjectProfile($actual);
      $this->assertEquals($expected, $actual);
    }
  }

  function testListDatastreams() {
    foreach ($this->fixtures as $pid => $fixture) {
      foreach($fixture['getObjectHistory'] as $datetime) {
        $actual = $this->apia->listDatastreams($pid, $datetime);
        $this->assertEquals($fixture['listDatastreams'][$datetime], $actual);
      }
      $revisions = count($fixture['getObjectHistory']);
      $date = $fixture['getObjectHistory'][$revisions-1];
      $acutal = $this->apia->listDatastreams($pid);
      $this->assertEquals($fixture['listDatastreams'][$date], $actual);
    }
  }

  function testListMethods() {
    $this->markTestIncomplete();
  }

  function testExport() {
    $this->markTestIncomplete();
    // One would think this would work, but there are a few problems
    // a number of tags change on ingest, so we need to do a more in
    // depth comparison.
    foreach ($this->fixtures as $pid => $fixture) {
      $actual = $this->apim->export($pid, array('context' => 'archive'));
      $dom = array();
      $dom[] = new DOMDocument();
      $dom[] = new DOMDocument();
      $dom[0]->loadXML($actual);
      $dom[1]->loadXML($fixture['xml']);

      $this->assertEquals($dom[1], $dom[0]);
    }
  }

  function testGetDatastream() {
    foreach ($this->fixtures as $pid => $fixture) {
      $listDatastreams = $fixture['listDatastreams'];

      // Do a test with the data we have.
      foreach($listDatastreams as $time => $datastreams) {
        foreach($datastreams as $dsid => $data) {
          $actual = $this->apim->getDatastream($pid, $dsid, array('asOfDateTime' => $time));
          $this->assertEquals($data['label'], $actual['dsLabel']);
          $this->assertEquals($data['mimetype'], $actual['dsMIME']);
          $this->assertArrayHasKey('dsVersionID', $actual);
          $this->assertArrayHasKey('dsCreateDate', $actual);
          $this->assertArrayHasKey('dsState', $actual);
          $this->assertArrayHasKey('dsMIME', $actual);
          $this->assertArrayHasKey('dsFormatURI', $actual);
          $this->assertArrayHasKey('dsControlGroup', $actual);
          $this->assertArrayHasKey('dsSize', $actual);
          $this->assertArrayHasKey('dsVersionable', $actual);
          $this->assertArrayHasKey('dsInfoType', $actual);
          $this->assertArrayHasKey('dsLocation', $actual);
          $this->assertArrayHasKey('dsLocationType', $actual);
          $this->assertArrayHasKey('dsChecksumType', $actual);
          $this->assertArrayHasKey('dsChecksum', $actual);
        }
      }

      // Test with the more detailed current data.
      foreach($fixture['dsids'] as $dsid => $data) {
        $actual = $this->apim->getDatastream($pid, $dsid);
        unset($actual['dsInfoType']);
        $this->assertEquals($data['data'], $actual);
      }
    }
  }

  function testGetDatastreamHistory() {
    foreach ($this->fixtures as $pid => $fixture) {
      foreach ($fixture['dsids'] as $dsid => $data) {
        $actual = $this->apim->getDatastreamHistory($pid, $dsid);
        // we should at least make sure we get the right count here
        $this->assertEquals($data['count'],count($actual));
        unset($actual[0]['dsInfoType']);
        $this->assertEquals($data['data'], $actual[0]);
      }
    }
  }

  function testGetNextPid() {
    $pid = $this->apim->getNextPid();
    $this->assertInternalType('string', $pid);

    $namespace = FedoraTestHelpers::randomString(10);
    $pids = $this->apim->getNextPid($namespace, 5);
    $this->assertInternalType('array', $pids);
    $this->assertEquals(5, count($pids));

    foreach ($pids as $pid) {
      $pid = explode(':', $pid);
      $this->assertEquals($namespace, $pid[0]);
    }
  }

  /**
   * @depends testGetObjectProfile
   */
  function testModifyObjectLabel() {
    foreach ($this->fixtures as $pid => $fixture) {
      $this->apim->modifyObject($pid, array('label' => 'wallawalla'));
      $expected = $fixture['getObjectProfile'];
      $actual = $this->apia->getObjectProfile($pid);
      $this->assertEquals('wallawalla', $actual['objLabel']);
      unset($actual['objLabel']);
      unset($actual['objLastModDate']);
      unset($actual['objModels']);
      unset($expected['objModels']);
      unset($expected['objLabel']);
      $expected = $this->sanitizeObjectProfile($expected);
      $actual = $this->sanitizeObjectProfile($actual);
      $this->assertEquals($expected, $actual);
    }
  }

  /**
   * @depends testGetObjectProfile
   */
  function testModifyObjectOwnerId() {
    foreach ($this->fixtures as $pid => $fixture) {
      $this->apim->modifyObject($pid, array('ownerId' => 'wallawalla'));
      $expected = $fixture['getObjectProfile'];
      $actual = $this->apia->getObjectProfile($pid);
      $this->assertEquals('wallawalla', $actual['objOwnerId']);
      unset($actual['objOwnerId']);
      unset($actual['objLastModDate']);
      unset($actual['objModels']);
      unset($expected['objModels']);
      unset($expected['objOwnerId']);
      $expected = $this->sanitizeObjectProfile($expected);
      $actual = $this->sanitizeObjectProfile($actual);
      $this->assertEquals($expected, $actual);
    }
  }

  /**
   * @depends testGetObjectProfile
   */
  function testModifyObjectState() {
    foreach ($this->fixtures as $pid => $fixture) {
      foreach (array('D', 'I', 'A') as $state) {
        $this->apim->modifyObject($pid, array('state' => $state));
        $expected = $fixture['getObjectProfile'];
        $actual = $this->apia->getObjectProfile($pid);
        $this->assertEquals($state, $actual['objState']);
        unset($actual['objState']);
        unset($actual['objLastModDate']);
        unset($actual['objModels']);
        unset($expected['objModels']);
        unset($expected['objState']);
        $expected = $this->sanitizeObjectProfile($expected);
        $actual = $this->sanitizeObjectProfile($actual);
        $this->assertEquals($expected, $actual);
      }
    }
  }

  /**
   * @depends testGetDatastream
   */
  function testModifyDatastreamLabel() {
    foreach ($this->fixtures as $pid => $fixture) {
      foreach($fixture['dsids'] as $dsid => $data) {
        $this->apim->modifyDatastream($pid, $dsid, array('dsLabel' => 'testtesttest'));
        $actual = $this->apim->getDatastream($pid, $dsid);
        $expected = $data['data'];
        $this->assertEquals('testtesttest', $actual['dsLabel']);
        foreach(array('dsLabel', 'dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType') as $unset) {
          unset($actual[$unset]);
          unset($expected[$unset]);
        }
        $this->assertEquals($expected, $actual);
      }
    }
  }

  /**
   * @depends testGetDatastream
   */
  function testModifyDatastreamVersionable() {
    foreach ($this->fixtures as $pid => $fixture) {
      foreach($fixture['dsids'] as $dsid => $data) {
        foreach(array(FALSE, TRUE) as $versionable) {
          $this->apim->modifyDatastream($pid, $dsid, array('versionable' => $versionable));
          $actual = $this->apim->getDatastream($pid, $dsid);
          $expected = $data['data'];
          $this->assertEquals($versionable ? 'true' : 'false', $actual['dsVersionable']);
          foreach(array('dsVersionable', 'dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType') as $unset) {
            unset($actual[$unset]);
            unset($expected[$unset]);
          }
          $this->assertEquals($expected, $actual);
        }
      }
    }
  }

  /**
   * @depends testGetDatastream
   */
  function testModifyDatastreamVersionableOldVersions() {
    $this->markTestIncomplete();
    $pid = $this->pids[0];
    $dsid = 'fixture';

    $before_history = $this->apim->getDatastreamHistory($pid, $dsid);
    print_r($before_history);
    $this->apim->modifyDatastream($pid, $dsid, array('versionable' => FALSE));
    $after_history = $this->apim->getDatastreamHistory($pid, $dsid);
    print_r($after_history);
    $this->apim->modifyDatastream($pid, $dsid, array('dsLabel' => 'goo'));
    $after_history = $this->apim->getDatastreamHistory($pid, $dsid);
    print_r($after_history);
  }

  /**
   * @depends testGetDatastream
   */
  function testModifyDatastreamState() {
    foreach ($this->fixtures as $pid => $fixture) {
      foreach($fixture['dsids'] as $dsid => $data) {
        foreach(array('I', 'D', 'A') as $state) {
          $this->apim->modifyDatastream($pid, $dsid, array('dsState' => $state));
          $actual = $this->apim->getDatastream($pid, $dsid);
          $expected = $data['data'];
          $this->assertEquals($state, $actual['dsState']);
          foreach(array('dsState', 'dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType') as $unset) {
            unset($actual[$unset]);
            unset($expected[$unset]);
          }
          $this->assertEquals($expected, $actual);
        }
      }
    }
  }

  /**
   * @depends testGetDatastream
   */
  function testModifyDatastreamChecksum() {
    foreach ($this->fixtures as $pid => $fixture) {
      foreach($fixture['dsids'] as $dsid => $data) {
        foreach(array('MD5', 'SHA-1', 'SHA-256', 'SHA-384', 'SHA-512', 'DISABLED') as $type) {
          $this->apim->modifyDatastream($pid, $dsid, array('checksumType' => $type));
          $actual = $this->apim->getDatastream($pid, $dsid);
          $expected = $data['data'];
          $this->assertEquals($type, $actual['dsChecksumType']);
          foreach(array('dsChecksumType', 'dsChecksum', 'dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType') as $unset) {
            unset($actual[$unset]);
            unset($expected[$unset]);
          }
          $this->assertEquals($expected, $actual);

          if($actual['dsControlGroup'] == "M") {
            $dscontent = $this->apia->getDatastreamDissemination($pid, $dsid);
            switch($type) {
              case 'MD5':
                $hash = hash('md5', $dscontent);
                break;
              case 'SHA-1':
                $hash = hash('sha1', $dscontent);
                break;
              case 'SHA-256':
                $hash = hash('sha256', $dscontent);
                break;
              case 'SHA-384':
                $hash = hash('sha384', $dscontent);
                break;
              case 'SHA-512':
                $hash = hash('sha512', $dscontent);
                break;
              case 'DISABLED':
                $hash = 'none';
                break;
            }

            $this->apim->modifyDatastream($pid, $dsid, array('checksumType' => $type, 'checksum' => $hash));
            $actual = $this->apim->getDatastream($pid, $dsid);
            $this->assertEquals($hash, $actual['dsChecksum']);
          }
        }
      }
    }
  }

  /**
   * @depends testGetDatastream
   */
  function testModifyDatastreamFormatURI() {
    foreach ($this->fixtures as $pid => $fixture) {
      foreach($fixture['dsids'] as $dsid => $data) {
        $this->apim->modifyDatastream($pid, $dsid, array('formatURI' => 'testtesttest'));
        $actual = $this->apim->getDatastream($pid, $dsid);
        $expected = $data['data'];
        $this->assertEquals('testtesttest', $actual['dsFormatURI']);
        foreach(array('dsFormatURI', 'dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType') as $unset) {
          unset($actual[$unset]);
          unset($expected[$unset]);
        }
        $this->assertEquals($expected, $actual);
      }
    }
  }

  /**
   * @depends testGetDatastream
   */
  function testModifyDatastreamMimeType() {
    foreach ($this->fixtures as $pid => $fixture) {
      foreach($fixture['dsids'] as $dsid => $data) {
        $this->apim->modifyDatastream($pid, $dsid, array('mimeType' => 'application/super-fucking-cool'));
        $actual = $this->apim->getDatastream($pid, $dsid);
        $expected = $data['data'];
        $this->assertEquals('application/super-fucking-cool', $actual['dsMIME']);
        foreach(array('dsMIME', 'dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType') as $unset) {
          unset($actual[$unset]);
          unset($expected[$unset]);
        }
        $this->assertEquals($expected, $actual);
      }
    }
  }

  /**
   * @depends testGetDatastream
   */
  function testModifyDatastreamAltIds() {
    foreach ($this->fixtures as $pid => $fixture) {
      foreach($fixture['dsids'] as $dsid => $data) {
        $this->apim->modifyDatastream($pid, $dsid, array('altIDs' => "one two three"));
        $actual = $this->apim->getDatastream($pid, $dsid);
        $expected = $data['data'];
        $this->assertArrayHasKey('dsAltID', $actual);
        $this->assertEquals(array('one', 'two', 'three'), $actual['dsAltID']);
        unset($actual['dsAltID']);
        foreach(array('dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType') as $unset) {
          unset($actual[$unset]);
          unset($expected[$unset]);
        }
        $this->assertEquals($expected, $actual);
      }
    }
  }
}
