<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/Account/UserILSMessage.php';

class MyAccount_NotificationHistory extends MyAccount {
	function launch() {
		global $interface;

		$userMessages = new UserILSMessage();
		$userMessages->userId = UserAccount::getActiveUserId();

		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$interface->assign('page', $page);

		$messagesPerPage = 20;
		$interface->assign('startingNumber', ($page - 1) * $messagesPerPage);
		$interface->assign('curPage', $page);
		$userMessages->orderBy('dateQueued DESC');
		$userMessages->limit(($page - 1) * $messagesPerPage, $messagesPerPage);
		$messageCount = $userMessages->count();
		$userMessages->find();
		$messages = [];
		while ($userMessages->fetch()) {
			$messages[] = clone $userMessages;
		}

		$interface->assign('userMessages', $messages);

		$options = [
			'totalItems' => $messageCount,
			'perPage' => $messagesPerPage,
		];
		$pager = new Pager($options);

		$interface->assign('pageLinks', $pager->getLinks());
		$this->display('../MyAccount/messages.tpl', 'Notification History');

	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Notification History');
		return $breadcrumbs;
	}
}