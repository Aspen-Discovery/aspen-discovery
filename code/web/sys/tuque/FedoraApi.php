<?php
/**
 * @file
 * The RAW API wrappers for the Fedora interface.
 *
 * This file currently contains fairly raw wrappers around the Fedora REST
 * interface. These could also be reinmplemented to use for example the Fedora
 * SOAP interface. If there are version specific modifications to be made for
 * Fedora, this is the place to make them.
 */

require_once 'RepositoryException.php';
require_once 'RepositoryConnection.php';

/**
 * This is a simple class that brings FedoraApiM and FedoraApiA together.
 */
class FedoraApi {

  /**
   * Fedora APIA Class
   * @var FedoraApiA
   */
  public $a;

  /**
   * Fedora APIM Class
   * @var FedoraApiM
   */
  public $m;

  public $connection;

  /**
   * Constructor for the FedoraApi object.
   *
   * @param RepositoryConnection $connection
   *   (Optional) If one isn't provided a default one will be used.
   * @param FedoraApiSerializer $serializer
   *   (Optional) If one isn't provided a default will be used.
   */
  public function __construct(RepositoryConnection $connection = NULL, FedoraApiSerializer $serializer = NULL) {
    if (!$connection) {
      $connection = new RepositoryConnection();
    }

    if (!$serializer) {
      $serializer = new FedoraApiSerializer();
    }

    $this->a = new FedoraApiA($connection, $serializer);
    $this->m = new FedoraApiM($connection, $serializer);

    $this->connection = $connection;
  }
}

/**
 * This class implements the Fedora API-A interface. This is a light wrapper
 * around the Fedora interface. Very little attempt is put into putting things
 * into native PHP datastructures.
 *
 * See this page for more information:
 * https://wiki.duraspace.org/display/FEDORA35/REST+API
 */
class FedoraApiA {

  protected $connection;
  protected $serializer;

  /**
   * Constructor for the new FedoraApiA object.
   *
   * @param RepositoryConnection $connection
   *   Takes the Respository Connection object for the Respository this API
   *   should connect to.
   * @param FedoraApiSerializer $serializer
   *   Takes the serializer object to that will be used to serialze the XML
   *   Fedora returns.
   */
  public function __construct(RepositoryConnection $connection, FedoraApiSerializer $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }

  /**
   * Returns basic information about the Repository.
   *
   * This is listed as an unimplemented function in the official API for Fedora.
   * However other libraries connecting to the Fedora REST interaface use this
   * so we are including it here. It may change in the future.
   *
   * @throws RepositoryException
   *
   * @return array()
   *   An array describing the repository.
   *   @code
   *   Array
   *   (
   *       [repositoryName] => Fedora Repository
   *       [repositoryBaseURL] => http://localhost:8080/fedora
   *       [repositoryVersion] => 3.4.1
   *       [repositoryPID] => Array
   *           (
   *               [PID-namespaceIdentifier] => changeme
   *               [PID-delimiter] => :
   *               [PID-sample] => changeme:100
   *               [retainPID] => *
   *           )
   *
   *       [repositoryOAI-identifier] => Array
   *           (
   *               [OAI-namespaceIdentifier] => example.org
   *               [OAI-delimiter] => :
   *               [OAI-sample] => oai:example.org:changeme:100
   *           )
   *
   *       [sampleSearch-URL] => http://localhost:8080/fedora/objects
   *       [sampleAccess-URL] => http://localhost:8080/fedora/objects/demo:5
   *       [sampleOAI-URL] => http://localhost:8080/fedora/oai?verb=Identify
   *       [adminEmail] => Array
   *           (
   *               [0] => bob@example.org
   *               [1] => sally@example.org
   *           )
   *
   *   )
   *   @endcode
   */
  public function describeRepository() {
    // This is weird and undocumented, but its what the web client does.
    $request = "/describe";
    $separator = '?';

    $this->connection->addParam($request, $separator, 'xml', 'true');

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->describeRepository($response);
    return $response;
  }

  /**
   * Authenticate and provide information about a user's fedora attributes.
   *
   * Please note that calling this method
   * with an unauthenticated (i.e. anonymous) user will throw
   * an 'HttpConnectionException' with the message 'Unauthorized'.
   *
   * @return array()
   *   Returns an array containing user attributes (i.e. fedoraRole).
   *    @code
   *    Array
   *    (
   *        [fedoraRole] => Array
   *            (
   *                [0] => authenticated user
   *            )
   *        [role] => Array
   *            (
   *                [0] => authenticated user
   *            )
   *    )
   *    @endcode
   */
  public function userAttributes() {
    $request = "/user";
    $separator = '?';

    $this->connection->addParam($request, $separator, 'xml', 'true');

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->userAttributes($response);
    return $response;
  }

  /**
   * Query fedora to return a list of objects.
   *
   * @param string $type
   *   The type of query. Decides the format of the next parameter. Valid
   *   options are:
   *   - query: specific query on certain fields
   *   - terms: search in any field
   * @param string $query
   *   The format of this parameter depends on what was passed to type. The
   *   formats are:
   *   - query: A sequence of space-separated conditions. A condition consists
   *     of a metadata element name followed directly by an operator, followed
   *     directly by a value. Valid element names are (pid, label, state,
   *     ownerId, cDate, mDate, dcmDate, title, creator, subject, description,
   *     publisher, contributor, date, type, format, identifier, source,
   *     language, relation, coverage, rights). Valid operators are:
   *     contains (~), equals (=), greater than (>), less than (<), greater than
   *     or equals (>=), less than or equals (<=). The contains (~) operator
   *     may be used in combination with the ? and * wildcards to query for
   *     simple string patterns. Values may be any string. If the string
   *     contains a space, the value should begin and end with a single quote
   *     character ('). If all conditions are met for an object, the object is
   *     considered a match.
   *   - terms: A phrase represented as a sequence of characters (including the
   *     ? and * wildcards) for the search. If this sequence is found in any of
   *     the fields for an object, the object is considered a match.
   * @param int $max_results
   *   (optional) Default: 25. The maximum number of results that the server
   *   should provide at once.
   * @param array $display_fields
   *   (optional) Default: array('pid', 'title'). The fields to be returned as
   *   an indexed array. Valid element names are the same as the ones given for
   *   the query parameter.
   *
   * @throws RepositoryException
   *
   * @return array()
   *   The results are returned in an array key called 'results'. If there
   *   are more results that aren't returned then the search session information
   *   is contained in a key called 'session'. Note that it is possible for
   *   some display fields to be multivalued, such as identifier (DC allows
   *   multiple DC identifier results) in the case there are multiple results
   *   an array is returned instread of a string, this indexed array contains
   *   all of the values.
   *   @code
   *   Array
   *   (
   *      [session] => Array
   *          (
   *              [token] => 96b2604f040067645f45daf029062d6e
   *              [cursor] => 0
   *              [expirationDate] => 2012-03-07T14:28:24.886Z
   *          )
   *
   *      [results] => Array
   *          (
   *              [0] => Array
   *                  (
   *                      [pid] => islandora:collectionCModel
   *                      [title] => Islandora Collection Content Model
   *                      [identifier] => Contents of DC:Identifier
   *                  )
   *
   *              [1] => Array
   *                  (
   *                      [pid] => islandora:testCModel
   *                      [title] => Test content model for Ari
   *                      [identifier] => Array
   *                          (
   *                              [0] => Contents of first DC:Identifier
   *                              [1] => Contents of seconds DC:Identifier
   *                          )
   *
   *                  )
   *
   *          )
   *
   *    )
   *    @endcode
   */
  public function findObjects($type, $query, $max_results = NULL, $display_fields = array('pid', 'title')) {
    $request = "/objects";
    $separator = '?';

    $this->connection->addParam($request, $separator, 'resultFormat', 'xml');

    switch ($type) {
      case 'terms':
        $this->connection->addParam($request, $separator, 'terms', $query);
        break;

      case 'query':
        $this->connection->addParam($request, $separator, 'query', $query);
        break;

      default:
        throw new RepositoryBadArguementException('$type must be either: terms or query.');
    }

    $this->connection->addParam($request, $separator, 'maxResults', $max_results);

    if (is_array($display_fields)) {
      foreach ($display_fields as $display) {
        $this->connection->addParam($request, $separator, $display, 'true');
      }
    }

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->findObjects($response);
    return $response;
  }

  /**
   * Returns next set of objects when given session key.
   *
   * @param string $session_token
   *   Session token returned from previous search call.
   *
   * @throws RespositoryException
   *
   * @return array()
   *   The result format is the same as findObjects.
   *
   * @see FedoraApiA::findObjects
   */
  public function resumeFindObjects($session_token) {
    $session_token = urlencode($session_token);
    $request = "/objects";
    $separator = '?';

    $this->connection->addParam($request, $separator, 'resultFormat', 'xml');
    $this->connection->addParam($request, $separator, 'sessionToken', $session_token);

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->resumeFindObjects($response);
    return $response;
  }

  /**
   * Get the default dissemination of a datastream. (Get the contents).
   *
   * @param String $pid
   *   Persistent identifier of the digital object.
   * @param String $dsid
   *   Datastream identifier.
   * @param array $as_of_date_time
   *   (optional) Indicates that the result should be relative to the
   *     digital object as it existed at the given date and time. Defaults to
   *     the most recent version.
   * @param array $file
   *   (optional) A file to retrieve the dissemination into.
   *
   * @throws RespositoryException
   *
   * @return string
   *   The response from Fedora with the contents of the datastream if file
   *   isn't set. Returns TRUE if the file parameter is passed.
   */
  public function getDatastreamDissemination($pid, $dsid, $as_of_date_time = NULL, $file = NULL) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);
    $separator = '?';

    $request = "/objects/$pid/datastreams/$dsid/content";

    $this->connection->addParam($request, $separator, 'asOfDateTime', $as_of_date_time);

    $response = $this->connection->getRequest($request, FALSE, $file);
    $response = $this->serializer->getDatastreamDissemination($response, $file);
    return $response;
  }

  /**
   * Get a datastream dissemination from Fedora.
   *
   * @param String $pid
   *   Persistent identifier of the digital object.
   * @param String $sdef_pid
   *   Persistent identifier of the sDef defining the methods.
   * @param String $method
   *   Method to invoke.
   * @param String $method_parameters
   *   A key-value paired array of parameters required by the method.
   *
   * @throws RespositoryException
   *
   * @return string
   *   The response from Fedora.
   */
  public function getDissemination($pid, $sdef_pid, $method, $method_parameters = NULL) {
    $pid = urlencode($pid);
    $sdef_pid = urldecode($sdef_pid);
    $method = urlencode($method);

    $request = "/objects/$pid/methods/$sdef_pid/$method";
    $separator = '?';

    if (isset($method_parameters) && is_array($method_parameters)) {
      foreach ($method_parameters as $key => $value) {
        $this->connection->addParam($request, $separator, $key, $value);
      }
    }

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getDissemination($response);
    return $response;
  }

  /**
   * Get the change history for the object.
   *
   * @param String $pid
   *   Persistent identifier of the digital object.
   *
   * @throws RepositoryException
   *
   * @return array()
   *   An array containing the different revisions of the object.
   *   @code
   *   Array
   *   (
   *       [0] => 2011-07-08T18:01:40.384Z
   *       [1] => 2011-07-08T18:01:40.464Z
   *       [2] => 2011-07-08T18:01:40.552Z
   *       [3] => 2011-07-08T18:01:40.694Z
   *       [4] => 2012-02-22T15:07:15.305Z
   *       [5] => 2012-02-29T14:20:28.857Z
   *       [6] => 2012-02-29T14:22:18.239Z
   *       [7] => 2012-02-29T14:22:46.545Z
   *       [8] => 2012-02-29T20:52:33.069Z
   *   )
   *   @endcode
   */
  public function getObjectHistory($pid) {
    $pid = urlencode($pid);

    $request = "/objects/$pid/versions";
    $separator = '?';
    $this->connection->addParam($request, $separator, 'format', 'xml');

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getObjectHistory($response);
    return $response;
  }

  /**
   * Implements the getObjectProfile Fedora API-A method.
   *
   * @param String $pid
   *   Persistent identifier of the digital object.
   * @param String $as_of_date_time
   *   (Optional) Indicates that the result should be relative to the digital
   *   object as it existed on the given date. Date Format: yyyy-MM-dd or
   *   yyyy-MM-ddTHH:mm:ssZ
   *
   * @throws RepositoryException
   *
   * @return array()
   *   Returns information about the digital object.
   *   @code
   *   Array
   *   (
   *       [objLabel] => Islandora strict PDF content model
   *       [objOwnerId] => fedoraAdminnnn
   *       [objModels] => Array
   *           (
   *               [0] => info:fedora/fedora-system:ContentModel-3.0
   *               [1] => info:fedora/fedora-system:FedoraObject-3.0
   *           )
   *
   *       [objCreateDate] => 2011-07-08T18:01:40.384Z
   *       [objLastModDate] => 2012-03-02T20:50:13.534Z
   *       [objDissIndexViewURL] => http://localhost:8080/fedora/objects/
   *         islandora%3Astrict_pdf/methods/fedora-system%3A3/viewMethodIndex
   *       [objItemIndexViewURL] => http://localhost:8080/fedora/objects/
   *         islandora%3Astrict_pdf/methods/fedora-system%3A3/viewItemIndex
   *       [objState] => A
   *   )
   *   @endcode
   */
  public function getObjectProfile($pid, $as_of_date_time = NULL) {
    $pid = urlencode($pid);

    $request = "/objects/{$pid}";
    $separator = '?';

    $this->connection->addParam($request, $separator, 'format', 'xml');
    $this->connection->addParam($request, $separator, 'asOfDateTime', $as_of_date_time);

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getObjectProfile($response);
    return $response;
  }

  /**
   * List all the datastreams that are associated with this PID.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param string $as_of_date_time
   *   (optional) Indicates that the result should be relative to the digital
   *   object as it existed on the given date. Date Format: yyyy-MM-dd or
   *   yyyy-MM-ddTHH:mm:ssZ.
   *
   * @throws RepositoryException
   *
   * @return array()
   *   An associative array with the dsid of the datastreams as the key and
   *   the mimetype and label as the value.
   *   @code
   *   Array
   *   (
   *       [DC] => Array
   *           (
   *               [label] => Dublin Core Record for this object
   *               [mimetype] => text/xml
   *           )
   *
   *       [RELS-EXT] => Array
   *           (
   *               [label] => Fedora Object-to-Object Relationship Metadata
   *               [mimetype] => text/xml
   *           )
   *
   *       [ISLANDORACM] => Array
   *           (
   *               [label] => ISLANDORACM
   *               [mimetype] => text/xml
   *           )
   *
   *   )
   *   @endcode
   */
  public function listDatastreams($pid, $as_of_date_time = NULL) {
    $pid = urlencode($pid);

    $request = "/objects/{$pid}/datastreams";
    $separator = '?';

    $this->connection->addParam($request, $separator, 'format', 'xml');
    $this->connection->addParam($request, $separator, 'asOfDateTime', $as_of_date_time);

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->listDatastreams($response);
    return $response;
  }

  /**
   * Implements the listMethods Fedora API-A method.
   *
   * @param String $pid
   *   Persistent identifier of the digital object.
   * @param String $sdef_pid
   *   (Optional) Persistent identifier of the SDef defining the methods.
   * @param String $as_of_date_time
   *   (Optional) Indicates that the result should be relative to the digital
   *   object as it existed on the given date. Date Format: yyyy-MM-dd or
   *   yyyy-MM-ddTHH:mm:ssZ.
   *
   * @throws RepositoryException
   *
   * @return array()
   *   An array containing data about the methods that can be called. The result
   *   array is an associative array where the sdef pid is the key and the value
   *   is a indexed array of methods.
   *   @code
   *   Array
   *   (
   *       [ilives:viewerSdef] => Array
   *           (
   *               [0] => getViewer
   *           )
   *
   *       [ilives:jp2Sdef] => Array
   *           (
   *               [0] => getMetadata
   *               [1] => getRegion
   *           )
   *
   *       [fedora-system:3] => Array
   *          (
   *               [0] => viewObjectProfile
   *               [1] => viewMethodIndex
   *               [2] => viewItemIndex
   *               [3] => viewDublinCore
   *           )
   *
   *   )
   *   @endcode
   */
  public function listMethods($pid, $sdef_pid = '', $as_of_date_time = NULL) {
    $pid = urlencode($pid);
    $sdef_pid = urlencode($sdef_pid);

    $request = "/objects/{$pid}/methods/{$sdef_pid}";
    $separator = '?';

    $this->connection->addParam($request, $separator, 'format', 'xml');
    $this->connection->addParam($request, $separator, 'asOfDateTime', $as_of_date_time);

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->listMethods($response);
    return $response;
  }

}

/**
 * This class implements the Fedora API-M interface. This is a light wrapper
 * around the Fedora interface. Very little attempt is put into putting things
 * into native PHP datastructures.
 *
 * See this page for more information:
 * https://wiki.duraspace.org/display/FEDORA35/REST+API
 */
class FedoraApiM {

  /**
   * Constructor for the new FedoraApiM object.
   *
   * @param RepositoryConnection $connection
   *   Takes the Respository Connection object for the Respository this API
   *   should connect to.
   * @param FedoraApiSerializer $serializer
   *   Takes the serializer object to that will be used to serialze the XML
   *   Fedora returns.
   */
  public function __construct(RepositoryConnection $connection, FedoraApiSerializer $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }

  /**
   * Add a new datastream to a fedora object.
   *
   * The datastreams are sent to Fedora using a multipart post if a string
   * or file is provided otherwise Fedora will go out and fetch the URL
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param string $dsid
   *   Datastream identifier.
   * @param string $type
   *   This parameter tells the function what type of arguement is given for
   *   file. It must be one of:
   *   - string: The datastream is passed as a string.
   *   - file: The datastream is contained in a file.
   *   - url: The datastream is located at a URL, which is passed as a string.
   *     this is the only option that can be used for R and E type datastreams.
   * @param string $file
   *   This parameter depends on what is selected for $type.
   *   - string: A string containing the datastream.
   *   - file: A string containing the file name that contains the datastream.
   *     The file name must be a full path.
   *   - url: A string containing the publically accessable URL that the
   *     datastream is located at.
   * @param array() $params
   *   (optional) An array that can have one or more of the following elements:
   *   - controlGroup: one of "X", "M", "R", or "E" (Inline *X*ML, *M*anaged
   *     Content, *R*edirect, or *E*xternal Referenced). Default: X.
   *   - altIDs: alternate identifiers for the datastream. A space seperated
   *     list of alternate identifiers for the datastream.
   *   - dsLabel: the label for the datastream.
   *   - versionable: enable versioning of the datastream (boolean).
   *   - dsState: one of "A", "I", "D" (*A*ctive, *I*nactive, *D*eleted).
   *   - formatURI: the format URI of the datastream.
   *   - checksumType: the algorithm used to compute the checksum. One of
   *     DEFAULT, DISABLED, MD5, SHA-1, SHA-256, SHA-384, SHA-512.
   *   - checksum: the value of the checksum represented as a hexadecimal
   *     string.
   *   - mimeType: the MIME type of the content being added, this overrides the
   *     Content-Type request header.
   *   - logMessage: a message describing the activity being performed.
   *
   * @throws RepositoryException
   *
   * @return array()
   *   Returns an array describing the new datastream. This is the same array
   *   returned by getDatastream. This may also contain an dsAltID key, that
   *   contains any alternate ids if any are specified.
   *   @code
   *   Array
   *   (
   *       [dsLabel] =>
   *       [dsVersionID] => test.3
   *       [dsCreateDate] => 2012-03-07T18:03:38.679Z
   *       [dsState] => A
   *       [dsMIME] => text/xml
   *       [dsFormatURI] =>
   *       [dsControlGroup] => M
   *       [dsSize] => 22
   *       [dsVersionable] => true
   *       [dsInfoType] =>
   *       [dsLocation] => islandora:strict_pdf+test+test.3
   *       [dsLocationType] => INTERNAL_ID
   *       [dsChecksumType] => DISABLED
   *       [dsChecksum] => none
   *       [dsLogMessage] =>
   *   )
   *   @endcode
   *
   * @see FedoraApiM::getDatastream
   */
  public function addDatastream($pid, $dsid, $type, $file, $params) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);

    $request = "/objects/$pid/datastreams/$dsid";
    $separator = '?';

    switch (strtolower($type)) {
      case 'file':
      case 'string':
        break;

      case 'url':
        $this->connection->addParam($request, $separator, 'dsLocation', $file);
        $type = 'none';
        break;

      default:
        throw new RepositoryBadArguementException("Type must be one of: file, string, url. ($type)");
      break;
    }

    $this->connection->addParamArray($request, $separator, $params, 'controlGroup');
    $this->connection->addParamArray($request, $separator, $params, 'altIDs');
    $this->connection->addParamArray($request, $separator, $params, 'dsLabel');
    $this->connection->addParamArray($request, $separator, $params, 'versionable');
    $this->connection->addParamArray($request, $separator, $params, 'dsState');
    $this->connection->addParamArray($request, $separator, $params, 'formatURI');
    $this->connection->addParamArray($request, $separator, $params, 'checksumType');
    $this->connection->addParamArray($request, $separator, $params, 'checksum');
    $this->connection->addParamArray($request, $separator, $params, 'mimeType');
    $this->connection->addParamArray($request, $separator, $params, 'logMessage');

    $response = $this->connection->postRequest($request, $type, $file);
    $response = $this->serializer->addDatastream($response);
    return $response;
  }

  /**
   * Add a RDF relationship to a Fedora object.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param array $relationship
   *   An array containing the subject, predicate and object for the
   *   relationship.
   *   - subject: (optional) Subject of the relationship. Either a URI for the
   *     object or one of its datastreams. If none is given then the URI for
   *     the current object is used.
   *   - predicate: Predicate of the relationship.
   *   - object: Object of the relationship.
   * @param boolean $is_literal
   *   true if the object of the relationship is a literal, false if it is a URI
   * @param string $datatype
   *   (optional) if the object is a literal, the datatype of the literal.
   *
   * @throws RepositoryException
   *
   * @see FedoraApiM::getRelationships
   * @see FedoraApiM::purgeRelationships
   */
  public function addRelationship($pid, $relationship, $is_literal, $datatype = NULL) {
    if (!isset($relationship['predicate'])) {
      throw new RepositoryBadArguementException('Relationship array must contain a predicate element');
    }
    if (!isset($relationship['object'])) {
      throw new RepositoryBadArguementException('Relationship array must contain a object element');
    }

    $pid = urlencode($pid);
    $request = "/objects/$pid/relationships/new";
    $separator = '?';

    $this->connection->addParamArray($request, $separator, $relationship, 'subject');
    $this->connection->addParamArray($request, $separator, $relationship, 'predicate');
    $this->connection->addParamArray($request, $separator, $relationship, 'object');
    $this->connection->addParam($request, $separator, 'isLiteral', $is_literal);
    $this->connection->addParam($request, $separator, 'datatype', $datatype);

    $response = $this->connection->postRequest($request);
    $response = $this->serializer->addRelationship($response);
  }

  /**
   * Export a Fedora object with the given PID.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param array $params
   *   (optional) An array that can have one or more of the following elements:
   *   - format: The XML format to export. One of
   *     info:fedora/fedora-system:FOXML-1.1 (default),
   *     info:fedora/fedora-system:FOXML-1.0,
   *     info:fedora/fedora-system:METSFedoraExt-1.1,
   *     info:fedora/fedora-system:METSFedoraExt-1.0,
   *     info:fedora/fedora-system:ATOM-1.1,
   *     info:fedora/fedora-system:ATOMZip-1.1
   *   - context: The export context, which determines how datastream URLs and
   *     content are represented. Options: public (default), migrate, archive.
   *   - encoding: The preferred encoding of the exported XML.
   *
   * @throws RepositoryException
   *
   * @return string
   *   A string containing the requested XML.
   */
  public function export($pid, $params = array()) {
    $pid = urlencode($pid);
    $request = "/objects/$pid/export";
    $separator = '?';

    $this->connection->addParamArray($request, $separator, $params, 'context');
    $this->connection->addParamArray($request, $separator, $params, 'format');
    $this->connection->addParamArray($request, $separator, $params, 'encoding');

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->export($response);
    return $response;
  }

  /**
   * Returns information about the datastream.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param string $dsid
   *   Datastream identifier.
   * @param array $params
   *   (optional) An array that can have one or more of the following elements:
   *   - asOfDateTime: Indicates that the result should be relative to the
   *     digital object as it existed on the given date.
   *   - validateChecksum: verifies that the Datastream content has not changed
   *     since the checksum was initially computed.
   *
   * @throws RepositoryException
   *
   * @return array()
   *   An array containing information about the datastream. This may also
   *   contains a key dsAltID which contains alternate ids if any are specified.
   *   @code
   *   Array
   *   (
   *       [dsLabel] =>
   *       [dsVersionID] => test.3
   *       [dsCreateDate] => 2012-03-07T18:03:38.679Z
   *       [dsState] => A
   *       [dsMIME] => text/xml
   *       [dsFormatURI] =>
   *       [dsControlGroup] => M
   *       [dsSize] => 22
   *       [dsVersionable] => true
   *       [dsInfoType] =>
   *       [dsLocation] => islandora:strict_pdf+test+test.3
   *       [dsLocationType] => INTERNAL_ID
   *       [dsChecksumType] => DISABLED
   *       [dsChecksum] => none
   *       [dsChecksumValid] => true
   *   )
   *   @endcode
   */
  public function getDatastream($pid, $dsid, $params = array()) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);

    $request = "/objects/$pid/datastreams/$dsid";
    $separator = '?';

    $this->connection->addParam($request, $separator, 'format', 'xml');
    $this->connection->addParamArray($request, $separator, $params, 'asOfDateTime');
    $this->connection->addParamArray($request, $separator, $params, 'validateChecksum');

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getDatastream($response);
    return $response;
  }

  /**
   * Get information on the different datastream versions.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param string $dsid
   *   Datastream identifier.
   *
   * @throws RepositoryException
   *
   * @return array
   *   Returns a indexed array with the same keys as getDatastream.
   *   @code
   *   Array
   *   (
   *       [0] => Array
   *           (
   *               [dsLabel] =>
   *               [dsVersionID] => test.3
   *               [dsCreateDate] => 2012-03-07T18:03:38.679Z
   *               [dsState] => A
   *               [dsMIME] => text/xml
   *               [dsFormatURI] =>
   *               [dsControlGroup] => M
   *               [dsSize] => 22
   *               [dsVersionable] => true
   *               [dsInfoType] =>
   *               [dsLocation] => islandora:strict_pdf+test+test.3
   *               [dsLocationType] => INTERNAL_ID
   *               [dsChecksumType] => DISABLED
   *               [dsChecksum] => none
   *           )
   *
   *       [1] => Array
   *           (
   *               [dsLabel] =>
   *               [dsVersionID] => test.2
   *               [dsCreateDate] => 2012-03-07T18:03:13.722Z
   *               [dsState] => A
   *               [dsMIME] => text/xml
   *               [dsFormatURI] =>
   *               [dsControlGroup] => M
   *               [dsSize] => 22
   *               [dsVersionable] => true
   *               [dsInfoType] =>
   *               [dsLocation] => islandora:strict_pdf+test+test.2
   *               [dsLocationType] => INTERNAL_ID
   *               [dsChecksumType] => DISABLED
   *               [dsChecksum] => none
   *           )
   *
   *   )
   *   @endcode
   *
   * @see FedoraApiM::getDatastream
   */
  public function getDatastreamHistory($pid, $dsid) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);

    $request = "/objects/{$pid}/datastreams/{$dsid}/history";
    $separator = '?';
    $this->connection->addParam($request, $separator, 'format', 'xml');

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getDatastreamHistory($response);

    return $response;
  }

  /**
   * Get a new unused PID.
   *
   * @param string $namespace
   *   The namespace to get the PID in. This defaults to default namespace of
   *   the repository. This should not contain the PID seperator, for example
   *   it should be islandora not islandora:.
   * @param int $numpids
   *   The number of pids being requested.
   *
   * @throws RepositoryException
   *
   * @return array/string
   *   If one pid is requested it is returned as a string. If multiple pids are
   *   requested they they are returned in an array containg strings.
   *   @code
   *   Array
   *   (
   *       [0] => test:7
   *       [1] => test:8
   *   )
   *   @endcode
   */
  public function getNextPid($namespace = NULL, $numpids = NULL) {
    $request = "/objects/nextPID";
    $separator = '?';

    $this->connection->addParam($request, $separator, 'format', 'xml');
    $this->connection->addParam($request, $separator, 'namespace', $namespace);
    $this->connection->addParam($request, $separator, 'numPIDs', $numpids);

    $response = $this->connection->postRequest($request, 'string', '');
    $response = $this->serializer->getNextPid($response);
    return $response;
  }

  /**
   * Get the Fedora Objects XML (Foxml).
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   *
   * @throws RepositoryException
   *
   * @return string
   *   A string containing the objects foxml
   *
   * @see FedoraApiM::export
   */
  public function getObjectXml($pid) {
    $pid = urlencode($pid);

    $request = "/objects/{$pid}/objectXML";
    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getObjectXml($response);
    return $response;
  }

  /**
   * Query relationships for a particular fedora object.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param array $relationship
   *   (Optional) An array defining the relationship:
   *   - subject: subject of the relationship(s). Either a URI for the object
   *     or one of its datastreams. defaults to the URI of the object.
   *   - predicate: predicate of the relationship(s), if missing returns all
   *     predicates.
   *
   * @throws RepositoryException
   *
   * @return array
   *   An indexed array with all the relationships.
   *   @code
   *   Array
   *   (
   *       [0] => Array
   *           (
   *               [subject] => islandora:strict_pdf
   *               [predicate] => Array
   *                   (
   *                       [predicate] => hasModel
   *                       [uri] => info:fedora/fedora-system:def/model#
   *                       [alias] =>
   *                   )
   *
   *               [object] => Array
   *                   (
   *                       [literal] => FALSE
   *                       [value] => fedora-system:FedoraObject-3.0
   *                   )
   *
   *           )
   *
   *       [1] => Array
   *           (
   *               [subject] => islandora:strict_pdf
   *               [predicate] => Array
   *                   (
   *                       [predicate] => bar
   *                       [uri] => http://woot/foo#
   *                       [alias] =>
   *                   )
   *
   *               [object] => Array
   *                   (
   *                       [literal] => TRUE
   *                       [value] => thedude
   *                   )
   *
   *           )
   *
   *   )
   *   @endcode
   */
  public function getRelationships($pid, $relationship = array()) {
    $pid = urlencode($pid);

    $request = "/objects/$pid/relationships";
    $separator = "?";

    $this->connection->addParam($request, $separator, 'format', 'xml');
    $this->connection->addParamArray($request, $separator, $relationship, 'subject');
    $this->connection->addParamArray($request, $separator, $relationship, 'predicate');

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getRelationships($response);
    return $response;
  }

  /**
   * Create a new object in Fedora.
   *
   * This could be ingesting a XML file as a
   * string or a file. Executing this request with no XML file content will
   * result in the creation of a new, empty object (with either the specified
   * PID or a system-assigned PID). The new object will contain only a minimal
   * DC datastream specifying the dc:identifier of the object.
   *
   * @param array $params
   *   (optional) An array that can have one or more of the following elements:
   *   - pid: persistent identifier of the object to be created. If this is not
   *     supplied then either a new PID will be created for this object or the
   *     PID to be used is encoded in the XML included as the body of the
   *     request
   *   - string: The XML file defining the new object as a string
   *   - file: The XML file defining the new object as a string containing the
   *     full path to the XML file. This must not be used with the string
   *     parameter
   *   - label: the label of the new object
   *   - format: the XML format of the object to be ingested. One of
   *     info:fedora/fedora-system:FOXML-1.1,
   *     info:fedora/fedora-system:FOXML-1.0,
   *     info:fedora/fedora-system:METSFedoraExt-1.1,
   *     info:fedora/fedora-system:METSFedoraExt-1.0,
   *     info:fedora/fedora-system:ATOM-1.1,
   *     info:fedora/fedora-system:ATOMZip-1.1
   *   - encoding: 	the encoding of the XML to be ingested.  If this is
   *     specified, and given as anything other than UTF-8, you must ensure
   *     that the same encoding is declared in the XML.
   *   - namespace: The namespace to be used to create a PID for a new empty
   *     object: if a 'string' parameter is included with the request, the
   *     namespace parameter is ignored.
   *   - ownerId: the id of the user to be listed at the object owner.
   *   - logMessage: a message describing the activity being performed.
   *
   * @throws RepositoryException
   *
   * @return string
   *   The PID of the newly created object.
   *
   * @todo This function is a problem in Fedora < 3.5 where ownerId does not
   *   properly get set. https://jira.duraspace.org/browse/FCREPO-963. We should
   *   deal with this.
   */
  public function ingest($params = array()) {
    $request = "/objects/";
    $separator = '?';

    if (isset($params['pid'])) {
      $pid = urlencode($params['pid']);
      $request .= "$pid";
    }
    else {
      $request .= "new";
    }

    if (isset($params['string'])) {
      $type = 'string';
      $data = $params['string'];
      $content_type = 'text/xml';
    }
    elseif (isset($params['file'])) {
      $type = 'file';
      $data = $params['file'];
      $content_type = 'text/xml';
    }
    else {
      $type = 'none';
      $data = NULL;
      $content_type = NULL;
    }

    $this->connection->addParamArray($request, $separator, $params, 'label');
    $this->connection->addParamArray($request, $separator, $params, 'format');
    $this->connection->addParamArray($request, $separator, $params, 'encoding');
    $this->connection->addParamArray($request, $separator, $params, 'namespace');
    $this->connection->addParamArray($request, $separator, $params, 'ownerId');
    $this->connection->addParamArray($request, $separator, $params, 'logMessage');

    $response = $this->connection->postRequest($request, $type, $data, $content_type);
    $response = $this->serializer->ingest($response);
    return $response;
  }

  /**
   * Update a datastream's metadata, contents, or both.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param string $dsid
   *   Datastream identifier.
   * @param array $params
   *   (optional) An array that can have one or more of the following elements:
   *   - dsFile: String containing the full path to a file that will be used
   *     as the new contents of the datastream.
   *   - dsString: String containing the new contents of the datastream.
   *   - dsLocation: String containing a URL to fetch the new datastream from.
   *     Only ONE of dsFile, dsString or dsLocation should be used.
   *   - altIDs: 	alternate identifiers for the datastream. This is a space
   *     seperated string of alternate identifiers for the datastream.
   *   - dsLabel: 	the label for the datastream.
   *   - versionable: enable versioning of the datastream.
   *   - dsState: one of "A", "I", "D" (*A*ctive, *I*nactive, *D*eleted)
   *   - formatURI: the format URI of the datastream
   *   - checksumType: the algorithm used to compute the checksum. This has to
   *     be one of: DEFAULT, DISABLED, MD5, SHA-1, SHA-256, SHA-384, SHA-512.
   *     If this parameter is given and no checksum is given the checksum will
   *     be computed.
   *   - checksum: 	the value of the checksum represented as a hexadecimal
   *     string. This checksum must be computed by the algorithm defined above.
   *   - mimeType: 	the MIME type of the content being added, this overrides
   *     the Content-Type request header.
   *   - logMessage: a message describing the activity being performed
   *   - lastModifiedDate: 	date/time of the last (known) modification to the
   *     datastream, if the actual last modified date is later, a 409 response
   *     is returned. This can be used for opportunistic object locking.
   *
   * @throws RepositoryException
   *
   * @return array
   *   An array contianing information about the updated datastream. This array
   *   is the same as the array returned by getDatastream.
   *
   * @see FedoraApiM::getDatastream
   */
  public function modifyDatastream($pid, $dsid, $params = array()) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);

    $request = "/objects/{$pid}/datastreams/{$dsid}";
    $separator = '?';

    // Setup the file.
    if (isset($params['dsFile'])) {
      $type = 'file';
      $data = $params['dsFile'];
    }
    elseif (isset($params['dsString'])) {
      $type = 'string';
      $data = $params['dsString'];
    }
    elseif (isset($params['dsLocation'])) {
      $type = 'none';
      $data = NULL;
      $this->connection->addParamArray($request, $separator, $params, 'dsLocation');
    }
    else {
      $type = 'none';
      $data = NULL;
    }

    $this->connection->addParamArray($request, $separator, $params, 'altIDs');
    $this->connection->addParamArray($request, $separator, $params, 'dsLabel');
    $this->connection->addParamArray($request, $separator, $params, 'versionable');
    $this->connection->addParamArray($request, $separator, $params, 'dsState');
    $this->connection->addParamArray($request, $separator, $params, 'formatURI');
    $this->connection->addParamArray($request, $separator, $params, 'checksumType');
    $this->connection->addParamArray($request, $separator, $params, 'mimeType');
    $this->connection->addParamArray($request, $separator, $params, 'logMessage');
    $this->connection->addParamArray($request, $separator, $params, 'lastModifiedDate');

    $response = $this->connection->putRequest($request, $type, $data);
    $response = $this->serializer->modifyDatastream($response);

    return $response;
  }

  /**
   * Update Fedora Object parameters.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param array $params
   *   (optional) An array that can have one or more of the following elements:
   *   - label: object label.
   *   - ownerId: the id of the user to be listed at the object owner.
   *   - state: the new object state - *A*ctive, *I*nactive, or *D*eleted.
   *   - logMessage: a message describing the activity being performed.
   *   - lastModifiedDate: date/time of the last (known) modification to the
   *     datastream, if the actual last modified date is later, a 409 response
   *     is returned. This can be used for opportunistic object locking.
   *
   * @throws RepositoryException
   *
   * @return string
   *   A string containg the timestamp of the object modification.
   */
  public function modifyObject($pid, $params = NULL) {
    $pid = urlencode($pid);
    $request = "/objects/$pid";
    $separator = '?';

    $this->connection->addParamArray($request, $separator, $params, 'label');
    $this->connection->addParamArray($request, $separator, $params, 'ownerId');
    $this->connection->addParamArray($request, $separator, $params, 'state');
    $this->connection->addParamArray($request, $separator, $params, 'logMessage');
    $this->connection->addParamArray($request, $separator, $params, 'lastModifiedDate');

    $response = $this->connection->putRequest($request);
    $response = $this->serializer->modifyObject($response);
    return $response;
  }

  /**
   * Permanently removes a datastream and all its associated data.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param string $dsid
   *   Datastream identifier
   * @param array $params
   *   (optional) An array that can have one or more of the following elements:
   *   - startDT: the (inclusive) start date-time stamp of the range. If not
   *     specified, this is taken to be the lowest possible value, and thus,
   *     the entire version history up to the endDT will be purged.
   *   - endDT: the (inclusive) ending date-time stamp of the range. If not
   *     specified, this is taken to be the greatest possible value, and thus,
   *     the entire version history back to the startDT will be purged.
   *   - logMessage: a message describing the activity being performed.
   *
   * @throws RepositoryException
   *
   * @return array
   *   An array containing the timestamps of the datastreams that were removed.
   *   @code
   *   Array
   *   (
   *       [0] => 2012-03-08T18:44:15.214Z
   *       [1] => 2012-03-08T18:44:15.336Z
   *   )
   *   @endcode
   */
  public function purgeDatastream($pid, $dsid, $params = array()) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);
    $request = "/objects/$pid/datastreams/$dsid";
    $separator = '?';

    $this->connection->addParamArray($request, $separator, $params, 'startDT');
    $this->connection->addParamArray($request, $separator, $params, 'endDT');
    $this->connection->addParamArray($request, $separator, $params, 'logMessage');

    $response = $this->connection->deleteRequest($request);
    $response = $this->serializer->purgeDatastream($response);
    return $response;
  }

  /**
   * Purge an object.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param string $log_message
   *   (optional)  A message describing the activity being performed.
   *
   * @throws RepositoryException
   *
   * @return string
   *   Timestamp when object was deleted.
   */
  public function purgeObject($pid, $log_message = NULL) {
    $pid = urlencode($pid);

    $request = "/objects/{$pid}";
    $separator = '?';

    $this->connection->addParam($request, $separator, 'logMessage', $log_message);
    $response = $this->connection->deleteRequest($request);
    $response = $this->serializer->purgeObject($response);
    return $response;
  }

  /**
   * Validate an object.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param array $as_of_date_time
   *   (optional) Indicates that the result should be relative to the
   *     digital object as it existed at the given date and time. Defaults to
   *     the most recent version.
   *
   * @throws RepositoryException
   *
   * @return array
   *   An array containing the validation results.
   *   @code
   *   Array
   *   (
   *       [valid] => false
   *       [contentModels] => Array
   *           (
   *               [0] => "info:fedora/fedora-system:FedoraObject-3.0"
   *           )
   *       [problems] => Array
   *           (
   *               [0] => "Problem description"
   *           )
   *       [datastreamProblems] => Array
   *           (
   *               [dsid] => Array
   *               (
   *                   [0] => "Problem description"
   *               )
   *           )
   *   )
   *   @endcode
   */
  public function validate($pid, $as_of_date_time = NULL) {
    $pid = urlencode($pid);

    $request = "/objects/{$pid}/validate";
    $separator = '?';

    $this->connection->addParam($request, $separator, 'asOfDateTime', $as_of_date_time);

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->validate($response);
    return $response;

  }

  /**
   * Uploads file.
   *
   * @param string $file
   *   Path to uploaded file on server
   *
   * @return string
   *   url to uploaded file
   */
  public function upload($file) {
    $request = "/upload";
    $response = $this->connection->postRequest($request, 'file', $file);
    $response = $this->serializer->upload($response);
    return $response;
  }
}
