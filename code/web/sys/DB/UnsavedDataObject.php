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

	function getPrintableHtmlData($structure) : string {
		$printableData = '';
		foreach ($this->_data as $fieldId => $value) {
			if (is_array($value)) {
				$value = implode(', ', $value);
			}
			$fieldLabel = $structure[$fieldId]['label'];
			$printableData .= "<div><b>$fieldLabel</b></div><div>$value</div><br/>";
		}
		return $printableData;
	}

	function getAllData() : array {
		return array_map(function ($value) {
			return $value;
		}, $this->_data);
	}
}