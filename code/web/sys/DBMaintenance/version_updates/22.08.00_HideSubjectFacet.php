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
        'hide_subject_facet' => [
            'title' => 'Add permission for Administer Subjects to Exclude from Subject Facet',
            'descrition' => 'Add permission for Administer Subjects to Exclude from Subject Facet',
            'sql' => [
                "INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Cataloging & eContent', 'Administer Subjects to Exclude from Subject Facet', '', 85, 'Controls if the user can Subjects to Exclude from Subject Facet.')",
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Subjects to Exclude from Subject Facet'))",
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='cataloging'), (SELECT id from permissions where name='Administer Subjects to Exclude from Subject Facet'))"
            ]
        ]
    ];
}