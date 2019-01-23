<?php
/**
 * @file
 * This file contains functions to serialize the XML responses from Fedora into
 * something more easily dealt with in PHP.
 */

define("RDF_NAMESPACE", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");

/**
 * A class to Serialize the XML responses from Fedora into PHP arrays.
 */
class FedoraApiSerializer {

  /**
   * Simple function that takes an XML string and returns a SimpleXml object.
   * It makes sure no PHP errors or warnings are issued and instead throws an
   * exception if the XML parse failed.
   *
   * @param string $xml
   *   The XML as a string
   *
   * @throws RepositoryXmlError
   *
   * @return SimpleXmlElement
   *   Return an istantiated simplexml
   */
  protected function loadSimpleXml($xml) {
    // We use the shutup operator so that we don't get a warning as well
    // as throwing an exception.
    $simplexml = @simplexml_load_string($xml);
    if ($simplexml === FALSE) {
      $errors = libxml_get_errors();
      libxml_clear_errors();
      throw new RepositoryXmlError('Failed to parse XML response from Fedora.', 0, $errors);
    }
    return $simplexml;
  }

  /**
   * This is a simple exception handler, that will throw an exception if there
   * is a problem parsing XML from Fedora. This is nice so we can catch it.
   *
   * @param int $errno
   *   The error number
   * @param string $errstr
   *   String describing an error.
   * @param string $errfile
   *   (optional) The third parameter is optional, errfile, which contains the
   *   filename that the error was raised in, as a string.
   * @param int $errline
   *   (optional) The fourth parameter is optional, errline, which contains the
   *   line number the error was raised at, as an integer.
   * @param array $errcontext
   *   (optional) The fifth parameter is optional, errcontext, which is an
   *   array that points to the active symbol table at the point the error
   *   occurred. In other words, errcontext will contain an array of every
   *   variable that existed in the scope the error was triggered in. User
   *   error handler must not modify error context.
   *
   * @return boolean
   *   TRUE if we through an exception FALSE otherwise
   *
   * @see php.net/manual/en/function.set-error-handler.php
   */
  public function domDocumentExceptionHandler($errno, $errstr, $errfile = '', $errline = NULL, $errcontext = NULL) {
    if ($errno == E_WARNING && strpos($errstr, "DOMDocument::loadXML()") !== FALSE) {
      throw new RepositoryXmlError('Failed to parse XML response from Fedora.', 0, $errstr);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Simple function that takes an XML string and returns a DomDocument object.
   * It makes sure no PHP errors or warnings are issued and instead throws an
   * exception if the XML parse failed.
   *
   * @param string $xml
   *   The XML as a string
   *
   * @throws RepositoryXmlError
   *
   * @return DomDocument
   *   Return an istantiated DomDocument
   */
  protected function loadDomDocument($xml) {
    set_error_handler(array($this, 'domDocumentExceptionHandler'));
    $dom = new DOMDocument();
    $dom->loadXml($xml);
    restore_error_handler();
    return $dom;
  }

  /**
   * Flatten a simplexml object returning an array containing the text from
   * the XML. This is often used to get data back from fedora. It also makes
   * sure to cast everything to string.
   *
   * @param SimpleXmlElement $xml
   *   The simplexml elemnt to be processed.
   * @param array() $make_array
   *   (optional) This parameter specifies tags that should become an array
   *   instead of an element in an array. This is used to get consistant values
   *   for things that are multivalued when there is only one value returned.
   *
   * @return array
   *   An array representation of the XML.
   */
  protected function flattenDocument($xml, $make_array = array()) {
    if (!is_object($xml)) {
      return '';
    }

    if ($xml->count() == 0) {
      return (string) $xml;
    }

    $initialized = array();
    $return = array();

    foreach ($xml->children() as $name => $child) {
      $value = $this->flattenDocument($child, $make_array);

      if (in_array($name, $make_array)) {
        $return[] = $value;
      }
      elseif (isset($return[$name])) {
        if (isset($initialized[$name])) {
          $return[$name][] = $value;
        }
        else {
          $tmp = $return[$name];
          $return[$name] = array();
          $return[$name][] = $tmp;
          $return[$name][] = $value;
          $initialized[$name] = TRUE;
        }
      }
      else {
        $return[$name] = $value;
      }
    }

    return $return;
  }

  /**
   * Serializes the data returned in FedoraApiA::describeRepository()
   */
  public function describeRepository($request) {
    $repository = $this->loadSimpleXml($request['content']);
    $data = $this->flattenDocument($repository);
    return $data;
  }

  /**
   * Serializes the data returned in FedoraApiA::userAttributes()
   */
  public function userAttributes($request) {
      $user_attributes = $this->loadSimpleXml($request['content']);
      $data = Array();
      foreach($user_attributes->attribute as $attribute){
          $values = Array();
          foreach($attribute->value as $value){
              array_push($values, (string)$value);
          }
          $data[(string)$attribute['name']] = $values;
      }
      return $data;
  }

  /**
   * Serializes the data returned in FedoraApiA::findObjects()
   */
  public function findObjects($request) {
    $results = $this->loadSimpleXml($request['content']);
    $data = array();

    if (isset($results->listSession)) {
      $data['session'] = $this->flattenDocument($results->listSession);
    }
    if (isset($results->resultList)) {
      $data['results'] = $this->flattenDocument($results->resultList, array('objectFields'));
    }

    return $data;
  }

  /**
   * Serializes the data returned in FedoraApiA::resumeFindObjects()
   */
  public function resumeFindObjects($request) {
    return $this->findObjects($request);
  }

  /**
   * Serializes the data returned in FedoraApiA::getDatastreamDissemination()
   */
  public function getDatastreamDissemination($request, $file) {
    if ($file) {
      return TRUE;
    }
    else {
      return $request['content'];
    }
  }

  /**
   * Serializes the data returned in FedoraApiA::getDissemination()
   */
  public function getDissemination($request) {
    return $request['content'];
  }

  /**
   * Serializes the data returned in FedoraApiA::getObjectHistory()
   */
  public function getObjectHistory($request) {
    $object_history = $this->loadSimpleXml($request['content']);
    $data = $this->flattenDocument($object_history, array('objectChangeDate'));
    return $data;
  }

  /**
   * Serializes the data returned in FedoraApiA::getObjectProfile()
   */
  public function getObjectProfile($request) {
    $result = $this->loadSimpleXml($request['content']);
    $data = $this->flattenDocument($result, array('model'));
    return $data;
  }

  /**
   * Serializes the data returned in FedoraApiA::listDatastreams()
   */
  public function listDatastreams($request) {
    $result = array();
    $datastreams = $this->loadSimpleXml($request['content']);
    // We can't use flattenDocument here, since everything is an attribute.
    foreach ($datastreams->datastream as $datastream) {
      $result[(string) $datastream['dsid']] = array(
        'label' => (string) $datastream['label'],
        'mimetype' => (string) $datastream['mimeType'],
      );
    }
    return $result;
  }

  /**
   * Serializes the data returned in FedoraApiA::listMethods()
   */
  public function listMethods($request) {
    $result = array();
    $object_methods = $this->loadSimpleXml($request['content']);
    // We can't use flattenDocument here because of the atrtibutes.
    if (isset($object_methods->sDef)) {
      foreach ($object_methods->sDef as $sdef) {
        $methods = array();
        if (isset($sdef->method)) {
          foreach ($sdef->method as $method) {
            $methods[] = (string) $method['name'];
          }
        }
        $result[(string) $sdef['pid']] = $methods;
      }
    }
    return $result;
  }

  /**
   * Serializes the data returned in FedoraApiM::addDatastream()
   */
  public function addDatastream($request) {
    return $this->getDatastream($request);
  }

  /**
   * Serializes the data returned in FedoraApiM::addRelationship()
   */
  public function addRelationship($request) {
    return TRUE;
  }

  /**
   * Serializes the data returned in FedoraApiM::export()
   */
  public function export($request) {
    return $request['content'];
  }

  /**
   * Serializes the data returned in FedoraApiM::getDatastream()
   */
  public function getDatastream($request) {
    $result = $this->loadSimpleXml($request['content']);
    $data = $this->flattenDocument($result);
    return $data;
  }

  /**
   * Serializes the data returned in FedoraApiM::getDatastreamHistory()
   */
  public function getDatastreamHistory($request) {
    $result = $this->loadSimpleXml($request['content']);
    $result = $this->flattenDocument($result, array('datastreamProfile'));

    return $result;
  }

  /**
   * Serializes the data returned in FedoraApiM::getNextPid()
   */
  public function getNextPid($request) {
    $result = $this->loadSimpleXml($request['content']);
    $result = $this->flattenDocument($result);
    $result = $result['pid'];

    return $result;
  }

  /**
   * Serializes the data returned in FedoraApiM::getObjectXml()
   */
  public function getObjectXml($request) {
    return $request['content'];
  }

  /**
   * Serializes the data returned in FedoraApiM::getRelationships()
   */
  public function getRelationships($request) {
    $relationships = array();

    $dom = $this->loadDomDocument($request['content']);
    $xpath = new DomXPath($dom);
    $results = $xpath->query('/rdf:RDF/rdf:Description/*');

    foreach ($results as $element) {
      $relationship = array();
      $parent = $element->parentNode;

      // Remove the 'info:fedora/' from the subject.
      $subject = $parent->getAttributeNS(RDF_NAMESPACE, 'about');
      $subject = explode('/', $subject);
      unset($subject[0]);
      $subject = implode('/', $subject);
      $relationship['subject'] = $subject;

      // This section parses the predicate.
      $predicate = explode(':', $element->tagName);
      $predicate = count($predicate) == 1 ? $predicate[0] : $predicate[1];
      $predicate = array('predicate' => $predicate);
      $predicate['uri'] = $element->namespaceURI;
      $predicate['alias'] = $element->lookupPrefix($predicate['uri']);
      $relationship['predicate'] = $predicate;

      // This section parses the object.
      if ($element->hasAttributeNS(RDF_NAMESPACE, 'resource')) {
        $attrib = $element->getAttributeNS(RDF_NAMESPACE, 'resource');
        $attrib = explode('/', $attrib);
        unset($attrib[0]);
        $attrib = implode('/', $attrib);
        $object['literal'] = FALSE;
        $object['value'] = $attrib;
      }
      else {
        $object['literal'] = TRUE;
        $object['value'] = $element->nodeValue;
      }
      $relationship['object'] = $object;

      $relationships[] = $relationship;
    }

    return $relationships;
  }

  /**
   * Serializes the data returned in FedoraApiM::ingest()
   */
  public function ingest($request) {
    return $request['content'];
  }

  /**
   * Serializes the data returned in FedoraApiM::modifyDatastream()
   */
  public function modifyDatastream($request) {
    $result = $this->loadSimpleXml($request['content']);
    return $this->flattenDocument($result);
  }

  /**
   * Serializes the data returned in FedoraApiM::modifyObject()
   */
  public function modifyObject($request) {
    return $request['content'];
  }

  /**
   * Serializes the data returned in FedoraApiM::purgeDatastream()
   */
  public function purgeDatastream($request) {
    return json_decode($request['content']);
  }

  /**
   * Serializes the data returned in FedoraApiM::purgeObject()
   */
  public function purgeObject($request) {
    return $request['content'];
  }

  /**
   * Serializes the data returned in FedoraApiM::validate()
   */
  public function validate($request){
    $result = $this->loadSimpleXml($request['content']);
    $doc = $this->flattenDocument($result);
    $doc['valid'] = (string) $result['valid'] == "true" ? TRUE : FALSE;
    return $doc;
  }

  public function upload($request) {
    return $request['content'];
  }

}
