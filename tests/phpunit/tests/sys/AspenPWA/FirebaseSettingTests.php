<?php
namespace sys\AspenPWA;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DataObjectUtilTests extends TestCase {

    public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../../code/web/sys/AspenPWA/FirebaseSetting.php';
	}
}