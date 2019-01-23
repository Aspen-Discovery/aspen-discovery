<?php

require_once 'Datastream.php';
require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'TestHelpers.php';

class DatastreamTest extends PHPUnit_Framework_TestCase {

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

    // create a DSID
    $this->testDsid = FedoraTestHelpers::randomCharString(10);
    $this->testDsidR = FedoraTestHelpers::randomCharString(10);
    $this->testDsidE = FedoraTestHelpers::randomCharString(10);
    $this->testDsidX = FedoraTestHelpers::randomCharString(10);
    $this->testDsContents = '<test><xml/></test>';
    $this->api->m->addDatastream($this->testPid, $this->testDsid, 'string', $this->testDsContents, array('controlGroup' => 'M'));
    $this->api->m->addDatastream($this->testPid, $this->testDsidR, 'url', 'http://test.com.fop', array('controlGroup' => 'R'));
    $this->api->m->addDatastream($this->testPid, $this->testDsidE, 'url', 'http://test.com.fop', array('controlGroup' => 'E'));
    $this->api->m->addDatastream($this->testPid, $this->testDsidX, 'string', $this->testDsContents, array('controlGroup' => 'X'));
    $this->object = new FedoraObject($this->testPid, $this->repository);
    $this->ds = new FedoraDatastream($this->testDsid, $this->object, $this->repository);
    $this->e = new FedoraDatastream($this->testDsidE, $this->object, $this->repository);
    $this->r = new FedoraDatastream($this->testDsidR, $this->object, $this->repository);
    $this->x = new FedoraDatastream($this->testDsidX, $this->object, $this->repository);
  }

  protected function tearDown() {
    $this->api->m->purgeObject($this->testPid);
  }

  public function testId() {
    $this->assertEquals($this->testDsid, $this->ds->id);
  }

  public function testControlGroup() {
    $this->assertEquals('M', $this->ds->controlGroup);
  }

  public function testLabel() {
    $this->assertEquals('', $this->ds->label);
    $this->assertFalse(isset($this->ds->label));
    $this->ds->label = 'foo';
    $this->assertEquals('foo', $this->ds->label);
    $this->assertTrue(isset($this->ds->label));
    unset($this->ds->label);
    $this->assertEquals('', $this->ds->label);
    $this->assertFalse(isset($this->ds->label));
    $this->ds->label = 'woot';
    $this->assertEquals('woot', $this->ds->label);
    $this->ds->label = 'aboot';
    $this->assertEquals('aboot', $this->ds->label);
    $this->ds->label = FedoraTestHelpers::randomString(355);
    $this->assertEquals(255, strlen($this->ds->label));
  }

  public function testFormat() {
    $this->assertEquals('', $this->ds->format);
    $this->assertFalse(isset($this->ds->format));
    $this->ds->format = 'foo';
    $this->assertEquals('foo', $this->ds->format);
    $this->assertTrue(isset($this->ds->format));
    unset($this->ds->format);
    $this->assertEquals('', $this->ds->format);
    $this->assertFalse(isset($this->ds->format));
    $this->ds->format = 'woot';
    $this->assertEquals('woot', $this->ds->format);
    $this->ds->format = 'aboot';
    $this->assertEquals('aboot', $this->ds->format);
  }

  public function testState() {
    $this->assertEquals('A', $this->ds->state);

    $this->ds->state = 'I';
    $this->assertEquals('I', $this->ds->state);
    $this->ds->state = 'A';
    $this->assertEquals('A', $this->ds->state);
    $this->ds->state = 'D';
    $this->assertEquals('D', $this->ds->state);

    $this->ds->state = 'i';
    $this->assertEquals('I', $this->ds->state);
    $this->ds->state = 'a';
    $this->assertEquals('A', $this->ds->state);
    $this->ds->state = 'd';
    $this->assertEquals('D', $this->ds->state);

    $this->ds->state = 'inactive';
    $this->assertEquals('I', $this->ds->state);
    $this->ds->state = 'active';
    $this->assertEquals('A', $this->ds->state);
    $this->ds->state = 'deleted';
    $this->assertEquals('D', $this->ds->state);

    // @todo make this a test
    //$this->ds->state = 'foo';
    //$this->assertEquals('D', $this->ds->state);

    $this->assertTrue(isset($this->ds->state));
  }

  public function testVersionable() {
    $this->assertTrue($this->ds->versionable);

    $this->ds->versionable = FALSE;
    $this->assertFalse($this->ds->versionable);
    $this->assertFalse($this->ds->versionable);
    $this->assertTrue(isset($this->ds->versionable));

    $this->ds->versionable = TRUE;
    $this->assertTrue($this->ds->versionable);

    // @todo make this into a test
    //$this->ds->versionable = 'goo';
    //$this->assertTrue($this->ds->versionable);
    //$this->ds->versionable = FALSE;
    //$this->ds->versionable = 'goo';
  }

  public function testMimetype() {
    $this->ds->mimetype = 'amazing/sauce';
    $this->assertEquals('amazing/sauce', $this->ds->mimetype);
    $this->ds->mimetype = 'text/xml';
    $this->assertEquals('text/xml', $this->ds->mimetype);
    $this->assertTrue(isset($this->ds->mimetype));
  }

  public function testSize() {
    $this->assertEquals(19, $this->ds->size);
    $this->assertTrue(isset($this->ds->size));
  }

  /**
   * @todo make a better test
   */
  public function testCreatedDate() {
    $this->assertTrue($this->ds->createdDate instanceof FedoraDate);
    $this->assertTrue(isset($this->ds->createdDate));
  }

  public function testChecksum() {
    foreach (array('MD5', 'SHA-1', 'SHA-256', 'SHA-384', 'SHA-512') as $algorithm) {
      $this->ds->checksumType = $algorithm;
      $this->assertTrue(isset($this->ds->checksum));
      $this->assertTrue(isset($this->ds->checksumType));
      switch($algorithm) {
        case 'MD5':
          $algorithm = 'md5';
          break;
        case 'SHA-1':
          $algorithm = 'sha1';
          break;
        case 'SHA-256':
          $algorithm = 'sha256';
          break;
        case 'SHA-384':
          $algorithm = 'sha384';
          break;
        case 'SHA-512':
          $algorithm = 'sha512';
          break;
      }
      $hash = hash($algorithm, $this->testDsContents);
      $this->assertEquals($hash, $this->ds->checksum);
    }

    $this->ds->checksumType = 'DISABLED';
    $this->assertFalse(isset($this->ds->checksum));
    $this->assertFalse(isset($this->ds->checksumType));
    $this->assertEquals('none', $this->ds->checksum);
  }

  public function testContents() {
    $temp = tempnam(sys_get_temp_dir(), 'tuque');
    $this->assertEquals($this->testDsContents, $this->ds->content);
    $return = $this->ds->getContent($temp);
    $this->assertTrue($return);
    $this->assertEquals($this->testDsContents, file_get_contents($temp));
    unlink($temp);
    $this->assertTrue(isset($this->ds->content));
  }

  public function testContentsSet() {
    $this->ds->content = 'foo';
    $newds = new FedoraDatastream($this->testDsid, $this->object, $this->repository);
    $this->assertEquals('foo', $newds->content);
    $this->assertEquals(3, $newds->size);
  }

  public function testContentSetUrl() {
    $temp = tempnam(sys_get_temp_dir(), 'tuque');
    $this->ds->setContentFromUrl('http://office.discoverygarden.ca/testfiles/test.png');
    $actual = file_get_contents('http://office.discoverygarden.ca/testfiles/test.png');
    $this->assertEquals($actual, $this->ds->content);
    $this->ds->getContent($temp);
    $this->assertEquals($actual, file_get_contents($temp));
    unlink($temp);
  }

  public function testContentSetString() {
    $string = "I'm a string";
    $this->ds->setContentFromString($string);
    $this->assertEquals($string, $this->ds->content);
  }

  public function testContentSetFile() {
    $filepath = getcwd() . '/tests/test_data/test.png';
    $this->ds->setContentFromFile($filepath);
    $actual = file_get_contents($filepath);
    $this->assertEquals($actual, $this->ds->content);
  }

  public function testContentX() {
    $temp = tempnam(sys_get_temp_dir(), 'tuque');
    $this->x->content = '<woot/>';
    $newds = new FedoraDatastream($this->testDsidX, $this->object, $this->repository);
    $this->assertEquals('<woot></woot>', trim($newds->content));
    $this->x->getContent($temp);
    $this->assertEquals("\n<woot></woot>\n", file_get_contents($temp));
    unlink($temp);
  }

  public function testContentXFromFile() {
    $file = getcwd() . '/tests/test_data/test.xml';
    $this->x->setContentFromFile($file);
    $newds = new FedoraDatastream($this->testDsidX, $this->object, $this->repository);
    $this->assertEquals('<testFixture></testFixture>', trim($newds->content));
  }

  public function testContentXFromUrl() {
    $url = 'http://office.discoverygarden.ca/testfiles/woo.xml';
    $data = <<<foo
<woo>
  <test>
    <xml></xml>
  </test>
</woo>
foo;
    $this->x->setContentFromUrl($url);
    $newds = new FedoraDatastream($this->testDsidX, $this->object, $this->repository);
    $this->assertEquals($data, trim($newds->content));
  }

  public function testVersions() {
    $this->assertEquals(1, count($this->ds));
    $this->ds->label = 'foot';
    $this->assertEquals(2, count($this->ds));
    $this->assertEquals('', $this->ds[1]->label);

    $this->assertTrue(isset($this->ds[0]));
    $this->assertTrue(isset($this->ds[1]));
    $this->assertFalse(isset($this->ds[2]));

    $newds = new FedoraDatastream($this->testDsid, $this->object, $this->repository);
    $this->assertEquals(2, count($newds));
    $this->assertEquals('', $newds[1]->label);

    unset($newds[0]);
    $this->assertEquals('', $newds->label);
    $this->assertEquals(1, count($newds));

    $newds = new FedoraDatastream($this->testDsid, $this->object, $this->repository);
    $this->assertEquals(1, count($newds));
    $this->assertEquals('', $newds->label);
  }

  public function testVersionsState() {
    $this->assertEquals(1, count($this->ds));
    $this->assertEquals('A', $this->ds->state);
    $this->ds->state = 'D';
    $this->assertEquals(2, count($this->ds));
    $this->assertEquals('D', $this->ds->state);
    $this->assertEquals('A', $this->ds[1]->state);
  }

  public function testVersionsForEach() {
    $this->assertEquals(1, count($this->ds));
    $this->assertEquals('A', $this->ds->state);
    $this->ds->state = 'D';
    $this->assertEquals(2, count($this->ds));

    $state = array(0 => 'D', 1 => 'A');

    foreach($this->ds as $key => $version) {
      $this->assertEquals($state[$key], $version->state);
    }
  }

  public function testVersionsVersionable() {
    $this->assertEquals('', $this->ds->label);
    $this->assertEquals(1, count($this->ds));
    $this->ds->label = 'foot';
    $this->assertEquals(2, count($this->ds));
    $this->assertEquals('foot', $this->ds->label);
    $this->assertEquals('', $this->ds[1]->label);
    $this->ds->versionable = FALSE;
    $this->assertEquals(3, count($this->ds));
    $this->ds->label = 'crook';
    $this->ds->label = 'book';
    $this->ds->label = 'crook';
    $this->assertEquals('crook', $this->ds->label);
    $this->assertEquals(3, count($this->ds));
    $this->assertEquals('', $this->ds[2]->label);
    $this->ds->refresh();
    $this->assertEquals('crook', $this->ds->label);
    $this->assertEquals(3, count($this->ds));
    $this->assertEquals('', $this->ds[2]->label);
    $this->ds->versionable = TRUE;
    $this->assertEquals(3, count($this->ds));
    $this->ds->refresh();
    $this->assertEquals(3, count($this->ds));
  }

  /**
   * This test had originally tested for an expected 409 error.  The 409
   * has been dealt with, but we now get a 500, probably from having two
   * open references to the same object.  This should be investigated.
   *
   * @expectedException        RepositoryException
   * @expectedExceptionCode 500
   */
  public function testLocking() {
    $ds1 = new FedoraDatastream($this->testDsid, $this->object, $this->repository);
    $ds2 = new FedoraDatastream($this->testDsid, $this->object, $this->repository);
    $this->assertEquals($this->testDsContents, $ds1->content);
    // access a member so that the datastructures are loaded
    $this->assertEquals($ds1->state, $ds2->state);
    $ds2->content = 'foo';
    $ds1->content = 'bar';
  }

  public function testLockingForceUpdate() {
    $ds1 = new FedoraDatastream($this->testDsid, $this->object, $this->repository);
    $ds2 = new FedoraDatastream($this->testDsid, $this->object, $this->repository);

    $this->assertEquals($this->testDsContents, $ds1->content);

    // access a member so that the datastructures are loaded
    $this->assertEquals($ds1->state, $ds2->state);

    $ds2->content = 'foo';
    $ds1->forceUpdate = TRUE;
    $ds1->content = 'bar';
  }

  public function testLockingRefresh() {
    $ds1 = new FedoraDatastream($this->testDsid, $this->object, $this->repository);
    $ds2 = new FedoraDatastream($this->testDsid, $this->object, $this->repository);

    $this->assertEquals($this->testDsContents, $ds1->content);

    // access a member so that the datastructures are loaded
    $this->assertEquals($ds1->state, $ds2->state);

    $ds2->content = 'foo';
    try {
      $ds1->content = 'bar';
    }
    catch(RepositoryException $e) {
      $ds1->refresh();
      $ds1->content = 'bar';
      return;
    }
    $this->fail();
  }
}