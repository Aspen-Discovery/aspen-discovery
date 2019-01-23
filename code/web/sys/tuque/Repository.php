<?php
/**
 * @file
 * This file defines an abstract repository that can be overridden and also
 * defines a concrete implementation for Fedora.
 */

require_once "RepositoryQuery.php";
require_once "FoxmlDocument.php";
require_once "Object.php";

/**
 * An abstract repository interface.
 *
 * This can be used to override the implementation of the Repository.
 */
abstract class AbstractRepository extends MagicProperty {

  /**
   * This method is a factory that will return a new repositoryobject object
   * that can be manipulated and then ingested into the repository.
   *
   * @param string $id
   *   The ID to assign to this object. There are three options:
   *   - NULL: An ID will be assigned.
   *   - A namespace: An ID will be assigned in this namespace.
   *   - A whole ID: The whole ID must contains a namespace and a identifier in
   *     the form NAMESPACE:IDENTIFIER
   * @param boolean $create_uuid
   *   Indicates if the objects ID should contain a UUID.
   *
   * @return AbstractObject
   *   Returns an instantiated AbstractObject object that can be manipulated.
   *   This object will not actually be created in the repository until the
   *   ingest method is called.
   */
  abstract public function constructObject($id = NULL, $create_uuid = FALSE);

  /**
   * This ingests a new object into the repository.
   *
   * @param AbstractObject &$object
   *   The instantiated AbstractObject to ingest into the repository. This
   *   object is passed by reference, and the reference will be replaced by
   *   an object representing the ingested AbstractObject.
   *
   * @return AbstractObject
   *   The ingested abstract object.
   */
  abstract public function ingestObject(NewFedoraObject &$object);

  /**
   * Gets a object from the repository.
   *
   * @param string $id
   *   The identifier of the object.
   *
   * @return AbstractObject
   *   The requested object.
   */
  abstract public function getObject($id);

  /**
   * Removes an object from the repository.
   *
   * This function removes an object from the repository premenenty. It is a
   * dangerous function since it remvoes an object and all of its history from
   * the repository permenently.
   *
   * @param string $id
   *   The identifier of the object.
   *
   * @return boolean
   *   TRUE if object was purged.
   */
  abstract public function purgeObject($id);

  /**
   * Search the repository for objects.
   *
   * This function isn't implemented yet.
   *
   * @todo Flesh out the function definition for this.
   */
  abstract public function findObjects(array $search);

  /**
   * Will return an unused identifier for an object.
   *
   * @note
   *   It is not mathematically impossible to have collisions if the
   *   $create_uuid parameter is set to true.
   *
   * @param mixed $namespace
   *   NULL if we should use the default namespace.
   *   string the namespace to be used for the identifier.
   * @param boolean $create_uuid
   *   True if a V4 UUID should be used as part of the identifier.
   * @param integer $number_of_identifiers
   *   The number of identifers to return
   *   Defaults to 1.
   *
   * @return mixed
   *   string An identifier for an object.
   *   array  An array of identifiers for an object.
   *     @code
   *       Array
   *         (
   *           [0] => test:7
   *           [1] => test:8
   *         )
   *     @endcode
   */
  abstract public function getNextIdentifier($namespace = NULL, $create_uuid = FALSE, $number_of_identifiers = 1);

}

/**
 * Concrete implementation of the AbstractRepository for Fedora.
 *
 * The parent class has more detailed documentation about how this class can
 * be called as an Array.
 *
 * @see AbstractRepository
 */
class FedoraRepository extends AbstractRepository {

  /**
   * This is an instantiated AbstractCache that we use to make sure we aren't
   * instantiating the same objects over and over.
   *
   * @var AbstractCache
   */
  protected $cache;

  /**
   * This provides some convientent methods for searching the resource index.
   *
   * @var RepositoryQuery
   */
  public $ri;

  public $api;

  protected $queryClass = 'RepositoryQuery';
  protected $newObjectClass = 'NewFedoraObject';
  protected $objectClass = 'FedoraObject';

  /**
   * Constructor for the FedoraRepository Object.
   *
   * @param FedoraApi $api
   *   An instantiated FedoraAPI which will be used to connect to the
   *   repository.
   * @param AbstractCache $cache
   *   An instantiated AbstractCache which will be used to cache fedora objects.
   */
  public function __construct(FedoraApi $api, AbstractCache $cache) {
    $this->api = $api;
    $this->cache = $cache;
    $this->ri = new $this->queryClass($this->api->connection);
  }

  /**
   * @see AbstractRepository::findObjects
   * @todo this needs to be implemented!
   */
  public function findObjects(array $search) {
  }

  /**
   * @see AbstractRepository::constructObject
   */
  public function constructObject($id = NULL, $create_uuid = FALSE) {
    $exploded_id = explode(':', $id);
    // If no namespace or PID provided.
    if (!$id) {
      $id = $this->getNextIdentifier(NULL, $create_uuid);
    }
    // If namespace is provided.
    elseif (count($exploded_id) == 1) {
      $id = $this->getNextIdentifier($exploded_id[0], $create_uuid);
    }
    // If a full PID is provided we fall through to this.
    return new $this->newObjectClass($id, $this);
  }

  /**
   *  @todo validate the ID
   *  @todo catch the getNextPid errors
   *
   *  @see AbstractRepository::getNextIdentifier
   */
  public function getNextIdentifier($namespace = NULL, $create_uuid = FALSE, $number_of_identifiers = 1) {
    $pids = array();

    if ($create_uuid) {
      if (is_null($namespace)) {
        $repository_info = $this->api->a->describeRepository();
        $namespace = $repository_info['repositoryPID']['PID-namespaceIdentifier'];
      }
      if ($number_of_identifiers > 1) {
        for ($i = 1; $i <= $number_of_identifiers; $i++) {
          $pids[] = $namespace . ':' . $this->getUuid();
        }
      }
      else {
        $pids = $namespace . ':' . $this->getUuid();
      }
    }
    else {
      $pids = $this->api->m->getNextPid($namespace, $number_of_identifiers);
    }

    return $pids;
  }

  /**
   * This method will return a valid UUID based on V4 methods.
   *
   * @return string
   *   A valid V4 UUID.
   */
  protected function getUuid() {
    $bytes = openssl_random_pseudo_bytes(2);
    $add_mask = $this->convertHexToBin('4000');
    $negate_mask = $this->convertHexToBin('C000');
    // Make start with 11.
    $manipulated_bytes = $bytes | $negate_mask;
    // Make start with 01.
    $manipulated_bytes = $manipulated_bytes ^ $add_mask;
    $hex_string_10 = bin2hex($manipulated_bytes);

    return sprintf('%08s-%04s-4%03s-%s-%012s',
      bin2hex(openssl_random_pseudo_bytes(4)),
      bin2hex(openssl_random_pseudo_bytes(2)),
      // Four most significant bits holds version number 4.
      substr(bin2hex(openssl_random_pseudo_bytes(2)), 1),
      // Two most significant bits holds zero and one for variant DCE1.1
      $hex_string_10,
      bin2hex(openssl_random_pseudo_bytes(6))
    );
  }

  /**
   * Will convert a hexadecimal string into a representative byte string.
   *
   * @note
   *   This method can be eliminated in PHP >= 5.4.
   *   http://php.net/manual/en/function.hex2bin.php#110973
   *
   * @param string $hex
   *   A string representation of a hexadecimal number.
   *
   * @return string
   *   A byte string holding the bits indicated by the hex string.
   */
  protected function convertHexToBin($hex) {
    $length_of_hex = strlen($hex);
    $byte_string = "";
    $byte_counter = 0;
    while ($byte_counter < $length_of_hex) {
      $current_hex_byte = substr($hex, $byte_counter, 2);
      $current_binary_byte = pack("H*", $current_hex_byte);

      if ($byte_counter == 0) {
        $byte_string = $current_binary_byte;
      }
      else {
        $byte_string .= $current_binary_byte;
      }
      $byte_counter += 2;
    }

    return $byte_string;
  }

  /**
   * @see AbstractRepository::ingestObject()
   * @todo error handling
   */
  public function ingestObject(NewFedoraObject &$object) {
    // We want all the managed datastreams to be uploaded.
    foreach ($object as $ds) {
      if ($ds->controlGroup == 'M') {
        $temp = tempnam(sys_get_temp_dir(), 'tuque');
        $return = $ds->getContent($temp);
        if ($return === TRUE) {
          $url = $this->api->m->upload($temp);
          $ds->setContentFromUrl($url);
        }
        unlink($temp);
      }
    }

    $dom = new FoxmlDocument($object);
    $xml = $dom->saveXml();
    $id = $this->api->m->ingest(array('string' => $xml, 'logMessage' => $object->logMessage));
    $object = new $this->objectClass($id, $this);
    $this->cache->set($id, $object);
    return $object;
  }

  /**
   * @see AbstractRepository::getObject()
   * @todo perhaps we should check if an object exists instead of catching
   *   the exception
   */
  public function getObject($id) {
    $object = $this->cache->get($id);
    if ($object !== FALSE) {
      return $object;
    }

    try {
      $object = new $this->objectClass($id, $this);
      $this->cache->set($id, $object);
      return $object;
    }
    catch (RepositoryException $e) {
        throw $e;
    }
  }

  /**
   * @see AbstractRepository::purgeObject()
   */
  public function purgeObject($id) {
    try {
      $this->api->m->purgeObject($id);
      $object = $this->cache->get($id);
      if ($object !== FALSE) {
        return $this->cache->delete($id);;
      }
    }
    catch (RepositoryException $e) {
      // @todo chain exceptions here.
      throw $e;
    }
  }
}
