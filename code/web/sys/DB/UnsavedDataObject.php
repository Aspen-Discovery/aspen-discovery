<?php


class UnsavedDataObject extends DataObject {
	function __get($name) {

		return $this->_data[$name] ?? null;
	}

	public function setProperty($propertyName, $newValue, $propertyStructure): bool {
		$this->__set($propertyName, $newValue);
		return true;
	}

	function __set($name, $value) {
		$this->_data[$name] = $value;
	}

	function serializeDataToJson($structure) {

		$dataToEncode = [];
		foreach ($this->_data as $fieldId => $value) {
			$fieldLabel = $structure[$fieldId]['label'];
			$dataToEncode[$fieldLabel] = $value;
		}
		return json_encode($dataToEncode);
	}

	function getPrintableHtmlData($structure) {
		$printableData = '';
		foreach ($this->_data as $fieldId => $value) {
			$fieldLabel = $structure[$fieldId]['label'];
			$printableData .= "<div><b>$fieldLabel</b></div><div>$value</div><br/>";
		}
		return $printableData;
	}

    function getAllData($structure){
        $formFields = [];
        foreach ($this->_data as $fieldId => $value) {
            $fieldLabel = $structure[$fieldId]['label'];
            $formFields[$fieldLabel] = $value;
        }

        error_log("LGM DATA : " . print_r($formFields,true));
        return $formFields;
    }

	function getPrintableTextData($structure) {
		$printableData = '';
		foreach ($this->_data as $fieldId => $value) {
			$fieldLabel = $structure[$fieldId]['label'];
			$printableData .= "$fieldLabel\r\n$value\r\n\r\n";
		}
		return $printableData;
	}
}