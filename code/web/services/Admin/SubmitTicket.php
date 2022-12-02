<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';

class SubmitTicket extends Admin_Admin {
	function launch() {
		global $interface;
		$user = UserAccount::getActiveUserObj();
		$interface->assign('name', $user->firstname . ' ' . $user->lastname);
		$interface->assign('email', $user->email);

		if (isset($_REQUEST['submitTicket'])) {
			$subject = $_REQUEST['subject'];
			$description = $_REQUEST['description'];
			$email = $_REQUEST['email'];
			$name = $_REQUEST['name'];
			$criticality = $_REQUEST['criticality'];
			if (isset($_REQUEST['component'])) {
				$component = $_REQUEST['component'];
				if (is_array($component)) {
					$component = implode(', ', $component);
				}
			} else {
				$component = '';
			}


			global $serverName;
			require_once ROOT_DIR . '/sys/Email/Mailer.php';
			$mailer = new Mailer();
			$description .= "\n";
			$description .= 'Server: ' . $serverName . "\n";
			$description .= 'From: ' . $name . "\n";
			$description .= 'Criticality: ' . $criticality . "\n";
			$description .= 'Component: ' . $component . "\n";

			$message = '';
			try {
				require_once ROOT_DIR . '/sys/SystemVariables.php';
				$systemVariables = new SystemVariables();
				if ($systemVariables->find(true) && !empty($systemVariables->ticketEmail)) {
					$result = $mailer->send($systemVariables->ticketEmail, "Aspen Discovery: $subject", $description, $email);
					if (!$result) {
						$message = 'Could not submit ticket via Aspen mailer';
					}
				} else {
					$result = false;
					$message = 'Could not find ticket email to submit to';
				}
			} catch (Exception $e) {
				//This happens when the table has not been created
				$result = false;
				$message = 'System Variables has not been created, could not find ticket email to submit to';
			}
			if ($result == true) {
				$this->display('submitTicketSuccess.tpl', 'Submit Ticket');
				die();
			} else {
				$interface->assign('error', 'There was an error submitting your ticket. ' . $message);
			}
		}

		$this->display('submitTicket.tpl', 'Submit Ticket');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#support', 'Aspen Discovery Support');
		$breadcrumbs[] = new Breadcrumb('', 'Submit Support Ticket');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'support';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Submit Ticket');
	}
}