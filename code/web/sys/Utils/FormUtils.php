<?php
class FormUtils {
	static function getModifiableFieldKeys ($formStructure) : array {
		$modifiableFieldKeys = [];
		foreach ($formStructure as $field){
			if ($field['type'] == 'section') {
				$sectionFieldKeys = FormUtils::getModifiableFieldKeys($field['properties']);
				$modifiableFieldKeys = array_merge($modifiableFieldKeys, $sectionFieldKeys);
			}else if ($field['type'] != 'hidden') {
				if (empty($field['readOnly'])) {
					$modifiableFieldKeys[$field['property']] = $field['property'];
				}
			}
		}
		return $modifiableFieldKeys;
	}

	static function getRequiredFields ($formStructure) : array {
		$requiredFields = [];
		foreach ($formStructure as $field){
			if ($field['type'] == 'section') {
				$sectionFieldKeys = FormUtils::getRequiredFields($field['properties']);
				$requiredFields = array_merge($requiredFields, $sectionFieldKeys);
			}else if ($field['type'] != 'hidden') {
				if (!empty($field['required'])) {
					$requiredFields[$field['property']] = $field;
				}
			}
		}
		return $requiredFields;
	}
}