<?php

class RBdigitalProduct extends DataObject
{
    public $__table = 'rbdigital_title';

    public $id;
    public $rbdigitalId;
    public $title;
    public $primaryAuthor;
    public $mediaType;
    public $isFiction;
    public $audience;
    public $language;
    public $rawChecksum;
    public $rawResponse;
    public $lastChange;
    public $dateFirstDetected;
    public $deleted;
}