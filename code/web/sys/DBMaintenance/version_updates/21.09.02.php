<?php
/** @noinspection PhpUnused */
function getUpdates21_09_02(): array {
	return [
		'refetch_novelist_data_21_09_02' => [
			'title' => 'Retry Novelist Data for 21.09.02',
			'description' => 'Reload Novelist data that was serialized as part of compression',
			'sql' => [
				"DELETE FROM novelist_data WHERE lastUpdate >= 1627430400",
			],
		],
		//refetch_novelist_data

	];
}