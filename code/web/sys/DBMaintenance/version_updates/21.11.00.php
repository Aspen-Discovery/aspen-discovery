<?php
/** @noinspection PhpUnused */
function getUpdates21_11_00() : array
{
	return [
		'showCardExpirationDate' => [
			'title' => 'Add app access level options',
			'description' => 'Create app access level for the greenhouse',
			'sql' => [
				'ALTER TABLE library ADD COLUMN showCardExpirationDate TINYINT(1) DEFAULT 1'
			]
		], //showCardExpirationDate
	];
}