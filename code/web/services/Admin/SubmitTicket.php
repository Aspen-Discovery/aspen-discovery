<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';

class SubmitTicket extends Admin_Admin
{
	function launch()
	{
		global $interface;
		$user = UserAccount::getActiveUserObj();
		$interface->assign('name', $user->firstname . ' '. $user->lastname);
		$interface->assign('email', $user->email);

		if (isset($_REQUEST['submitTicket'])){
			$subject = $_REQUEST['subject'];
			$description = $_REQUEST['description'];
			$email = $_REQUEST['email'];
			$name = $_REQUEST['name'];
			$criticality = $_REQUEST['criticality'];
			$component = $_REQUEST['component'];
			if (is_array($component)){
				$component = implode(', ', $component);
			}

			global $serverName;
			require_once ROOT_DIR . '/sys/Email/Mailer.php';
			$mailer = new Mailer();
			$description .= "\n";
			$description .= 'Server: ' . $serverName . "\n";
			$description .= 'From: ' . $name . "\n";
			$description .= 'Criticality: ' . $criticality . "\n";
			$description .= 'Component: ' . $component . "\n";

			$result = $mailer->send("aspensupport@bywatersolutions.com", "Aspen Discovery: $subject", $description, $email);
			if ($result == true){
				$this->display('submitTicketSuccess.tpl', 'Submit Ticket');
				die();
			}else{
				$interface->assign('error', 'There was an error submitting your ticket. ' . $result->message);
			}
		}

		$this->display('submitTicket.tpl', 'Submit Ticket');
	}

	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin');
	}
}