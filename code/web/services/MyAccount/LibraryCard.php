<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class LibraryCard extends MyAccount {

	function launch() {
		global $interface;
		global $library;
		$user = UserAccount::getLoggedInUser();
		$user->loadContactInformation();

		$interface->assign('libraryCardBarcodeStyle', $library->libraryCardBarcodeStyle);
		$interface->assign('showAlternateLibraryCard', $library->showAlternateLibraryCard);
		$interface->assign('alternateLibraryCardFormMessage', $library->alternateLibraryCardFormMessage);
		$interface->assign('showAlternateLibraryCardPassword', $library->showAlternateLibraryCardPassword);
		$interface->assign('alternateLibraryCardLabel', $library->alternateLibraryCardLabel);
		$interface->assign('alternateLibraryCardPasswordLabel', $library->alternateLibraryCardPasswordLabel);
		$interface->assign('alternateLibraryCardStyle', $library->alternateLibraryCardStyle);
		$interface->assign('showCardExpirationDate', $library->showCardExpirationDate);
		$interface->assign('expirationDate', $user->getAccountSummary()->expirationDate);
		$interface->assign('showPatronTypeOnCard', $library->showPatronTypeOnCard ?? false);

		$interface->assign('showRenewalLink', false);
		if ($user->hasIlsConnection()) {
			$ilsSummary = $user->getAccountSummary();
			$showRenewalLink = $user->showRenewalLink($ilsSummary);
			$interface->assign('showRenewalLink', $showRenewalLink);
			if ($showRenewalLink) {
				$renewalConfig = $user->getHomeLibrary()->getCardRenewalConfig();
				if ($renewalConfig['externalLink'] !== null) {
					$interface->assign('cardRenewalLink', $renewalConfig['externalLink']);
				}
			}
		}

		$linkedUsers = $user->getLinkedUsers();
		$linkedCards = [];
		foreach ($linkedUsers as $tmpUser) {
			$tmpUser->loadContactInformation();
			$linkedCards[] = [
				'id' => $tmpUser->id,
				'fullName' => $tmpUser->displayName,
				'barcode' => $tmpUser->getBarcode(),
				'expirationDate' => $tmpUser->getAccountSummary()->expirationDate,
				'patronType' => $tmpUser->getPTypeObj() ? $tmpUser->getPTypeObj()->pType : null,
			];
		}
		$interface->assign('linkedCards', $linkedCards);

		if (isset($_REQUEST['submit'])) {
			if (isset($_REQUEST['alternateLibraryCard'])) {
				$user->alternateLibraryCard = $_REQUEST['alternateLibraryCard'];
			}
			if (isset($_REQUEST['alternateLibraryCardPassword'])) {
				$user->alternateLibraryCardPassword = $_REQUEST['alternateLibraryCardPassword'];
			}
			$user->update();
			$updateMessage = translate([
				'text' => 'Your alternate library card has been updated.',
				'isPublicFacing' => true,
			]);
			$interface->assign('updateMessage', $updateMessage);
		}

		$interface->assign('profile', $user);

		$this->display('libraryCard.tpl', 'Library Card');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'My Library Card');
		return $breadcrumbs;
	}
}