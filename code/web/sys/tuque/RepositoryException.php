<?php

/**
 * @file
 * Contains all of the exceptions thrown by Tuque.
 */

/**
 * The top level exception for the Islandora Fedora API
 */
class RepositoryException extends Exception {}

/**
 * This is thrown when there is an error parsing XML.
 */
class RepositoryXmlError extends RepositoryException {
  public $errors;

  /**
   * Same as the default exception constructor except it takes another
   * parameter errors, this is the error returned by the xml parser.
   */
  function __construct($message, $code, $errors, $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->errors = $errors;
  }
}

/**
 * This is thrown when a bad arguement is passed.
 */
class RepositoryBadArguementException extends RepositoryException {}

/**
 * This is thrown from the relationships class when something goes wrong
 */
class RepositoryRelationshipsException extends RepositoryException {}
