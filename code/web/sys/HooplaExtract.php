<?php
/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 4/24/2018
 *
 */


class HooplaExtract extends DB_DataObject
{
	public $id;
	public $hooplaId;
	public $active;
	public $title;
	public $kind;
	public $pa;  //Parental Advisory
	public $demo;
	public $profanity;
	public $rating; // eg TV parental guidance rating
	public $abridged;
	public $price;

	public $__table = 'hoopla_export';

}