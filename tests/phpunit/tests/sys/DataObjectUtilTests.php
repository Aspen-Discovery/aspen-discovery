<?php
namespace sys;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DataObjectUtilTests extends TestCase {

    public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../code/web/sys/DataObjectUtil.php';
	}

    /**
     * cases
     * 1. $_REQUEST[remove$propertyName] is set
     * 2. $_FILES[$propertyName] is not set
     * 3. $fileForProperty["error"]  == 4
     * 4. $fileForProperty["error"] > 0
     * 5. property has validExtensions listed but the file is not one of those extensions
     * 6. contents of file returns false
     * 7. valid file
     */
    public function testProcessFileContentsProperty(){}
}