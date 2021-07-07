<?php
/** @noinspection PhpUnused */
function getUpdates21_08_01() : array
{
	return [
		'indexed_information_publisher_length' => [
			'title' => 'Indexed Information Publisher Length',
			'description' => 'Increase the length of the publisher field',
			'sql' => [
				"ALTER TABLE indexed_publisher CHANGE COLUMN publisher publisher VARCHAR(500) collate utf8_bin UNIQUE ",
			]
		], //indexed_information_publisher_length
	];
}