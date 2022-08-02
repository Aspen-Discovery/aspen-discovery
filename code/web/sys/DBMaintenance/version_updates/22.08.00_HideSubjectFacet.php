<?php
/** @noinspection PhpUnused */
function getUpdates22_08_00_HideSubjectFacet() : array
{
    return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
        'hide_subject_facet_permission' => [
            'title' => 'Add permission for Administer Subjects to Exclude from Subject Facet',
            'description' => 'Add permission for Administer Subjects to Exclude from Subject Facet',
            'sql' => [
                "INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Cataloging & eContent', 'Administer Subjects to Exclude from Subject Facet', '', 85, 'Controls if the user can Subjects to Exclude from Subject Facet.')",
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Subjects to Exclude from Subject Facet'))",
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='cataloging'), (SELECT id from permissions where name='Administer Subjects to Exclude from Subject Facet'))"
            ]
        ], // hide_subject_facets_permission
        'hide_subject_facets' => [
            'title' => 'Add subjects to exclude from subject facet',
            'description' => 'Add subjects to exclude from subject, era, genre, region, and topic facets',
            'sql' => [
                'CREATE TABLE IF NOT EXISTS hide_subject_facets (
                            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            subjectTerm VARCHAR(512) NOT NULL UNIQUE,
                            subjectNormalized VARCHAR(512) NOT NULL UNIQUE,
                            dateAdded INT(11)
                        ) ENGINE INNODB',
            ],
        ], // hide_subject_facets
    ];
}