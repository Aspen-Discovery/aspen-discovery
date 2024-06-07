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
			$reason = $_REQUEST['reason'];
			$product = $_REQUEST['product'];
			$sharepass = $_REQUEST['sharepass'] ?? null;
			$example1 = $_REQUEST['example1'];
			$example2 = $_REQUEST['example2'];
			$example3 = $_REQUEST['example3'];
			$attachments = $_FILES['attachments'] ?? [];

			global $serverName;
			require_once ROOT_DIR . '/sys/Email/Mailer.php';
			$mailer = new Mailer();
			$description .= "\n";
			$description .= 'Server: ' . $serverName . "\n";
			$description .= 'From: ' . $name . "\n\n";
			$description .= 'Reason: ' . $reason . "\n";
			$description .= 'Product: ' . $product . "\n";

			if($example1) {
				$description .= 'Example 1: ' . $example1 . "\n";
			}

			if($example2) {
				$description .= 'Example 2: ' . $example2 . "\n";
			}

			if($example3) {
				$description .= 'Example 3: ' . $example3 . "\n";
			}

			if($sharepass) {
				$description .= 'Sharepass: ' . $sharepass . "\n";
			}

			$fileTooBig = false;
			if($_FILES) {
				$i = 0;
				foreach ($_FILES as $file) {
					$fileSize = $file['size'][$i];
					$maxFileSize = 10 * 1048576; // 10MB for RT limitations
					if($fileSize > $maxFileSize) {
						$fileTooBig = true;
					}
					$i++;

				}
			}

			// don't lose already entered in data if something goes wrong on the form
			$interface->assign('name', $name);
			$interface->assign('email', $email);
			$interface->assign('subject', $subject);
			$interface->assign('description', $_REQUEST['description']);
			$interface->assign('reason', $reason);
			$interface->assign('product', $product);
			$interface->assign('sharepass', $sharepass);
			$interface->assign('example1', $example1);
			$interface->assign('example2', $example2);
			$interface->assign('example3', $example3);

			if($fileTooBig) {
				$interface->assign('error', 'One or more of your files was larger than the 10MB file size limit.');
				$this->display('submitTicket.tpl', 'Submit Ticket');
				return;
			}


			$message = '';
			try {
				require_once ROOT_DIR . '/sys/SystemVariables.php';
				$systemVariables = new SystemVariables();
				if ($systemVariables->find(true) && !empty($systemVariables->ticketEmail)) {
					$result = $mailer->send($systemVariables->ticketEmail, "Aspen Discovery: $subject", $description, $email, null, $attachments);
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
				header('Location: /Admin/SubmitTicketResults?success=true');
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