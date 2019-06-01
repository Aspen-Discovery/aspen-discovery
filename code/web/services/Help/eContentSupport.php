<?php

require_once ROOT_DIR . '/Action.php';

class eContentSupport extends Action
{
	function launch()
	{
		global $interface;
		global $configArray;
		$interface->setPageTitle('eContent Support');

		if (isset($_REQUEST['submit'])){
			//Email the library with details of the support request
			require_once ROOT_DIR . '/sys/Email/Mailer.php';
			$mail = new Mailer();
			$userLibrary = Library::getPatronHomeLibrary();
			if (!empty($userLibrary->eContentSupportAddress)){
				$to = $userLibrary->eContentSupportAddress;
			}elseif (!empty($configArray['Site']['email'])){
				$to = $configArray['Site']['email'];
			} else {
				echo(json_encode(array(
					'title' => "Support Request Not Sent",
					'message' => "<p>We're sorry, but your request could not be submitted because we do not have a support email address on file.</p><p>Please contact your local library.</p>"
				)));
				return;
			}
			$multipleEmailAddresses = preg_split('/[;,]/', $to, null, PREG_SPLIT_NO_EMPTY);
			if (!empty($multipleEmailAddresses)) {
				$sendingAddress = $multipleEmailAddresses[0];
			} else {
				$sendingAddress = $to;
			}

			$name = $_REQUEST['name'];
			$interface->assign('bookAuthor', $_REQUEST['bookAuthor']);
			$interface->assign('device', $_REQUEST['device']);
			$interface->assign('format', $_REQUEST['format']);
			$interface->assign('operatingSystem', $_REQUEST['operatingSystem']);
			$interface->assign('problem', $_REQUEST['problem']);

			$subject = 'eContent Support Request from ' . $name;
			$patronEmail = $_REQUEST['email'];

			$interface->assign('name', $name);
			$interface->assign('email', $patronEmail);

			$body = $interface->fetch('Help/eContentSupportEmail.tpl');
			$emailResult = $mail->send($to, $subject, $body, $patronEmail);
			if (PEAR::isError($emailResult)) {
				echo(json_encode(array(
					'title' => "Support Request Not Sent",
					'message' => "<p>We're sorry, an error occurred while submitting your request.</p>". $emailResult->getMessage()
				)));
			} elseif ($emailResult){
				echo(json_encode(array(
					'title' => "Support Request Sent",
					'message' => "<p>Your request was sent to our support team.  We will respond to your request as quickly as possible.</p><p>Thank you for using the catalog.</p>"
				  ,'body' => $body //TODO: remove this
				)));
			}else{
				echo(json_encode(array(
						'title' => "Support Request Not Sent",
						'message' => "<p>We're sorry, but your request could not be submitted to our support team at this time.</p><p>Please try again later.</p>"
				)));
			}
		}else{
			if (isset($_REQUEST['lightbox'])){
				$interface->assign('lightbox', true);
				$user = UserAccount::getLoggedInUser();
				if ($user){
					$name = $user->firstname .' '. $user->lastname;
					$interface->assign('name', $name);
					$interface->assign('email', $user->email);
				}
				$result = array(
						'title' => 'eContent Support',
						'modalBody' => $interface->fetch('Help/eContentSupport.tpl'),
						'modalButtons' => "<button class='btn btn-sm btn-primary' onclick='return VuFind.EContent.submitHelpForm();'>Submit</button>",
				);
				echo json_encode($result);
			}else{
				$interface->assign('lightbox', false);
				$this->display('eContentSupport.tpl', 'eContent Support');
			}
		}
	}
}

