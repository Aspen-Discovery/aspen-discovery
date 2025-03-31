<?php

function getUpdates25_03_01(): array {
	$curTime = time();
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark - Grove

		//katherine

		//kirstien - Grove

		//sublocation_ptype_uniqueness

		//kodi

		// Leo Stoyanov - BWS
		'move_uploaded_list_images_again' => [
			'title' => 'Properly Move Uploaded List Images',
			'description' => "Rerun move of uploaded list images to their own directory so they don't conflict with uploaded records' covers.",
			'sql'=> [
				'moveUploadedListImages'
			]
		], //move_uploaded_list_images_again

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

		//yanjun - ByWater


	];
}
