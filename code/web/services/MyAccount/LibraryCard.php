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
		$interface->assign('showAlternateLibraryCardPassword', $library->showAlternateLibraryCardPassword);
		$interface->assign('alternateLibraryCardLabel', $library->alternateLibraryCardLabel);
		$interface->assign('alternateLibraryCardPasswordLabel', $library->alternateLibraryCardPasswordLabel);
		$interface->assign('alternateLibraryCardStyle', $library->alternateLibraryCardStyle);
		$interface->assign('showCardExpirationDate', $library->showCardExpirationDate);
		$interface->assign('expirationDate', $user->getAccountSummary()->expirationDate);

		$linkedUsers = $user->getLinkedUsers();
		$linkedCards = [];
		foreach ($linkedUsers as $tmpUser) {
			$tmpUser->loadContactInformation();
			$linkedCards[] = [
				'id' => $tmpUser->id,
				'fullName' => $tmpUser->displayName,
				'barcode' => $tmpUser->getBarcode(),
				'expirationDate' => $tmpUser->getAccountSummary()->expirationDate,
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