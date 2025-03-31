<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';

class Testing_SIPTester extends Admin_Admin {
	function launch() : void {
		global $interface;
		if (isset($_REQUEST['testConnection'])) {
			$sipHost = $_REQUEST['sipHost'] ?? null;
			$sipPort = $_REQUEST['sipPort'] ?? null;
			$sipUser = $_REQUEST['sipUser'] ?? null;
			$sipPassword = $_REQUEST['sipPassword'] ?? null;
			$patronBarcode = $_REQUEST['patronBarcode'] ?? null;
			$patronPin = $_REQUEST['patronPin'] ?? null;
			$interface->assign('sipHost', $sipHost);
			$interface->assign('sipPort', $sipPort);
			$interface->assign('sipUser', $sipUser);
			$interface->assign('sipPassword', $sipPassword);
			$interface->assign('patronBarcode', $patronBarcode);
			$interface->assign('patronPin', $patronPin);

			$results = [
				'success' => false,
			];
			if (!empty($sipHost) && !empty($sipPort)) {
				$mySip = new sip2();
				$mySip->hostname = $sipHost;
				$mySip->port = $sipPort;

				$mySip->debug = true;

				if ($mySip->connect($sipUser, $sipPassword)) {
					$results['success'] = true;
					$results['message'] = 'Connection succeeded';

					$scMessage = $mySip->msgSCStatus();
					$scResult = $mySip->get_message($scMessage);
					$results['message'] .= '<br/>' . $scMessage;
					$results['message'] .= '<br/>' . $scResult;

					if (str_starts_with($scResult, "98")) {
						$results['message'] .= '<br/>ACS Status check succeeded';

						$result = $mySip->parseACSStatusResponse($scResult);
						//  Use the result to populate SIP2 settings
						$mySip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
						if (isset($result['variable']['AN'])) {
							$mySip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
						}

						if (!empty($patronBarcode) && !empty($patronPin)) {

							$mySip->patron = $patronBarcode;
							$mySip->patronpwd = $patronPin;

							$patronStatusMsg = $mySip->msgPatronStatusRequest();
							$patronStatusResult = $mySip->get_message($patronStatusMsg);

							$results['message'] .= '<br/>' . $patronStatusMsg;
							$results['message'] .= '<br/>Patron Status Result: ' . $patronStatusResult;

							// Make sure the response is 24 as expected
							if (str_starts_with($patronStatusResult, "24")) {
								$patronInfoResponse = $mySip->parsePatronStatusResponse($patronStatusResult);
								$results['message'] .= '<br/>Patron validated successfully';
							}else{
								$results['message'] .= '<br/>Got invalid patron info response';
							}
						}
					}else{
						$results['message'] .= '<br/>ACS Status check failed';
					}

					$mySip->disconnect();
				}else{
					$results['message'] = 'Connection failed, additional details in messages.log';
					$results['message'] .= '<br/>' . $mySip->lastMessageSent;
					$results['message'] .= '<br/>' . $mySip->lastResponse;
				}
			}else{
				$results['message'] = 'The IP and port must be provided.';
			}
			$interface->assign('results', $results);
		}

		$this->display('sipTester.tpl', 'SIP Tester', 'Greenhouse/greenhouse-sidebar.tpl');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Testing/SIPTester', 'SIP Tester', true);
		return $breadcrumbs;
	}

	function canView() : bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->isAspenAdminUser()) {
				return true;
			}
		}
		return false;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}
}