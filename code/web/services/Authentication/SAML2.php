<?php
require_once ROOT_DIR . '/sys/Authentication/SAMLAuthentication.php';

class Authentication_SAML2 extends Action {
	/**
	 * @throws UnknownAuthenticationMethodException
	 */
	public function launch() {
		global $configArray;
		$returnTo = $configArray['Site']['url'] . '/MyAccount/Home';
		if(isset($_GET['init'])) {
			global $logger;
			$logger->log('Starting SAML Authentication', Logger::LOG_ERROR, true);
			$auth = new SAMLAuthentication();
			$auth->login($returnTo);
		} elseif(isset($_GET['acs'])) {
			global $logger;
			$logger->log('Completing SAML Authentication', Logger::LOG_ERROR, true);
			try {
				$auth = new SAMLAuthentication();
				if (isset($_SESSION) && isset($_SESSION['AuthNRequestID'])) {
					$requestID = $_SESSION['AuthNRequestID'];
				} else {
					$requestID = null;
				}
				$auth->processResponse($requestID);
				$errors = $auth->getErrors();
				if (!empty($errors)) {
					echo(htmlentities(implode(', ', $errors)));
					die();
				}

				if(!$auth->isAuthenticated()) {
					echo("Not authenticated");
					die();
				}

				$isValidated = $auth->validateAccount();
				unset($_SESSION['AuthNRequestID']);
				if($isValidated) {
					$_SESSION['samlUserdata'] = $auth->getAttributes();
					if (isset($_POST['RelayState']) && OneLogin_Saml2_Utils::getSelfURL() != $_POST['RelayState']) {
						$auth->redirectTo($_POST['RelayState']);
					}
				}

			} catch (Exception $e) {
				$errorMessage = 'Could not initialize authentication';
				require_once ROOT_DIR . '/services/MyAccount/Login.php';
				$launchAction = new MyAccount_Login();
				$launchAction->launch('Could not initialize authentication');
				exit();
			}
		} elseif(isset($_GET['metadata'])) {
			global $logger;
			$logger->log('Fetching SAML SP metadata', Logger::LOG_ERROR, true);
			try {
				$auth = new SAMLAuthentication();
				try {
					$settings = $auth->getSettings();
					$metadata = $settings->getSPMetadata();
					$errors = $settings->validateMetadata($metadata);
					if (empty($errors)) {
						header('Content-Type: text/xml');
						echo $metadata;
					} else {
						require_once ROOT_DIR . '/services/Authentication/SAML/lib/Saml2/Error.php';
						throw new OneLogin_Saml2_Error(
							'Invalid SP metadata: '.implode(', ', $errors),
							OneLogin_Saml2_Error::METADATA_SP_INVALID
						);
					}
				} catch (Exception $e) {
					echo $e->getMessage();
				}
			} catch (Exception $e) {
				$errorMessage = 'Could not initialize authentication';
				require_once ROOT_DIR . '/services/MyAccount/Login.php';
				$launchAction = new MyAccount_Login();
				$launchAction->launch('Could not initialize authentication');
				exit();
			}
		} elseif(isset($_REQUEST['sls'])) {
			global $logger;
			$logger->log('Completing SAML SLS', Logger::LOG_ERROR);
			try {
				$auth = new SAMLAuthentication();
				if (isset($_SESSION) && isset($_SESSION['LogoutRequestID'])) {
					$requestID = $_SESSION['LogoutRequestID'];
				} else {
					$requestID = null;
				}

				$auth->processSLO(false, $requestID);
				$errors = $auth->getErrors();
				if(!empty($errors)) {
					echo(htmlentities(implode(', ', $errors)));
					die();
				}
			} catch (Exception $e) {
				$errorMessage = 'Could not initialize authentication';
				require_once ROOT_DIR . '/services/MyAccount/Login.php';
				$launchAction = new MyAccount_Login();
				$launchAction->launch('Could not initialize authentication');
				exit();
			}
		} else {
			header('Location: /Search/Home');
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}
}