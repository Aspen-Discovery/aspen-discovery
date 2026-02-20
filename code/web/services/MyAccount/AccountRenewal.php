<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_AccountRenewal extends MyAccount {
	function launch(): void {
		global $interface;

		$user = UserAccount::getLoggedInUser();
		if (empty($user) || !$user) {
			$this->display('accountRenewal.tpl', 'Renew Your Account');
			$interface->assign('loggedIn', false);
			return;
		}

		$ilsName = $user->getILSName();
		// The present version only supports Koha, but is written in such a way that enabling support for ILS can be done as future enhancements
		if ($ilsName !== 'koha') {
			$this->display('accountRenewal.tpl', 'Renew Your Account');
			$interface->assign('ilsUnsupported', true);
			return;
		}

		$sessionKey = 'account_renewal_data_' . $user->id;
		$renewalInfo = $this->getRenewalInformation($sessionKey, $user->unique_ils_id);
		
		$userAgreementVerificationMessage = $renewalInfo['data']['self_renewal_settings']['self_renewal_information_message'] ?? '';
		$selfRenewalSettings = $renewalInfo['data']['self_renewal_settings'] ?? [];
		$hasVerificationCheck = !empty($userAgreementVerificationMessage);

		$currentStepName = $_POST['currentStep'] ?? $_GET['currentStep'] ?? 'start'; 
		$requestedDirection = $_POST['navigation'] ?? 'reload';

		$validationError = '';
		$currentWarningMessage = '';

		// override currentStep with the next as we prepare to relaunch
		$direction = $this->getDirection($currentStepName, $requestedDirection, $result['userAgrees'] ?? false);
		$nextStepName = $this->getNextStep($direction, $currentStepName, $hasVerificationCheck);
		$nextStep = $this->getCurrentStepData($nextStepName, $userAgreementVerificationMessage);

		$interface->assign('currentStep', $nextStep);
		$interface->assign('selfRenewalSettings', $selfRenewalSettings);
		$interface->assign('hasVerificationCheck', $hasVerificationCheck);
		$interface->assign('ilsUnsupported', false);
		$interface->assign('validationError', $validationError);
		$interface->assign('currentWarningMessage', $currentWarningMessage);
		$interface->assign('userAgrees', $result['userAgrees'] ?? '');

		$this->display('accountRenewal.tpl', 'Renew Your Account');
	}

	private function getCurrentStepData(string $currentStepName, string $userAgreementVerificationMessage): array {
		$data = [
			'name' => $currentStepName,
			'title' => '',
			'description' => ''
		];

		if ($currentStepName === 'start') {
			$data['title'] = 'Start';
			$data['description'] = 'Welcome to the account renewal process. Please click Continue to begin.';
		} elseif ($currentStepName === 'verification_check') {
			$data['title'] = 'Verification Questions';
			$data['description'] = $userAgreementVerificationMessage;
		} elseif ($currentStepName === 'verifyContactInformation') {
			$data['title'] = 'Confirm Contact Information';
			$data['description'] = 'Please review and update your contact information as needed.';
		} elseif ($currentStepName === 'done') {
			$data['title'] = 'Request Submitted.';
			$data['description'] = 'Your request has been processed.';
		} else {
			$data['title'] = 'Error';
			$data['description'] = 'An unexpected error occurred.';
		}

		return $data;
	}

	private function getDirection(string $currentStepName, string $requestedDirection, bool $userAgrees = false): string {
		if ($requestedDirection === 'back') {
			return 'back';
		}
		if ($requestedDirection === 'next' || $requestedDirection === 'continue') {
			if ($currentStepName === 'verification_check' && !$userAgrees) {
				return 'stay';
			}
			return 'next';
		}
		return 'stay';
	}

	private function getNextStep(string $direction, string $currentStepName, bool $hasVerificationCheck): string {
		if ($direction === 'stay') {
			return $currentStepName;
		}

		if ($currentStepName === 'start') {
			if ($direction === 'next') {
				return $hasVerificationCheck ? 'verification_check' : 'verifyContactInformation';
			}
			return 'start';
		}

		if ($currentStepName === 'verification_check') {
			return $direction === 'next' ? 'verifyContactInformation' : 'start';
		}

		if ($currentStepName === 'verifyContactInformation') {
			return $direction === 'next' ? 'done' : ($hasVerificationCheck ? 'verification_check' : 'start');
		}

		return $currentStepName;
	}

	private function getRenewalInformation(string $sessionKey, string $userIlsId): array {
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}

		if (isset($_SESSION[$sessionKey])) {
			return $_SESSION[$sessionKey];
		}

		$ilsDriver = CatalogFactory::getCatalogConnectionInstance();

		$renewalInfo = $ilsDriver->getAccountRenewalInformationForPatron($userIlsId);
		$_SESSION[$sessionKey] = $renewalInfo;

		return $renewalInfo;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Renew Your Account');
		return $breadcrumbs;
	}
}
