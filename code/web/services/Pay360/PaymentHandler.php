<?php
require_once ROOT_DIR . '/sys/Account/UserPayment.php';
require_once ROOT_DIR . '/services/Pay360/Client.php';

class Pay360_PaymentHandler {

	public static function validateRequest(): ?array {
		global $configArray;

		if (
			!isset($_REQUEST['paymentId']) ||
			!isset($_REQUEST['settingsId']) ||
			!is_numeric($_REQUEST['paymentId']) ||
			!is_numeric($_REQUEST['settingsId'])
		) {
			header("Location: " . $configArray['Site']['url']);
			return null;
		}

		$paymentId = intval($_REQUEST['paymentId']);
		$pay360SettingsId = intval($_REQUEST['settingsId']);

		$payment = new UserPayment();
		$payment->id = $paymentId;
		$payment->find(true);
		if ($payment->userId !== UserAccount::getActiveUserId()) {
			header("Location: " . $configArray['Site']['url']);
			return null;
		}

		return [
			'paymentId' => $paymentId,
			'pay360SettingsId' => $pay360SettingsId,
			'payment' => $payment,
		];
	}

	public static function completeOrder(): void {
		self::executeOutcome(true);
	}

	public static function handleNotAttempted(): void {
		self::executeOutcome(false);
	}

	private static function executeOutcome(bool $attempted): void {
		global $configArray;
		$validated = self::validateRequest();
		if ($validated === null) {
			return;
		}

		$paymentId = $validated['paymentId'];
		$pay360SettingsId = $validated['pay360SettingsId'];
		$payment = $validated['payment'];

		$client = new Pay360_Client($pay360SettingsId, $paymentId, [], null, false, $payment);
		$client->getOrderStatus(true);
		$client->handleOutcome([], $attempted);
		header("Location: " . $configArray['Site']['url'] . "/MyAccount/PaymentDetails?paymentId=" . $paymentId);
	}

	public static function spawnPoller(int $pay360SettingsId, int $paymentId): void {
		global $configArray;
		$serverName = $_SERVER['aspen_server'];
		$logFilePath = '/var/log/' . $configArray['System']['applicationName'] . '/' . $serverName . '/messages.log';
		$pollCommand = 'php ' . ROOT_DIR . "/scripts/pay360-poll.php $serverName " . escapeshellarg($pay360SettingsId) . ' ' . escapeshellarg($paymentId) . ' >> ' . escapeshellarg($logFilePath) . ' 2>&1 &';
		exec($pollCommand);
	}
}
