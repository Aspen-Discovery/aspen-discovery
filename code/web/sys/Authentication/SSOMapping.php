<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';

class SSOMapping extends DataObject {
	public $__table = 'sso_mapping';
	public $id;
	public $aspenField;
	public $responseField;
	public $ssoSettingId;
	public $fallbackValue;

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'aspenField' => [
				'property' => 'aspenField',
				'type' => 'text',
				'label' => 'Attribute in Aspen or ILS',
				'description' => 'The attribute to match',
				'readOnly' => true,
			],
			'responseField' => [
				'property' => 'responseField',
				'type' => 'text',
				'label' => 'Attribute from Provider<br><span class="label label-primary">Leave blank to disable</span>',
				'description' => 'The attribute to match with Aspen that is returned by the provider',
			],
			'fallbackValue' => [
				'property' => 'fallbackValue',
				'type' => 'text',
				'label' => 'Fallback Value<br><span class="label label-primary">Leave blank to disable</span>',
				'description' => 'If the attribute is not given by the provider, give a fallback value to assign to users',
			],
		];
	}

	static function getDefaults($ssoSettingId) {
		$defaultFieldsToDisplay = [];

		$defaultField = new SSOMapping();
		$defaultField->ssoSettingId = $ssoSettingId;
		$defaultField->aspenField = 'user_id';
		$defaultField->responseField = '';
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new SSOMapping();
		$defaultField->ssoSettingId = $ssoSettingId;
		$defaultField->aspenField = 'username';
		$defaultField->responseField = '';
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new SSOMapping();
		$defaultField->ssoSettingId = $ssoSettingId;
		$defaultField->aspenField = 'email';
		$defaultField->responseField = '';
		$defaultField->fallbackValue = '';
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new SSOMapping();
		$defaultField->ssoSettingId = $ssoSettingId;
		$defaultField->aspenField = 'first_name';
		$defaultField->responseField = '';
		$defaultField->fallbackValue = '';
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new SSOMapping();
		$defaultField->ssoSettingId = $ssoSettingId;
		$defaultField->aspenField = 'last_name';
		$defaultField->responseField = '';
		$defaultField->fallbackValue = '';
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new SSOMapping();
		$defaultField->ssoSettingId = $ssoSettingId;
		$defaultField->aspenField = 'display_name';
		$defaultField->responseField = '';
		$defaultField->fallbackValue = '';
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new SSOMapping();
		$defaultField->ssoSettingId = $ssoSettingId;
		$defaultField->aspenField = 'patron_type';
		$defaultField->responseField = '';
		$defaultField->fallbackValue = '';
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new SSOMapping();
		$defaultField->ssoSettingId = $ssoSettingId;
		$defaultField->aspenField = 'library_code';
		$defaultField->responseField = '';
		$defaultField->fallbackValue = '';
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		return $defaultFieldsToDisplay;
	}
}