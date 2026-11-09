<?php
namespace sys\AspenPWA;

use FirebaseSetting;
use Library;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty; //needed to access private _librariesByLocationId for clearing between tests
use User;
use UserNotificationToken;

class FirebaseSettingTests extends TestCase {
	private const PROJECT_ID = 'phpunit-project';
	private const MISSING_ID = 999999;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../../code/web/sys/AspenPWA/FirebaseSetting.php';
		require_once __DIR__ . '/../../../../../code/web/sys/LibraryLocation/Library.php';
		require_once __DIR__ . '/../../../../../code/web/sys/Account/User.php';
		require_once __DIR__ . '/../../../../../code/web/sys/Account/UserNotificationToken.php';
	}

	protected function setUp(): void {
		parent::setUp();
		$this->clearLibraryCache();
	}

	protected function tearDown(): void {
		global $aspen_db;
		$aspen_db->exec("DELETE FROM firebase_settings WHERE name LIKE 'PHPUnit%'");
		$aspen_db->exec("UPDATE library SET firebaseSettingId = -1 WHERE libraryId = 1");
		$aspen_db->exec("DELETE FROM user WHERE source = 'phpunit'");
		$this->clearLibraryCache();
		parent::tearDown();
	}

	//Library::getLibraryForLocation caches libraries statically, which would hide firebaseSettingId changes between tests
	private function clearLibraryCache(): void {
		$cache = new ReflectionProperty(Library::class, '_librariesByLocationId');
		$cache->setValue(null, []);
	}

	private function insertSettings(string $name = 'PHPUnit Firebase'): FirebaseSetting {
		$settings = new FirebaseSetting();
		$settings->name = $name;
		$settings->firebaseAPIKey = 'phpunit-api-key';
		$settings->firebaseAuthDomain = 'phpunit-project.firebaseapp.com';
		$settings->firebaseProjectID = self::PROJECT_ID;
		$settings->firebaseStorageBucket = 'phpunit-project.appspot.com';
		$settings->firebaseMessagingSenderID = '123456789';
		$settings->firebaseAppID = '1:123456789:web:phpunit';
		$settings->vapidKey = 'phpunit-vapid-key';
		$settings->insert();
		return $settings;
	}

	private function createLibrary(mixed $firebaseSettingId): Library {
		$library = new Library();
		$library->firebaseSettingId = $firebaseSettingId;
		return $library;
	}

	//Main Library (libraryId 1) owns location 1 in install/aspen.sql
	private function linkHomeLibraryToSettings(int $firebaseSettingId): void {
		global $aspen_db;
		$aspen_db->exec("UPDATE library SET firebaseSettingId = $firebaseSettingId WHERE libraryId = 1");
	}

	private function insertUser(): User {
		$user = new User();
		$user->source = 'phpunit';
		$user->username = 'phpunit_firebase_user';
		$user->firstname = 'Test';
		$user->lastname = 'Firebase';
		$user->displayName = 'Test Firebase';
		$user->created = date('Y-m-d H:i:s');
		$user->homeLocationId = 1;
		$user->myLocation1Id = 1;
		$user->myLocation2Id = 1;
		$user->unique_ils_id = 'phpunit_firebase_user';
		$user->insert();
		return $user;
	}

	private function createToken(mixed $firebaseSettingId, int $userId): UserNotificationToken {
		$token = new UserNotificationToken();
		$token->userId = $userId;
		$token->firebaseSettingId = $firebaseSettingId;
		return $token;
	}

	public static function missingSettingIdProvider(): array {
		return [
			'id is null' => [null],
			'id is an empty string' => [''],
			'id is 0' => [0],
			'id is -1' => [-1],
		];
	}

	/**
	 * cases
	 * 1. library is null
	 * 2. firebase setting id for library is empty
	 * 3. firebase setting id for library is -1
	 * 4. library has a valid firebase setting id
	 */
	public function testGetSettingsForLibraryReturnsNullWhenLibraryIsNull(): void {
		$this->assertNull(FirebaseSetting::getSettingsForLibrary(null));
	}

	#[DataProvider('missingSettingIdProvider')]
	public function testGetSettingsForLibraryReturnsNullWithoutSettingId(mixed $firebaseSettingId): void {
		$this->insertSettings();
		$this->assertNull(FirebaseSetting::getSettingsForLibrary($this->createLibrary($firebaseSettingId)));
	}

	public function testGetSettingsForLibraryReturnsLinkedSettings(): void {
		$settings = $this->insertSettings();
		$result = FirebaseSetting::getSettingsForLibrary($this->createLibrary($settings->id));
		$this->assertNotNull($result);
		$this->assertEquals($settings->id, $result->id);
		$this->assertSame(self::PROJECT_ID, $result->firebaseProjectID);
	}

	/**
	 * cases
	 * 1. id is empty
	 * 2. id is -1
	 * 3. id is valid
	 * 4. id could not be found
	 */
	#[DataProvider('missingSettingIdProvider')]
	public function testGetSettingsByIdReturnsNullWithoutId(mixed $id): void {
		$this->insertSettings();
		$this->assertNull(FirebaseSetting::getSettingsById($id));
	}

	public function testGetSettingsByIdReturnsSettings(): void {
		$settings = $this->insertSettings();
		$result = FirebaseSetting::getSettingsById($settings->id);
		$this->assertNotNull($result);
		$this->assertEquals($settings->id, $result->id);
	}

	public function testGetSettingsByIdReturnsNullWhenNotFound(): void {
		$this->assertNull(FirebaseSetting::getSettingsById(self::MISSING_ID));
	}

	/**
	 * cases
	 * 1. token's firebase setting id is empty
	 * 2. token's firebase setting id is -1
	 * 3. token's firebase setting id is valid
	 * 4. cannot find firebase settings for token but can find settings for token's user
	 * 5. cannot find firebase settings for token or for token's user
	 */
	#[DataProvider('missingSettingIdProvider')]
	public function testGetSettingsForTokenFallsBackToHomeLibraryWithoutSettingId(mixed $firebaseSettingId): void {
		$homeLibrarySettings = $this->insertSettings();
		$this->linkHomeLibraryToSettings($homeLibrarySettings->id);
		$user = $this->insertUser();

		$result = FirebaseSetting::getSettingsForToken($this->createToken($firebaseSettingId, $user->id));
		$this->assertNotNull($result);
		$this->assertEquals($homeLibrarySettings->id, $result->id);
	}

	public function testGetSettingsForTokenPrefersTokenSettingsOverHomeLibrary(): void {
		$tokenSettings = $this->insertSettings('PHPUnit Firebase Token');
		$homeLibrarySettings = $this->insertSettings('PHPUnit Firebase Home Library');
		$this->linkHomeLibraryToSettings($homeLibrarySettings->id);
		$user = $this->insertUser();

		$result = FirebaseSetting::getSettingsForToken($this->createToken($tokenSettings->id, $user->id));
		$this->assertNotNull($result);
		$this->assertEquals($tokenSettings->id, $result->id);
	}

	public function testGetSettingsForTokenFallsBackToHomeLibraryWhenTokenSettingsAreMissing(): void {
		$homeLibrarySettings = $this->insertSettings();
		$this->linkHomeLibraryToSettings($homeLibrarySettings->id);
		$user = $this->insertUser();

		$result = FirebaseSetting::getSettingsForToken($this->createToken(self::MISSING_ID, $user->id));
		$this->assertNotNull($result);
		$this->assertEquals($homeLibrarySettings->id, $result->id);
	}

	public function testGetSettingsForTokenReturnsNullWhenUserIsMissing(): void {
		$this->linkHomeLibraryToSettings($this->insertSettings()->id);
		$this->assertNull(FirebaseSetting::getSettingsForToken($this->createToken(self::MISSING_ID, self::MISSING_ID)));
	}

	public function testGetSettingsForTokenReturnsNullWhenHomeLibraryHasNoSettings(): void {
		$user = $this->insertUser();
		$this->assertNull(FirebaseSetting::getSettingsForToken($this->createToken(-1, $user->id)));
	}

	public static function serviceAccountProvider(): array {
		$validAccount = [
			'type' => 'service_account',
			'project_id' => self::PROJECT_ID,
			'client_email' => 'phpunit@phpunit-project.iam.gserviceaccount.com',
			'private_key' => "-----BEGIN PRIVATE KEY-----\nphpunit\n-----END PRIVATE KEY-----\n",
		];
		return [
			'serviceAccount is empty' => ['', self::PROJECT_ID, true],
			'serviceAccount is not json' => ['not json', self::PROJECT_ID, false],
			'serviceAccount is not an array' => ['"a string"', self::PROJECT_ID, false],
			'type is not service_account' => [json_encode(array_merge($validAccount, ['type' => 'authorized_user'])), self::PROJECT_ID, false],
			'client_email is empty' => [json_encode(array_merge($validAccount, ['client_email' => ''])), self::PROJECT_ID, false],
			'private_key is empty' => [json_encode(array_merge($validAccount, ['private_key' => ''])), self::PROJECT_ID, false],
			'project_id is empty' => [json_encode(array_merge($validAccount, ['project_id' => ''])), self::PROJECT_ID, true],
			'firebaseProjectID is empty' => [json_encode($validAccount), '', true],
			'project ids do not match' => [json_encode($validAccount), 'other-project', false],
			'valid serviceAccount' => [json_encode($validAccount), self::PROJECT_ID, true],
		];
	}

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
	#[DataProvider('serviceAccountProvider')]
	public function testValidateServiceAccount(string $serviceAccount, string $firebaseProjectID, bool $expectedOk): void {
		$settings = new FirebaseSetting();
		$settings->serviceAccount = $serviceAccount;
		$settings->firebaseProjectID = $firebaseProjectID;

		$results = $settings->validateServiceAccount();
		$this->assertSame($expectedOk, $results['validatedOk']);
		$this->assertSame($expectedOk, empty($results['errors']));
	}
}
