<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';

class Greenhouse_ReadingHistoryReload extends Admin_Admin {
	function launch(): void {
		global $interface;
		if (isset($_REQUEST['submit'])) {
			$barcodesRaw = trim($_REQUEST['barcodes']);
			if (!empty($barcodesRaw)) {
				$barcodes = preg_split("/\\r\\n|\\r|\\n/", $barcodesRaw);
				$reloadResults = [];
				global $aspen_db;
				foreach ($barcodes as $barcode) {
					$foundUserForBarcode = false;
					foreach (UserAccount::getAccountProfiles() as $name => $accountProfileInfo) {
						$userToReset = new User();
						$userToReset->source = $name;
						$userToReset->ils_barcode = $barcode;
						if ($userToReset->find(true)) {
							$foundUserForBarcode = true;
							// Use raw SQL to properly set null values.
							/** @noinspection SqlDialectInspection */
							/** @noinspection SqlResolve */
							$updateSql = "
								UPDATE user
								SET initialReadingHistoryLoaded = 0,
									forceReadingHistoryLoad = 1,
									readingHistoryImportStartedAt = NULL
								WHERE id = :user_id
							";
							$updateStmt = $aspen_db->prepare($updateSql);
							$updateStmt->bindParam(':user_id', $userToReset->id, PDO::PARAM_INT);
							$updateStmt->execute();
						}
					}
					$reloadResults[] = [
						'barcode' => $barcode,
						'success' => $foundUserForBarcode,
					];
				}
			}
			else {
				$reloadResults[] = [
					'barcode' => "No valid barcodes were entered.",
					'success' => false,
				];
			}

			$interface->assign('reloadResults', $reloadResults);
		}

		$this->display('reloadReadingHistoryFromIls.tpl', 'Reload Reading History from ILS for User', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home#greenhouse-maintenance-tools', 'Maintenance Tools');
		$breadcrumbs[] = new Breadcrumb('', 'Reload Reading History for User');

		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::isLoggedIn() && UserAccount::getActiveUserObj()->isAspenAdminUser();
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}
}