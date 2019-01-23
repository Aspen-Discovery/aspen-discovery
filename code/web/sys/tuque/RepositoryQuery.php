<?php
/**
 * @file
 * This file provides some methods for doing RDF queries.
 *
 * The essance of this file was taken from some commits that Adam Vessy made to
 * Islandora 6.x, so I'd like to give him some credit here.
 */

class RepositoryQuery {

  public $connection;
  const SIMPLE_XML_NAMESPACE = "http://www.w3.org/2001/sw/DataAccess/rf1/result";

  /**
   * Construct a new RI object.
   *
   * @param RepositoryConnection $connection
   *   The connection to connect to the RI with.
   */
  public function __construct(RepositoryConnection $connection) {
    $this->connection = $connection;
  }

  /**
   * Parse the passed in Sparql XML string into a more easily usable format.
   *
   * @param string $sparql
   *   A string containing Sparql result XML.
   *
   * @return array
   *   Indexed (numerical) array, containing a number of associative arrays,
   *   with keys being the same as the variable names in the query.
   *   URIs beginning with 'info:fedora/' will have this beginning stripped
   *   off, to facilitate their use as PIDs.
   */
  public static function parseSparqlResults($sparql) {
    // Load the results into a XMLReader Object.
    $xmlReader = new XMLReader();
    $xmlReader->xml($sparql);
    
    // Storage.
    $results = array();
    // Build the results.
    while ($xmlReader->read()) {
      if ($xmlReader->localName === 'result') {
        if ($xmlReader->nodeType == XMLReader::ELEMENT) {
          // Initialize a single result.
          $r = array();
        }
        elseif ($xmlReader->nodeType == XMLReader::END_ELEMENT) {
          // Add result to results
          $results[] = $r;
        }
      }
      elseif ($xmlReader->nodeType == XMLReader::ELEMENT && $xmlReader->depth == 3) {
        $val = array();
        $uri = $xmlReader->getAttribute('uri');
        if ($uri !== NULL) {
          $val['value'] = self::pidUriToBarePid($uri);
          $val['uri'] = (string) $uri;
          $val['type'] = 'pid';
        }
        else {
          //deal with any other types
          $val['type'] = 'literal';
          $val['value'] = (string) $xmlReader->readInnerXML();
        }
        $r[$xmlReader->localName] = $val;
      }
    }

    $xmlReader->close();
    return $results;
  }

  /**
   * Performs the given Resource Index query and return the results.
   *
   * @param string $query
   *   A string containing the RI query to perform.
   * @param string $type
   *   The type of query to perform, as used by the risearch interface.
   * @param int $limit
   *   An integer, used to limit the number of results to return.
   * @param string $format
   *   A string indicating the type format desired, as supported by the
   *   underlying triple store.
   *
   * @return string
   *   The contents returned, in the $format specified.
   */
  protected function internalQuery($query, $type = 'itql', $limit = -1, $format = 'Sparql') {
    // Construct the query URL.
    $url = '/risearch';
    $seperator = '?';

    $this->connection->addParam($url, $seperator, 'type', 'tuples');
    $this->connection->addParam($url, $seperator, 'flush', TRUE);
    $this->connection->addParam($url, $seperator, 'format', $format);
    $this->connection->addParam($url, $seperator, 'lang', $type);
    $this->connection->addParam($url, $seperator, 'query', $query);

    // Add limit if provided.
    if ($limit > 0) {
      $this->connection->addParam($url, $seperator, 'limit', $limit);
    }

    $result = $this->connection->getRequest($url);

    return $result['content'];
  }

  /**
   * Performs the given Resource Index query and return the results.
   *
   * @param string $query
   *   A string containing the RI query to perform.
   * @param string $type
   *   The type of query to perform, as used by the risearch interface.
   * @param int $limit
   *   An integer, used to limit the number of results to return.
   *
   * @return array
   *   Indexed (numerical) array, containing a number of associative arrays,
   *   with keys being the same as the variable names in the query.
   *   URIs beginning with 'info:fedora/' will have this beginning stripped
   *   off, to facilitate their use as PIDs.
   */
  public function query($query, $type = 'itql', $limit = -1) {
    // Pass the query's results off to a decent parser.
    return self::parseSparqlResults($this->internalQuery($query, $type, $limit));
  }

  /**
   * Thin wrapper for self::query().
   *
   * @see self::query()
   */
  public function itqlQuery($query, $limit = -1) {
    return $this->query($query, 'itql', $limit);
  }

  /**
   * Thin wrapper for self::query().
   *
   * This function once took a 3rd parameter for an offset that did not work.
   * It has been eliminated.  If you wish to use an offset include it in the
   * query.
   *
   * @see self::query()
   */
  public function sparqlQuery($query, $limit = -1) {
    return $this->query($query, 'sparql', $limit);
  }

  /**
   * Utility function used in self::query().
   *
   * Strips off the 'info:fedora/' prefix from the passed in string.
   *
   * @param string $uri
   *   A string containing a URI.
   *
   * @return string
   *   The input string less the 'info:fedora/' prefix (if it has it).
   *   The original string otherwise.
   */
  protected static function pidUriToBarePid($uri) {
    $chunk = 'info:fedora/';
    $pos = strpos($uri, $chunk);
    // Remove info:fedora/ chunk.
    if ($pos === 0) {
      return substr($uri, strlen($chunk));
    }
    // Doesn't start with info:fedora/ chunk...
    else {
      return $uri;
    }
  }

  /**
   * Get the count of tuples a query selects.
   *
   * Given that some langauges do not have a built-in construct for performing
   * counting/aggregation, a method to help with this is desirable.
   *
   * @param string $query
   *   A query for which to count the number tuples returned.
   *
   * @return int
   *   The number of tuples which were selected.
   */
  public function countQuery($query, $type='itql') {
    $content = $this->internalQuery($query, $type, -1, 'count');
    return intval($content);
  }
}
