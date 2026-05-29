<?php

abstract class AbstractAPI extends Action{
	protected $context;
	function __construct($context = 'external') {
		parent::__construct(false);
		$this->context = $context;
		if ($this->checkIfLiDA()) {
			$this->context = 'lida';
		}
	}

	function checkIfLiDA(): bool {
		if (function_exists('getallheaders')) {
			foreach (getallheaders() as $name => $value) {
				if ($name == 'User-Agent' || $name == 'user-agent') {
					if (str_contains($value, "Aspen LiDA")) {
						return true;
					}
				}
			}
		}
		return false;
	}

	function getLiDAVersion() {
		if (function_exists('getallheaders')) {
			foreach (getallheaders() as $name => $value) {
				if ($name == 'version' || $name == 'Version') {
					$version = explode(' ', $value);
					$version = substr($version[0], 1); // remove starting 'v'
					return floatval($version);
				}
			}
		}
		return 0;
	}

	function getLiDASession() {
		if (function_exists('getallheaders')) {
			foreach (getallheaders() as $name => $value) {
				if ($name == 'LiDA-SessionID' || $name == 'lida-sessionid') {
					$sessionId = explode(' ', $value);
					return $sessionId[0];
				}
			}
		}
		return false;
	}

	function getLiDASlug() {
		if (function_exists('getallheaders')) {
			foreach (getallheaders() as $name => $value) {
				if (strcasecmp($name, 'lida-slug') === 0) {
					return $value;
				}
			}
		}
		return false;
	}

	function getLiDAUserAgent() {
		if (function_exists('getallheaders')) {
			foreach (getallheaders() as $name => $value) {
				if ($name == 'User-Agent' || $name == 'user-agent') {
					if (str_contains($value, 'Aspen LiDA') || str_contains($value, 'aspen lida')) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * @return array
	 * @noinspection PhpUnused
	 */
	function loadUsernameAndPassword() {
		$username = $_REQUEST['username'] ?? '';
		$password = $_REQUEST['password'] ?? '';

		if (isset($_POST['username']) && isset($_POST['password'])) {
			$username = $_POST['username'];
			$password = $_POST['password'];
		}

		if (is_array($username)) {
			$username = reset($username);
		}
		if (is_array($password)) {
			$password = reset($password);
		}
		return [$username, $password];
	}

	function logPatronRequest($userId): void {
		if ($this->context == 'lida') {
			require_once ROOT_DIR . '/sys/SystemLogging/UserAppRequestLogEntry.php';
			UserAppRequestLogEntry::logRequest($userId, $_GET['action'], $_GET['method'], json_encode($_REQUEST), $this->getLiDAVersion());
		}
	}

	private $_userForAPICall = null;
	/**
	 * @return bool|User
	 */
	function getUserForApiCall() : bool|User {
		if ($this->_userForAPICall === null) {
			[$username, $password] = $this->loadUsernameAndPassword();
			$user = UserAccount::validateAccount($username, $password);
			if ($user !== false && $user->source == 'admin') {
				//Admin users are not allowed with API calls
				$this->_userForAPICall = false;
				return $this->_userForAPICall;
			}

			//Set translations up based on the active user's desired language
			if (empty($_REQUEST['language']) && $user !== false) {
				global $activeLanguage;
				global $translator;
				$userLanguage = new Language();
				$userLanguage->code = $user->interfaceLanguage;
				if ($userLanguage->find(true)) {
					if ($userLanguage->code != $activeLanguage->code) {
						$activeLanguage = $userLanguage;
						$translator = new Translator('lang', $userLanguage->code);
					}
				}
			}

			if ($user !== false && $user->allowAppRequestLogging) {
				$this->logPatronRequest($user->id);
			}
			$this->_userForAPICall = $user;
		}

		return $this->_userForAPICall;
	}

	/**
	 * Returns valid sources for Aspen LiDA to return when making API requests for searching, browse categories, lists, etc.
	 * <ul>
	 *     <li><b>Adding new items here without proper testing can result in the app crashing and should only be updated when a source is confirmed to be working with LiDA.</b></li>
	 * </ul>
	 * @return array
	 * @noinspection PhpUnused
	 */
	public static function getValidSourcesForLiDA($context = 'browseCategory'): array {
		if ($context == 'search') {
			return [
				'event_assabet',
				'event_communico',
				'event_libcal',
				'library_calendar_event',
				'event_aspenEvent',
				'grouped_work'
			];
		} elseif ($context == 'list') {
			return [
				'GroupedWork',
				'Events',
				'Lists'
			];
		} else {
			return [
				'GroupedWork',
				'List',
				'Events'
			];
		}
	}
}