<?php
/** @noinspection PhpUnused */
function getUpdates22_08_00() : array
{
	$curTime = time();
	return [
		'add_library_sso_config_options' => array(
			'title' => 'SSO - Library config options',
			'description' => 'Allow SSO configuration options to be specified',
			'sql' => [
				"ALTER TABLE library ADD column IF NOT EXISTS ssoName VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoXmlUrl VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoUniqueAttribute VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoMetadataFilename VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoIdAttr VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoUsernameAttr VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoFirstnameAttr VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoLastnameAttr VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoEmailAttr VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoDisplayNameAttr VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoPhoneAttr VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoPatronTypeAttr VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoPatronTypeFallback VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoAddressAttr VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoCityAttr VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoLibraryIdAttr VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoLibraryIdFallback VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoCategoryIdAttr VARCHAR(255)",
				"ALTER TABLE library ADD column IF NOT EXISTS ssoCategoryIdFallback VARCHAR(255)"
            ]
		), //add_library_sso_config_options
	];
}
