<?php

/**
 * @file
 * This file defines all the classes used to manipulate datastreams in the
 * repository.
 */
require_once 'MagicProperty.php';
require_once 'FedoraDate.php';

/**
 * This abstract class can be overriden by anything implementing a datastream.
 */
abstract class AbstractDatastream extends MagicProperty {

  /**
   * This will set the state of the datastream to deleted.
   */
  abstract public function delete();

  /**
   * Set the contents of the datastream from a file.
   *
   * @param string $file
   *   The full path of the file to set to the contents of the datastream.
   */
  abstract public function setContentFromFile($file);

  /**
   * Set the contents of the datastream from a URL. The contents of this
   * URL will be fetched, and the datastream will be updated to contain the
   * contents of the URL.
   *
   * @param string $url
   *   The full URL to fetch.
   */
  abstract public function setContentFromUrl($url);

  /**
   * Set the contents of the datastream from a string.
   *
   * @param string $string
   *   The string whose contents will become the contents of the datastream.
   */
  abstract public function setContentFromString($string);

  /**
   * Get the contents of a datastream and output it to the file provided.
   *
   * @param string $file
   *   The path of the file to output the contents of the datastream to.
   *
   * @return
   *   TRUE on success or FALSE on failure.
   */
  abstract public function getContent($file);

  /**
   * The identifier of the datastream. This is a read-only property.
   *
   * @var string
   */
  public $id;
  /**
   * The label of the datastream. Fedora limits the label to be 255 characters.
   * Anything after this amount is truncated.
   *
   * @var string
   */
  public $label;
  /**
   * the location of consists of a combination of
   * datastream id and datastream version id
   * @var type
   */
  public $location;
  /**
   * The control group of the datastream. This property is read-only. This will
   * return one of: "X", "M", "R", or "E" (Inline *X*ML, *M*anaged Content,
   * *R*edirect, or *E*xternal Referenced). Defaults to "M".
   * @var string
   */
  public $controlGroup;
  /**
   * This defines if the datastream will be versioned or not.
   * @var boolean
   */
  public $versionable;
  /**
   * The state of the datastream. This will be one of: "A", "I", "D". When
   * setting the property you can use: A, I, D or Active, Inactive, Deleted.
   * @var string
   */
  public $state;
  /**
   * The mimetype of the datastrem.
   * @var string
   */
  public $mimetype;
  /**
   * The format of the datastream.
   * @var string
   */
  public $format;
  /**
   * The size in bytes of the datastream. This is only valid once a datastream
   * has been ingested.
   *
   * @var int
   */
  public $size;
  /**
   * The base64 encoded checksum string.
   *
   * @var string
   */
  public $checksum;
  /**
   * The type of checksum that will be done on this datastream. Defaults to
   * DISABLED. One of: DISABLED, MD5, SHA-1, SHA-256, SHA-384, SHA-512.
   *
   * @var string
   */
  public $checksumType;
  /**
   * The date the datastream was created.
   *
   * @var FedoraDate
   */
  public $createdDate;
  /**
   * The contents of the datastream as a string. This can only be set for
   * M and X datastreams. For R and E datastreams the URL property needs to be
   * set which will change the contents of this property. This should only be
   * used for small files, as it loads the contents into PHP memory. Otherwise
   * you should use the getContent function.
   *
   * @var string
   */
  public $content;
  /**
   * This is only valid for R and E datastreams. This is the URL that the
   * datastream references.
   *
   * @var string
   */
  public $url;
  /**
   * This is the log message that will be associated with the action in the
   * Fedora audit datastream.
   *
   * @var string
   */
  public $logMessage;

  /**
   * Unsets public members.
   *
   * We only define the public members of the object for Doxygen, they aren't actually accessed or used,
   * and if they are not unset, they can cause problems after unserialization.
   */
  public function __construct() {
    $this->unset_members();
  }

  /**
   * Upon unserialization unset any public members.
   */
  public function __wakeup() {
    $this->unset_members();
  }

  /**
   * Unsets public members, required for child classes to funciton properly with MagicProperties.
   */
  private function unset_members() {
    unset($this->id);
    unset($this->label);
    unset($this->controlGroup);
    unset($this->versionable);
    unset($this->state);
    unset($this->mimetype);
    unset($this->format);
    unset($this->size);
    unset($this->checksum);
    unset($this->checksumType);
    unset($this->createdDate);
    unset($this->content);
    unset($this->url);
    unset($this->location);
    unset($this->logMessage);
  }
}

/**
 * Abstract base class implementing a datastream in Fedora.
 */
abstract class AbstractFedoraDatastream extends AbstractDatastream {

  /**
   * The repository this object belongs to.
   * @var FedoraRepository
   */
  public $repository;
  /**
   * The fedora object this datastream belongs to.
   * @var AbstractFedoraObject
   */
  public $parent;
  /**
   * An object for manipulating the fedora relationships related to this DS.
   * @var FedoraRelsInt
   */
  public $relationships;
  /**
   * The read only ID of the datastream.
   *
   * @var string
   */
  protected $datastreamId = NULL;
  /**
   * The array defining what is in the datastream.
   *
   * @var array
   * @see FedoraApiM::getDatastream
   */
  protected $datastreamInfo = NULL;

  protected $fedoraRelsIntClass = 'FedoraRelsInt';
  protected $fedoraDatastreamVersionClass = 'FedoraDatastreamVersion';

  /**
   * The constructor for the datastream.
   *
   * @param string $id
   *   The identifier of the datastream.
   */
  public function __construct($id, AbstractFedoraObject $object, FedoraRepository $repository) {
    parent::__construct();
    $this->datastreamId = $id;
    $this->parent = $object;
    $this->repository = $repository;
    $this->relationships = new $this->fedoraRelsIntClass($this);
  }

  /**
   * @see AbstractDatastream::id
   */
  protected function idMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->datastreamId;
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly datastream->id property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @see AbstractDatastream::delete()
   */
  public function delete() {
    $this->state = 'd';
  }

  /**
   * This is a replacement for isset when things can't be unset. So we define
   * a default value, then return TRUE or FALSE based on if it is set.
   *
   * @param anything $actual
   *   The value we are testing.
   * @param anything $unsetval
   *   The value it would be if it was unset.
   *
   * @return boolean
   *   TRUE or FALSE
   */
  protected function isDatastreamProperySet($actual, $unsetval) {
    if ($actual === $unsetval) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Validates a mimetype using a regular expression.
   *
   * @param string $mime
   *   A string representing a mimetype
   *
   * @return boolean
   *   TRUE if the string looks like a mimetype.
   *
   * @todo test if this covers all cases.
   */
  protected function validateMimetype($mime) {
    if (preg_match('#^[-\w]+/[-\w\.+]+$#', $mime)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Validates and normalizes the datastream state.
   *
   * @param string $state
   *   The input state
   *
   * @return string
   *   Returns FALSE if validation fails, otherwise it returns the normalized
   *   datastream state.
   */
  protected function validateState($state) {
    switch (strtolower($state)) {
      case 'd':
      case 'deleted':
        return 'D';
        break;

      case 'a':
      case 'active':
        return 'A';
        break;

      case 'i':
      case 'inactive':
        return 'I';
        break;

      default:
        return FALSE;
        break;
    }
  }

  /**
   * Validates the versionable setting of a datastream.
   *
   * @param mixed $versionable
   *   The input versionable arguement.
   *
   * @return boolean
   *   Returns TRUE if the arguement is a boolean, FALSE otherwise.
   */
  protected function validateVersionable($versionable) {
    return is_bool($versionable);
  }

  /**
   * Validates and normalizes the checksumType arguement.
   *
   * @param string $type
   *   The input string
   *
   * @return mixed
   *   FALSE if validation fails. The checksumType string otherwise.
   */
  protected function validateChecksumType($type) {
    switch ($type) {
      case 'DEFAULT':
      case 'DISABLED':
      case 'MD5':
      case 'SHA-1':
      case 'SHA-256':
      case 'SHA-384':
      case 'SHA-512':
        return $type;
        break;

      default:
        return FALSE;
        break;
    }
  }

  /**
   * @see AbstractDatastream::controlGroup
   */
  protected function controlGroupMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->datastreamInfo['dsControlGroup'];
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly datastream->controlGroup property.", E_USER_WARNING);
        break;
    }
  }

}

/**
 * This defines a new fedora datastream. This is the class used to contain the
 * inforamtion for a new fedora datastream before it is ingested.
 */
class NewFedoraDatastream extends AbstractFedoraDatastream {

  /**
   * Used to determine if we should delete the contents of this datastream when
   * this class is destoryed.
   *
   * @var boolean
   */
  protected $copied = FALSE;

  /**
   * The constructor for a new fedora datastream.
   *
   * @param string $id
   *   The unique identifier of the DS.
   * @param FedoraObject $object
   *   The FedoraObject that this DS belongs to.
   * @param FedoraRepository $repository
   *   The FedoraRepository that this DS belongs to.
   * @param string $control_group
   *   The control group this DS will belong to.
   *
   * @todo test for valid identifiers. it can't start with a number etc.
   */
  public function __construct($id, $control_group, AbstractFedoraObject $object, FedoraRepository $repository) {
    parent::__construct($id, $object, $repository);

    $group = $this->validateControlGroup($control_group);

    if ($group === FALSE) {
      trigger_error("Invalid control group \"$control_group\", using managed instead.", E_USER_WARNING);
      $group = 'M';
    }

    // Set defaults!
    $this->datastreamInfo['dsControlGroup'] = $group;
    $this->datastreamInfo['dsState'] = 'A';
    $this->datastreamInfo['dsLabel'] = '';
    $this->datastreamInfo['dsVersionable'] = TRUE;
    $this->datastreamInfo['dsMIME'] = 'text/xml';
    $this->datastreamInfo['dsFormatURI'] = '';
    $this->datastreamInfo['dsChecksumType'] = 'DISABLED';
    $this->datastreamInfo['dsChecksum'] = 'none';
    $this->datastreamInfo['dsLogMessage'] = '';
    $this->datastreamInfo['content'] = array('type' => 'string', 'content' => ' ');
  }

  /**
   * Validates and normalizes the control group.
   *
   * @param string $value
   *   The passed in control group.
   *
   * @return mixed
   *   The stirng for hte controlgroup or FALSE if validation fails.
   */
  protected function validateControlGroup($value) {
    switch (strtolower($value)) {
      case 'x':
      case 'inline':
      case 'inline xml':
        return 'X';
        break;

      case 'm':
      case 'managed':
      case 'managed content':
        return 'M';
        break;

      case 'r':
      case 'redirect':
        return 'R';
        break;

      case 'e':
      case 'external':
      case 'external referenced':
        return 'E';
        break;

      default:
        return FALSE;
        break;
    }
  }

  /**
   * Validates and normalizes the contentType.
   *
   * @param string $type
   *   The passed in value for type.
   *
   * @return mixed
   *   The stirng for the type or FALSE if validation fails.
   */
  protected function validateType($type) {
    switch (strtolower($type)) {
      case 'string':
        return 'string';
        break;

      case 'url':
        return 'url';
        break;

      case 'file':
        return 'file';
        break;

      default:
        return FALSE;
        break;
    }
  }

  /**
   *  @see AbstractDatastream::controlGroup
   */
  protected function controlGroupMagicProperty($function, $value) {
    return parent::controlGroupMagicProperty($function, $value);
  }

  /**
   *  @see AbstractDatastream::state
   */
  protected function stateMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->datastreamInfo['dsState'];
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
        $state = $this->validateState($value);
        if ($state !== FALSE) {
          $this->datastreamInfo['dsState'] = $state;
        }
        else {
          trigger_error("$value is not a valid value for the datastream->state property.", E_USER_WARNING);
        }
        break;

      case 'unset':
        trigger_error("Cannot unset the required datastream->state property.", E_USER_WARNING);
        break;
    }
  }

  /**
   *  @see AbstractDatastream::label
   */
  protected function labelMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->datastreamInfo['dsLabel'];
        break;

      case 'isset':
        return $this->isDatastreamProperySet($this->datastreamInfo['dsLabel'], '');
        break;

      case 'set':
        $this->datastreamInfo['dsLabel'] = function_exists('mb_substr') ? mb_substr($value, 0, 255) : substr($value, 0, 255);
        break;

      case 'unset':
        $this->datastreamInfo['dsLabel'] = '';
        break;
    }
  }

  /**
   *  @see AbstractDatastream::versionable
   */
  protected function versionableMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->datastreamInfo['dsVersionable'];
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
        if ($this->validateVersionable($value)) {
          $this->datastreamInfo['dsVersionable'] = $value;
        }
        else {
          trigger_error("Datastream->versionable must be a boolean.", E_USER_WARNING);
        }
        break;

      case 'unset':
        trigger_error("Cannot unset the required datastream->versionable property.", E_USER_WARNING);
        break;
    }
  }

  /**
   *  @see AbstractDatastream::mimetype
   */
  protected function mimetypeMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->datastreamInfo['dsMIME'];
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
        if ($this->validateMimetype($value)) {
          $this->datastreamInfo['dsMIME'] = $value;
        }
        else {
          trigger_error("Invalid mimetype.", E_USER_WARNING);
        }
        break;

      case 'unset':
        trigger_error("Cannot unset the required datastream->mimetype property.", E_USER_WARNING);
        break;
    }
  }

  /**
   *  @see AbstractDatastream::format
   */
  protected function formatMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->datastreamInfo['dsFormatURI'];
        break;

      case 'isset':
        return $this->isDatastreamProperySet($this->datastreamInfo['dsFormatURI'], '');
        break;

      case 'set':
        $this->datastreamInfo['dsFormatURI'] = $value;
        break;

      case 'unset':
        $this->datastreamInfo['dsFormatURI'] = '';
        break;
    }
  }

  /**
   * @see AbstractDatastream::checksum
   * @todo this should be refined a bit
   */
  protected function checksumMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->datastreamInfo['dsChecksum'];
        break;

      case 'isset':
        return $this->isDatastreamProperySet($this->datastreamInfo['dsChecksum'], 'none');
        break;

      case 'set':
        $this->datastreamInfo['dsChecksum'] = $value;

      case 'unset':
        $this->datastreamInfo['dsChecksum'] = 'none';
        break;
    }
  }

  /**
   *  @see AbstractDatastream::checksumType
   */
  protected function checksumTypeMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->datastreamInfo['dsChecksumType'];
        break;

      case 'isset':
        return $this->isDatastreamProperySet($this->datastreamInfo['dsChecksumType'], 'DISABLED');
        break;

      case 'set':
        $type = $this->validateChecksumType($value);
        if ($type !== FALSE) {
          $this->datastreamInfo['dsChecksumType'] = $type;
        }
        else {
          trigger_error("$value is not a valid value for the datastream->checksumType property.", E_USER_WARNING);
        }
        break;

      case 'unset':
        $this->datastreamInfo['dsChecksumType'] = 'DISABLED';
        break;
    }
  }

  /**
   *  @see AbstractDatastream::content
   */
  protected function contentMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        switch ($this->datastreamInfo['content']['type']) {
          case 'string':
          case 'url':
            return $this->datastreamInfo['content']['content'];
          case 'file':
            return file_get_contents($this->datastreamInfo['content']['content']);
        }
        break;

      case 'isset':
        return $this->isDatastreamProperySet($this->datastreamInfo['content']['content'], ' ');
        break;

      case 'set':
        if ($this->controlGroup == 'M' || $this->controlGroup == 'X') {
          $this->deleteTempFile();
          $this->datastreamInfo['content']['type'] = 'string';
          $this->datastreamInfo['content']['content'] = $value;
        }
        else {
          trigger_error("Cannot set content of a {$this->controlGroup} datastream, please use datastream->url.", E_USER_WARNING);
        }
        break;

      case 'unset':
        if ($this->controlGroup == 'M' || $this->controlGroup == 'X') {
          $this->datastreamInfo['content']['type'] = 'string';
          $this->datastreamInfo['content']['content'] = ' ';
        }
        else {
          trigger_error("Cannot unset content of a {$this->controlGroup} datastream, please use datastream->url.", E_USER_WARNING);
        }
        break;
    }
  }

  /**
   *  @see AbstractDatastream::url
   */
  protected function urlMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
          return $this->datastreamInfo['content']['content'];
        }
        else {
          trigger_error("Datastream->url property is undefined for a {$this->controlGroup} datastream.", E_USER_WARNING);
          return NULL;
        }
        break;

      case 'isset':
        if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
          return TRUE;
        }
        else {
          return FALSE;
        }
        break;

      case 'set':
        if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
          $this->datastreamInfo['content']['type'] = 'url';
          $this->datastreamInfo['content']['content'] = $value;
        }
        else {
          trigger_error("Cannot set url of a {$this->controlGroup} datastream, please use datastream->content.", E_USER_WARNING);
        }
        break;

      case 'unset':
        trigger_error("Cannot unset the required datastream->url property.", E_USER_WARNING);
        break;
    }
  }

  /**
   *  @see AbstractDatastream::logMessage
   */
  protected function logMessageMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->datastreamInfo['dsLogMessage'];
        break;

      case 'isset':
        return $this->isDatastreamProperySet($this->datastreamInfo['dsLogMessage'], '');
        break;

      case 'set':
        $this->datastreamInfo['dsLogMessage'] = $value;
        break;

      case 'unset':
        $this->datastreamInfo['dsLogMessage'] = '';
        break;
    }
  }

  /**
   *  @see AbstractDatastream::setContentFromFile
   *
   * @param boolean $copy
   *   If TRUE this object will copy and manage the given file, if FALSE the management of the files is up to the caller.
   */
  public function setContentFromFile($file, $copy = TRUE) {
    if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
      trigger_error("Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.", E_USER_WARNING);
      return;
    }
    $this->deleteTempFile();
    $this->copied = $copy;
    if ($copy) {
      $tmpfile = tempnam(sys_get_temp_dir(), 'tuque');
      copy($file, $tmpfile);
      $file = $tmpfile;
    }
    $this->datastreamInfo['content']['type'] = 'file';
    $this->datastreamInfo['content']['content'] = $file;
  }

  /**
   * @see AbstractDatastream::setContentFromUrl
   *
   * @param string $url
   *   Https (SSL) URL's will cause this to fail.
   */
  public function setContentFromUrl($url) {
    if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
      trigger_error("Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.", E_USER_WARNING);
      return;
    }
    $this->deleteTempFile();
    $this->datastreamInfo['content']['type'] = 'url';
    $this->datastreamInfo['content']['content'] = $url;
  }

  /**
   *  @see AbstractDatastream::setContentFromString
   */
  public function setContentFromString($string) {
    if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
      trigger_error("Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.", E_USER_WARNING);
      return;
    }
    $this->deleteTempFile();
    $this->datastreamInfo['content']['type'] = 'string';
    $this->datastreamInfo['content']['content'] = $string;
  }

  /**
   * @see AbstractDatastream::getContent
   */
  public function getContent($file) {
    if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
      trigger_error("Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.", E_USER_WARNING);
      return;
    }
    switch ($this->datastreamInfo['content']['type']) {
      case 'file':
        copy($this->datastreamInfo['content']['content'], $file);
        return TRUE;
      case 'string':
        file_put_contents($file, $this->datastreamInfo['content']['content']);
        return TRUE;
      case 'url':
        return FALSE;
    }
  }

  public function __destruct() {
    $this->deleteTempFile();
  }

  /**
   * Deletes any temp files that may be present such that we do not 'leak'
   * over any files.
   */
  private function deleteTempFile() {
    if ($this->datastreamInfo['content']['type'] == 'file' && $this->copied == TRUE) {
      unlink($this->datastreamInfo['content']['content']);
    }
  }
}

/**
 * This abstract class defines some shared functionality between all classes
 * that implement exising fedora datastreams.
 */
abstract class AbstractExistingFedoraDatastream extends AbstractFedoraDatastream {

  /**
   * Class constructor.
   *
   * @param string $id
   *   Unique identifier for the DS.
   * @param FedoraObject $object
   *   The FedoraObject that this DS belongs to.
   * @param FedoraRepository $repository
   *   The FedoraRepository that this DS belongs to.
   */
  public function __construct($id, FedoraObject $object, FedoraRepository $repository) {
    parent::__construct($id, $object, $repository);
  }

  /**
   * Wrapper for the APIA getDatastreamDissemination function.
   *
   * @param string $version
   *   The version of the content to retreve.
   * @param string $file
   *   The file to put the content into.
   *
   * @return string
   *   String containing the content.
   */
  protected function getDatastreamContent($version = NULL, $file = NULL) {
    return $this->repository->api->a->getDatastreamDissemination($this->parent->id, $this->id, $version, $file);
  }

  /**
   * Wrapper around the APIM getDatastreamHistory function.
   *
   * @return array
   *   Array containing datastream history.
   */
  protected function getDatastreamHistory() {
    return $this->repository->api->m->getDatastreamHistory($this->parent->id, $this->id);
  }

  /**
   * Wrapper around the APIM modifyDatastream function.
   *
   * @param array $args
   *   Args to pass to the function.
   *
   * @return array
   *   Datastream history array.
   */
  protected function modifyDatastream(array $args) {
    return $this->repository->api->m->modifyDatastream($this->parent->id, $this->id, $args);
  }

  /**
   * Wrapper around the APIM Purge function.
   *
   * @param string $version
   *   The version to purge.
   *
   * @return array
   *   The versions purged.
   */
  protected function purgeDatastream($version) {
    return $this->repository->api->m->purgeDatastream($this->parent->id, $this->id, array('startDT' => $version, 'endDT' => $version));
  }

}

/**
 * This class implements an old version of a fedora datastream. Its properties
 * are the same of a normal fedora datastream, except since its an older verion
 * everything is read only.
 */
class FedoraDatastreamVersion extends AbstractExistingFedoraDatastream {

  /**
   * The parent datastream.
   * @var FedoraDatastream
   */
  public $parent;

  /**
   * The Constructor! Sounds like a superhero doesn't it. Constructor away!
   */
  public function __construct($id, array $datastream_info, FedoraDatastream $datastream, FedoraObject $object, FedoraRepository $repository) {
    parent::__construct($id, $object, $repository);
    $this->datastreamInfo = $datastream_info;
    $this->parent = $object;
  }

  /**
   * This function gives us a consistant error across this whole clas.
   */
  protected function error() {
    trigger_error("All properties of previous datastream versions are read only. Please modify parent datastream object.", E_USER_WARNING);
  }

  /**
   * Since this whole class is read only, this is a general implementation of
   * the MagicPropery function that is ready only.
   */
  protected function generalReadOnly($offset, $unset_val, $function, $value) {
    switch ($function) {
      case 'get':
        return $this->datastreamInfo[$offset];
        break;

      case 'isset':
        if ($unset_val === NULL) {
          // Object cannot be unset.
          return TRUE;
        }
        else {
          return $this->isDatastreamProperySet($this->datastreamInfo[$offset], $unset_val);
        }
        break;

      case 'set':
      case 'unset':
        $this->error();
        break;
    }
  }

  /**
   * @see AbstractDatastream::state
   */
  protected function stateMagicProperty($function, $value) {
    return $this->generalReadOnly('dsState', NULL, $function, $value);
  }

  /**
   * @see AbstractDatastream::label
   */
  protected function labelMagicProperty($function, $value) {
    return $this->generalReadOnly('dsLabel', '', $function, $value);
  }

  /**
   * @see AbstractDatastream::versionable
   */
  protected function versionableMagicProperty($function, $value) {
    if (!is_bool($this->datastreamInfo['dsVersionable'])) {
      $this->datastreamInfo['dsVersionable'] = $this->datastreamInfo['dsVersionable'] == 'true' ? TRUE : FALSE;
    }
    return $this->generalReadOnly('dsVersionable', NULL, $function, $value);
  }

  /**
   * @see AbstractDatastream::mimetype
   */
  protected function mimetypeMagicProperty($function, $value) {
    return $this->generalReadOnly('dsMIME', '', $function, $value);
  }

  /**
   * @see AbstractDatastream::format
   */
  protected function formatMagicProperty($function, $value) {
    return $this->generalReadOnly('dsFormatURI', '', $function, $value);
  }

  /**
   * @see AbstractDatastream::size
   */
  protected function sizeMagicProperty($function, $value) {
    return $this->generalReadOnly('dsSize', NULL, $function, $value);
  }

  /**
   * @see AbstractDatastream::checksum
   */
  protected function checksumMagicProperty($function, $value) {
    return $this->generalReadOnly('dsChecksum', 'none', $function, $value);
  }

  /**
   * @see AbstractDatastream::url
   */
  protected function urlMagicProperty($function, $value) {
    if (in_array($this->controlGroup, array('R', 'E'))) {
      return $this->generalReadOnly('dsLocation', FALSE, $function, $value);
    }
    else {
      trigger_error("No 'url' property on datastreams in control group {$this->controlGroup}", E_USER_WARNING);
    }
  }

  /**
   * @see AbstractDatastream::createDate
   */
  protected function createdDateMagicProperty($function, $value) {
    if (!$this->datastreamInfo['dsCreateDate'] instanceof FedoraDate) {
      $this->datastreamInfo['dsCreateDate'] = new FedoraDate($this->datastreamInfo['dsCreateDate']);
    }
    return $this->generalReadOnly('dsCreateDate', NULL, $function, $value);
  }

  /**
   * @see AbstractDatastream::content
   */
  protected function contentMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->getDatastreamContent((string) $this->createdDate);
        break;

      case 'isset':
        return $this->isDatastreamProperySet($this->content, '');
        break;

      case 'set':
      case 'unset':
        $this->error();
        break;
    }
  }

  /**
   * @see AbstractDatastream::logMessage
   */
  protected function logMessageMagicProperty($function, $value) {
    return $this->generalReadOnly('dsLogMessage', '', $function, $value);
  }

  /**
   * @see AbstractDatastream::setContentFromFile()
   */
  public function setContentFromFile($file) {
    $this->error();
  }

  /**
   * @see AbstractDatastream::setContentFromString()
   */
  public function setContentFromString($string) {
    $this->error();
  }

  /**
   * @see AbstractDatastream::setContentFromUrl()
   */
  public function setContentFromUrl($url) {
    $this->error();
  }

  /**
   * @see AbstractDatastream::getContent()
   */
  public function getContent($file) {
    return $this->getDatastreamContent((string) $this->createdDate, $file);
  }
}

/**
 * This class implements a fedora datastream.
 *
 * It also lets old versions of datastreams be accessed using array notation.
 * For example to see how many versions of a datastream there are:
 * @code
 *   count($datastream)
 * @endcode
 *
 * Old datastreams are indexed newest to oldest. The current version is always
 * index 0, and older versions are indexed from that. Old versions can be
 * discarded using the unset command.
 *
 * These functions respect datastream locking. If a datastream changes under
 * your feet then an exception will be raised.
 */
class FedoraDatastream extends AbstractExistingFedoraDatastream implements Countable, ArrayAccess, IteratorAggregate {

  /**
   * An array containing the datastream history.
   * @var array
   */
  protected $datastreamHistory = NULL;
  /**
   * If this is set to TRUE then datastream locking won't be respected. This is
   * dangerous as any changes could clobber someone elses changes.
   *
   * @var boolean
   */
  public $forceUpdate = FALSE;

  /**
   * Domo arigato, Mr. Roboto. Constructor.
   */
  public function __construct($id, FedoraObject $object, FedoraRepository $repository, array $datastream_info = NULL) {
    parent::__construct($id, $object, $repository);
    $this->datastreamInfo = $datastream_info;
  }

  /**
   * This function clears the datastreams caches, so everything will be
   * requested directly from fedora again.
   */
  public function refresh() {
    $this->datastreamInfo = NULL;
    $this->datastreamHistory = NULL;
  }

  /**
   * This populates datastream history if it needs to be populated.
   */
  protected function populateDatastreamHistory() {
    if ($this->datastreamHistory === NULL) {
      $this->datastreamHistory = $this->getDatastreamHistory();
    }
  }

  /**
   * This function uses datastream history to populate datastream info.
   */
  protected function populateDatastreamInfo() {
    $this->datastreamHistory = $this->getDatastreamHistory();
    $this->datastreamInfo = $this->datastreamHistory[0];
  }

  /**
   * This function modifies the datastream in fedora while adding the parameters
   * needed to respect datastram locking and making sure that we keep the
   * internal class cache of the datastream up to date.
   */
  protected function modifyDatastream(array $args) {
    $versionable = $this->versionable;
    if (!$this->forceUpdate) {
      $args = array_merge($args, array('lastModifiedDate' => (string) $this->createdDate));
    }
    $this->datastreamInfo = parent::modifyDatastream($args);
    if ($this->datastreamHistory !== NULL) {
      if ($versionable) {
        array_unshift($this->datastreamHistory, $this->datastreamInfo);
      }
      else {
        $this->datastreamHistory[0] = $this->datastreamInfo;
      }
    }
    $this->parent->refresh();
  }

  /**
   * @see AbstractDatastream::controlGroup
   */
  protected function controlGroupMagicProperty($function, $value) {
    if (!isset($this->datastreamInfo['dsControlGroup'])) {
      $this->populateDatastreamInfo();
    }
    return parent::controlGroupMagicProperty($function, $value);
  }

  /**
   * @see AbstractDatastream::location
   */
  protected function locationMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        if (!isset($this->datastreamInfo['dsLocation'])) {
          $this->populateDatastreamInfo();
        }
        return $this->datastreamInfo['dsLocation'];
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly datastream->id property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @see AbstractDatastream::state
   */
  protected function stateMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        if (!isset($this->datastreamInfo['dsState'])) {
          $this->populateDatastreamInfo();
        }
        return $this->datastreamInfo['dsState'];
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
        $state = $this->validateState($value);
        if ($state !== FALSE) {
          $this->modifyDatastream(array('dsState' => $state));
        }
        else {
          trigger_error("$value is not a valid value for the datastream->state property.", E_USER_WARNING);
        }
        break;

      case 'unset':
        trigger_error("Cannot unset the required datastream->state property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @see AbstractDatastream::label
   */
  protected function labelMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        if (!isset($this->datastreamInfo['dsLabel'])) {
          $this->populateDatastreamInfo();
        }
        return $this->datastreamInfo['dsLabel'];
        break;

      case 'isset':
        if (!isset($this->datastreamInfo['dsLabel'])) {
          $this->populateDatastreamInfo();
        }
        return $this->isDatastreamProperySet($this->datastreamInfo['dsLabel'], '');
        break;

      case 'set':
        $this->modifyDatastream(array('dsLabel' => function_exists('mb_substr') ? mb_substr($value, 0, 255) : substr($value, 0, 255)));
        break;

      case 'unset':
        $this->modifyDatastream(array('dsLabel' => ''));
        break;
    }
  }

  /**
   * @see AbstractDatastream::versionable
   */
  protected function versionableMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        if (!isset($this->datastreamInfo['dsVersionable'])) {
          $this->populateDatastreamInfo();
        }
        // Convert to a boolean.
        $versionable = $this->datastreamInfo['dsVersionable'] == 'true' ? TRUE : FALSE;
        return $versionable;
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
        if ($this->validateVersionable($value)) {
          $this->modifyDatastream(array('versionable' => $value));
        }
        else {
          trigger_error("Datastream->versionable must be a boolean.", E_USER_WARNING);
        }
        break;

      case 'unset':
        trigger_error("Cannot unset the required datastream->versionable property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @see AbstractDatastream::mimetype
   */
  protected function mimetypeMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        if (!isset($this->datastreamInfo['dsMIME'])) {
          $this->populateDatastreamInfo();
        }
        return $this->datastreamInfo['dsMIME'];
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
        if ($this->validateMimetype($value)) {
          $this->modifyDatastream(array('mimeType' => $value));
        }
        else {
          trigger_error("Invalid mimetype.", E_USER_WARNING);
        }
        break;

      case 'unset':
        trigger_error("Cannot unset the required datastream->mimetype property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @see AbstractDatastream::format
   */
  protected function formatMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        if (!isset($this->datastreamInfo['dsFormatURI'])) {
          $this->populateDatastreamInfo();
        }
        return $this->datastreamInfo['dsFormatURI'];
        break;

      case 'isset':
        if (!isset($this->datastreamInfo['dsFormatURI'])) {
          $this->populateDatastreamInfo();
        }
        return $this->isDatastreamProperySet($this->datastreamInfo['dsFormatURI'], '');
        break;

      case 'set':
        $this->modifyDatastream(array('formatURI' => $value));
        break;

      case 'unset':
        $this->modifyDatastream(array('formatURI' => ''));
        break;
    }
  }

  /**
   * @see AbstractDatastream::size
   */
  protected function sizeMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        if (!isset($this->datastreamInfo['dsSize'])) {
          $this->populateDatastreamInfo();
        }
        return $this->datastreamInfo['dsSize'];
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly datastream->size property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @see AbstractDatastream::checksum
   * @todo maybe add functionality to set it to auto
   */
  protected function checksumMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        if (!isset($this->datastreamInfo['dsChecksum'])) {
          $this->populateDatastreamInfo();
        }
        return $this->datastreamInfo['dsChecksum'];
        break;

      case 'isset':
        if (!isset($this->datastreamInfo['dsChecksum'])) {
          $this->populateDatastreamInfo();
        }
        return $this->isDatastreamProperySet($this->datastreamInfo['dsChecksum'], 'none');
        break;

      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly datastream->checksum property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @see AbstractDatastream::checksumType
   */
  protected function checksumTypeMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        if (!isset($this->datastreamInfo['dsChecksumType'])) {
          $this->populateDatastreamInfo();
        }
        return $this->datastreamInfo['dsChecksumType'];
        break;

      case 'isset':
        if (!isset($this->datastreamInfo['dsChecksumType'])) {
          $this->populateDatastreamInfo();
        }
        return $this->isDatastreamProperySet($this->datastreamInfo['dsChecksumType'], 'DISABLED');
        break;

      case 'set':
        $type = $this->validateChecksumType($value);
        if ($type) {
          $this->modifyDatastream(array('checksumType' => $type));
        }
        else {
          trigger_error("$value is not a valid value for the datastream->checksumType property.", E_USER_WARNING);
        }
        break;

      case 'unset':
        $this->modifyDatastream(array('checksumType' => 'DISABLED'));
        break;
    }
  }

  /**
   * @see AbstractDatastream::createdDate
   */
  protected function createdDateMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        if (!isset($this->datastreamInfo['dsCreateDate'])) {
          $this->populateDatastreamInfo();
        }
        return new FedoraDate($this->datastreamInfo['dsCreateDate']);
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly datastream->createdDate property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @see AbstractDatastream::content
   * @todo We should perhaps cache this? depending on size?
   */
  protected function contentMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->getDatastreamContent();
        break;

      case 'isset':
        return $this->isDatastreamProperySet($this->getDatastreamContent(), '');
        break;

      case 'set':
        if ($this->controlGroup == 'M' || $this->controlGroup == 'X') {
          $this->modifyDatastream(array('dsString' => $value));
        }
        else {
          trigger_error("Cannot set content of a {$this->controlGroup} datastream, please use datastream->url.", E_USER_WARNING);
        }
        break;

      case 'unset':
        if ($this->controlGroup == 'M' || $this->controlGroup == 'X') {
          $this->modifyDatastream(array('dsString' => ''));
        }
        else {
          trigger_error("Cannot unset content of a {$this->controlGroup} datastream, please use datastream->url.", E_USER_WARNING);
        }
        break;
    }
  }

  /**
   * @see AbstractDatastream::url
   */
  protected function urlMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        if (!isset($this->datastreamInfo['dsLocation'])) {
          $this->populateDatastreamInfo();
        }
        if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
          return $this->datastreamInfo['dsLocation'];
        }
        else {
          trigger_error("Datastream->url property is undefined for a {$this->controlGroup} datastream.", E_USER_WARNING);
          return NULL;
        }
        break;

      case 'isset':
        if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
          return TRUE;
        }
        else {
          return FALSE;
        }
        break;

      case 'set':
        if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
          $this->modifyDatastream(array('dsLocation' => $value));
        }
        else {
          trigger_error("Cannot set url of a {$this->controlGroup} datastream, please use datastream->content.", E_USER_WARNING);
        }
        break;

      case 'unset':
        trigger_error("Cannot unset the required datastream->url property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @see AbstractDatastream::logMessage
   */
  protected function logMessageMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        if (!isset($this->datastreamInfo['dsLogMessage'])) {
          $this->populateDatastreamInfo();
        }
        return $this->datastreamInfo['dsLogMessage'];
        break;

      case 'isset':
        if (!isset($this->datastreamInfo['dsLogMessage'])) {
          $this->populateDatastreamInfo();
        }
        return $this->isDatastreamProperySet($this->datastreamInfo['dsLogMessage'], '');
        break;

      case 'set':
        $this->modifyDatastream(array('dsLogMessage' => $value));
        break;

      case 'unset':
        $this->modifyDatastream(array('dsLogMessage' => ''));
        break;
    }
  }

  /**
   * @see AbstractDatastream::setContentFromFile
   */
  public function setContentFromFile($file) {
    if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
      trigger_error("Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.", E_USER_WARNING);
      return;
    }
    $this->modifyDatastream(array('dsFile' => $file));
  }

  /**
   * @see AbstractDatastream::setContentFromUrl
   *
   * @param string $url
   *   Https (SSL) URL's will cause this to fail.
   */
  public function setContentFromUrl($url) {
    if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
      trigger_error("Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.", E_USER_WARNING);
      return;
    }
    $this->modifyDatastream(array('dsLocation' => $url));
  }

  /**
   * @see AbstractDatastream::setContentFromString
   */
  public function setContentFromString($string) {
    if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
      trigger_error("Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.", E_USER_WARNING);
      return;
    }
    $this->modifyDatastream(array('dsString' => $string));
  }

  /**
   * @see Countable::count
   */
  public function count() {
    $this->populateDatastreamHistory();
    return count($this->datastreamHistory);
  }

  /**
   * @see ArrayAccess::offsetExists
   */
  public function offsetExists($offset) {
    $this->populateDatastreamHistory();
    return isset($this->datastreamHistory[$offset]);
  }

  /**
   * @see ArrayAccess::offsetGet
   */
  public function offsetGet($offset) {
    $this->populateDatastreamHistory();
    return new $this->fedoraDatastreamVersionClass($this->id, $this->datastreamHistory[$offset], $this, $this->parent, $this->repository);
  }

  /**
   * @see ArrayAccess::offsetSet
   */
  public function offsetSet($offset, $value) {
    trigger_error("Datastream versions are read only and cannot be set.", E_USER_WARNING);
  }

  /**
   * @see ArrayAccess::offsetUnset
   */
  public function offsetUnset($offset) {
    $this->populateDatastreamHistory();
    if ($this->count() == 1) {
      trigger_error("Cannot unset the last version of a datastream. To delete the datastream use the object->purgeDatastream() function.", E_USER_WARNING);
      return;
    }
    $this->purgeDatastream($this->datastreamHistory[$offset]['dsCreateDate']);
    unset($this->datastreamHistory[$offset]);
    $this->datastreamHistory = array_values($this->datastreamHistory);
    $this->datastreamInfo = $this->datastreamHistory[0];
  }

  /**
   * IteratorAggregate::getIterator()
   */
  public function getIterator() {
    $this->populateDatastreamHistory();
    $history = array();
    foreach ($this->datastreamHistory as $key => $value) {
      $history[$key] = new $this->fedoraDatastreamVersionClass($this->id, $value, $this, $this->parent, $this->repository);
    }
    return new ArrayIterator($history);
  }

  /**
   * @see AbstractDatastream::getContent()
   */
  public function getContent($file) {
    return $this->getDatastreamContent(NULL, $file);
  }
}
