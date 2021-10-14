<?php
/** @noinspection PhpUnused */
function getUpdates21_09_01() : array
{
	return [
		'increase_checkout_due_date' => [
			'title' => 'Increase Checkout Due Date Length',
			'description' => 'Increase Checkout Due Date Length',
			'sql' => [
				"ALTER TABLE user_checkout CHANGE COLUMN dueDate dueDate BIGINT",
				"ALTER TABLE user_checkout CHANGE COLUMN renewalDate renewalDate BIGINT",
			]
		], //increase_checkout_due_date

	];
}