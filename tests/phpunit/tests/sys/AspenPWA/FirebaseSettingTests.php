<?php
namespace sys\AspenPWA;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DataObjectUtilTests extends TestCase {

    public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../../code/web/sys/AspenPWA/FirebaseSetting.php';
	}
    /**
     * cases
     * 1. library is null
     * 2. firebase setting id for library is empty
     * 3. firebase setting id for library is -1
     * 4. library has a valid firebase setting id
     */
    public function testGetSettingsForLibrary()
    {
        
    }

    /**
     * cases
     * 1. id is empty
     * 2. id is -1
     * 3. id is valid
     * 4. is could not be found
     */
    public function testGetSettingsById()
    {
        // cases
        
    }

    /**
     * cases
     * 1. tokens firebase setting id is empty
     * 2. token's firebase setting id is -1
     * 3. token's firebase setting id is valid
     * 4. cannot find firebase settings for token but can find settings for token's user
     * 5. cannot find firebase settings for token or for token's user
     */
    public function testGetSettingsForToken()
    {}

    /**
     * cases
     * 1. serviceAccount is empty
     * 2. serviceAccount is not an array
     * 3. serviceAccount['type'] is not service_account
     * 4. serviceAccount['client_email'] is empty
     * 5. serviceAccount['private_key'] is empty
     * 6. serviceAccount['project_id'] is empty
     * 7. settings' firebaseProjectID is empty
     * 8. settings' firebaseProjectID and serviceAccount['project_id'] are not equal
     * 9. valid serviceAccount
     */
    public function testValidateServiceAccount(){}
}