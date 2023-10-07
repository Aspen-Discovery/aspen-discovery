<?php

function getUpdates23_11_00():array {
    $curTime = time();
    return [
        'store_place_of_publication' => [
            'title' => 'Places of Publication',
            'description' => 'Store information about the place of publication',
            'sql' => [
                "DROP TABLE IF EXISTS indexed_places_of_publication",

                "CREATE TABLE indexed_places_of_publication (
                    id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    placesOfPublication VARCHAR(255) collate utf8_bin UNIQUE
                    ) ENGINE INNODB",
            ],
        ],
        //indexed_information_places_of_publication
        'add_places_of_publication_to_grouped_work' => [
            'title' => 'Add Places of Publication to Grouped Work',
            'description' => 'Add Places of Publication to Grouped Work',
            'sql' => [
                "ALTER TABLE grouped_work_records ADD COLUMN placesOfPublicationId INT(11) DEFAULT 1",
            ]
        ]
    ];
}