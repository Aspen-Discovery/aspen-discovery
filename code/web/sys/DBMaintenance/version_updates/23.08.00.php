<?php
/** @noinspection PhpUnused */
function getUpdates23_08_00(): array {
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


		//mark - ByWater
		'custom_facets' => [
			'title' => 'Add custom facet indexing information to Indexing Profiles',
			'description' => 'Add custom facet indexing information to Indexing Profiles',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet1SourceField VARCHAR(50) DEFAULT ''",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet1ValuesToInclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet1ValuesToExclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet2SourceField VARCHAR(50) DEFAULT ''",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet2ValuesToInclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet2ValuesToExclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet3SourceField VARCHAR(50) DEFAULT ''",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet3ValuesToInclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet3ValuesToExclude TEXT",
				"UPDATE indexing_profiles set customFacet1ValuesToInclude = '.*'",
				"UPDATE indexing_profiles set customFacet2ValuesToInclude = '.*'",
				"UPDATE indexing_profiles set customFacet3ValuesToInclude = '.*'",
			]
		],
		'twilio_settings' => [
			'title' => 'Twilio Settings',
			'description' => 'Add twilio settings and permissions',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS twilio_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) UNIQUE,
					phone VARCHAR(15),
					accountSid VARCHAR(50),
					authToken VARCHAR(256)
				)",
				"ALTER TABLE library ADD COLUMN twilioSettingId INT(11) DEFAULT -1",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('System Administration', 'Administer Twilio', '', 34, 'Controls if the user can change Twilio settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Twilio'))",
			]
		],

		//kirstien - ByWater
		'aspen_lida_self_check_settings' => [
			'title' => 'Aspen LiDA Self-Check Settings',
			'description' => 'Add Aspen LiDA self-check settings and permissions',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS aspen_lida_self_check_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) UNIQUE,
					isEnabled TINYINT(1) DEFAULT 0
				)',
				'ALTER TABLE location ADD COLUMN lidaSelfCheckSettingId INT(11) DEFAULT -1',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Aspen LiDA', 'Administer Aspen LiDA Self-Check Settings', 'Aspen LiDA', 10, 'Controls if the user can change Aspen LiDA Self-Check settings.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Aspen LiDA Self-Check Settings'))",
			]
		],
		'aspen_lida_permissions_update' => [
			'title' => 'Update Aspen LiDA permissions',
			'description' => 'Add Aspen LiDA as required module for Aspen LiDA permissions',
			'continueOnError' => true,
			'sql' => [
				"UPDATE permissions set requiredModule = 'Aspen LiDA' WHERE sectionName = 'Aspen LiDA'",
			]
		],
		'add_ecommerce_options' => [
			'title' => 'Add additional eCommerce options in Library Systems',
			'description' => 'Add option to add notes for convenience fee and terms of service for eCommerce',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE library ADD COLUMN eCommerceFee VARCHAR(11) DEFAULT 0",
				"ALTER TABLE library ADD COLUMN eCommerceTerms VARCHAR(255) DEFAULT NULL"
			]
		],
		'add_hold_pending_cancellation' => [
			'title' => 'Add column to store if hold is pending cancellation',
			'description' => 'Add column to store if hold is pending cancellation',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user_hold ADD COLUMN pendingCancellation TINYINT(1) DEFAULT 0'
			]
		],

		//kodi - ByWater

		'webpage_default_image' => [
			'title' => 'Website Indexing - Set default image for cover images',
			'description' => 'Update website_indexing_settings table to have default values for the default cover image',
			'sql' => [
				"ALTER TABLE website_indexing_settings ADD COLUMN defaultCover VARCHAR(100) default ''",
			],
		], //webpage_default_image
		'OAI_default_image' => [
			'title' => 'OAI Indexing - Set default image for cover images',
			'description' => 'Update open_archives_collection table to have default values for the default cover image',
			'sql' => [
				"ALTER TABLE open_archives_collection ADD COLUMN defaultCover VARCHAR(100) default ''",
			],
		], //OAI_default_image
		'events_in_lists' => [
			'title' => 'Events in Lists Settings',
			'description'=> 'Add settings for events in lists for Communico, Springshare LibCal, and Library Market',
			'sql' => [
				'ALTER TABLE lm_library_calendar_settings ADD COLUMN eventsInLists tinyint(1) default 1',
				'ALTER TABLE springshare_libcal_settings ADD COLUMN eventsInLists tinyint(1) default 1',
				'ALTER TABLE communico_settings ADD COLUMN eventsInLists tinyint(1) default 1',
			],
		], //events_in_lists
		'bypass_event_pages' => [
			'title' => 'Bypass Aspen event pages',
			'description'=> 'Add settings for events to bypass the Aspen event page and redirect the user to the event page on the native platform',
			'sql' => [
				'ALTER TABLE lm_library_calendar_settings ADD COLUMN bypassAspenEventPages tinyint(1) default 0',
				'ALTER TABLE springshare_libcal_settings ADD COLUMN bypassAspenEventPages tinyint(1) default 0',
				'ALTER TABLE communico_settings ADD COLUMN bypassAspenEventPages tinyint(1) default 0',
			],
		], //bypass_event_pages
		'event_registration_modal' => [
			'title' => 'Event Registration Modal',
			'description'=> 'Add settings for modal for event registration information',
			'sql' => [
				'ALTER TABLE lm_library_calendar_settings ADD COLUMN registrationModalBody mediumtext',
				'ALTER TABLE springshare_libcal_settings ADD COLUMN registrationModalBody mediumtext',
				'ALTER TABLE communico_settings ADD COLUMN registrationModalBody mediumtext',
			],
		], //event_registration_modal

		//other organizations

        //Lucas - Theke
        'secondary phone number' => [
			'title' => 'Add a secondary phone number to Location',
			'description' => 'A new field has been added in the location forms
                                 referring to a secondary telephone number.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE location ADD COLUMN secondaryPhoneNumber VARCHAR(25) DEFAULT ''",
			]
		], //secondary phone number

		//Alexander - PTFS
		'add_supporting_company_system_variables' => [
			'title' => 'Add supporting company into system variables',
			'description' => 'Add column to set name of company undertaking installation',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN supportingCompany VARCHAR(72) default 'ByWater Solutions'",
			]
		],

		//Jacob - PTFS
		'add_cookie_consent_theming' => [
			'title' => 'Add theming to Cookie Consent banner',
			'description' => 'Adds column to specify cookie consent colors in themes',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN cookieConsentBackgroundColor CHAR(7) DEFAULT '#1D7FF0'",
				'ALTER TABLE themes ADD COLUMN cookieConsentBackgroundColorDefault tinyint(1) DEFAULT 1',
				"ALTER TABLE themes ADD COLUMN cookieConsentButtonColor CHAR(7) DEFAULT '#1D7FF0'",
				'ALTER TABLE themes ADD COLUMN cookieConsentButtonColorDefault tinyint(1) DEFAULT 1',
				"ALTER TABLE themes ADD COLUMN cookieConsentButtonHoverColor CHAR(7) DEFAULT '#FF0000'",
				'ALTER TABLE themes ADD COLUMN cookieConsentButtonHoverColorDefault tinyint(1) DEFAULT 1',
				"ALTER TABLE themes ADD COLUMN cookieConsentTextColor CHAR(7) DEFAULT '#FFFFFF'",
				'ALTER TABLE themes ADD COLUMN cookieConsentTextColorDefault tinyint(1) DEFAULT 1',
				"ALTER TABLE themes ADD COLUMN cookieConsentButtonTextColor CHAR(7) DEFAULT '#FFFFFF'",
				'ALTER TABLE themes ADD COLUMN cookieConsentButtonTextColorDefault tinyint(1) DEFAULT 1',
				"ALTER TABLE themes ADD COLUMN cookieConsentButtonHoverTextColor CHAR(7) DEFAULT '#FFFFFF'",
				'ALTER TABLE themes ADD COLUMN cookieConsentButtonHoverTextColorDefault tinyint(1) DEFAULT 1',
				"ALTER TABLE themes ADD COLUMN cookieConsentButtonBorderColor CHAR(7) DEFAULT '#FFFFFF'",
				'ALTER TABLE themes ADD COLUMN cookieConsentButtonBorderColorDefault tinyint(1) DEFAULT 1',
			]
		],
		//add theming to cookieConsent banner
	];
}