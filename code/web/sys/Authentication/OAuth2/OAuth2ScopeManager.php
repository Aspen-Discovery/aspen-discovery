<?php
/**
 * OAuth2 Scope Manager
 * Provides utilities for managing and verifying OAuth2 client scopes
 */

require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2Client.php';

class OAuth2ScopeManager {

	/**
	 * Get all available scopes in the system
	 */
	public static function getAvailableScopes(): array {
		return OAuth2Client::getScopeOptions();
	}

	/**
	 * Get scopes assigned to a specific client
	 */
	public static function getClientScopes(string $clientId): array {
		$client = new OAuth2Client();
		$client->setClientId($clientId);
		$client->setIsActive(1);

		if ($client->find(true)) {
			return $client->getScopesArray();
		}

		return [];
	}

	/**
	 * Assign scopes to a client
	 * @param string $clientId The OAuth2 client ID
	 * @param array $scopes Array of scope identifiers to assign
	 * @return bool True if successful
	 */
	public static function assignScopesToClient(string $clientId, array $scopes): bool {
		$client = new OAuth2Client();
		$client->setClientId($clientId);
		$client->setIsActive(1);

		if (!$client->find(true)) {
			return false;
		}

		$availableScopes = self::getAvailableScopes();
		$validScopes = [];

		foreach ($scopes as $scope) {
			if (array_key_exists($scope, $availableScopes)) {
				$validScopes[] = $scope;
			}
		}

		if (empty($validScopes)) {
			return false;
		}

		// Log the scope assignment
		self::logScopeAssignment($clientId, $validScopes, 'assigned');

		// Update the client
		$client->scopes = $validScopes;
		$client->processScopes();
		return $client->update() !== false;
	}

	/**
	 * Verify that a client has required scopes
	 * @param string $clientId The OAuth2 client ID
	 * @param array $requiredScopes Array of required scope identifiers
	 * @return bool True if client has all required scopes
	 */
	public static function verifyClientScopes(string $clientId, array $requiredScopes): bool {
		$clientScopes = self::getClientScopes($clientId);

		foreach ($requiredScopes as $scope) {
			if (!in_array($scope, $clientScopes)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get a report of which clients have specific scopes
	 */
	public static function getClientsWithScope(string $scope): array {
		$result = [];
		$client = new OAuth2Client();
		$clients = $client->find();

		if ($clients) {
			while ($client->fetch()) {
				$scopes = $client->getScopesArray();
				if (in_array($scope, $scopes)) {
					$result[] = [
						'id' => $client->id,
						'name' => $client->name,
						'client_id' => $client->getClientId(),
						'scopes' => $scopes,
					];
				}
			}
		}

		return $result;
	}

	/**
	 * Get a scope assignment report for all clients
	 */
	public static function generateScopeAssignmentReport(): array {
		$report = [];
		$client = new OAuth2Client();
		$clients = $client->find();

		if ($clients) {
			while ($client->fetch()) {
				$report[] = [
					'id' => $client->id,
					'name' => $client->name,
					'client_id' => $client->getClientId(),
					'client_type' => $client->client_type,
					'is_active' => $client->is_active,
					'scopes' => $client->getScopesArray(),
					'scope_count' => count($client->getScopesArray()),
				];
			}
		}

		return $report;
	}

	/**
	 * Validate that all clients have appropriate scopes for their type
	 */
	public static function validateAllClientScopes(): array {
		$issues = [];
		$client = new OAuth2Client();
		$clients = $client->find();

		if ($clients) {
			while ($client->fetch()) {
				$scopes = $client->getScopesArray();

				if ($client->client_type === 'service_application' && empty($scopes)) {
					$issues[] = [
						'client_id' => $client->getClientId(),
						'client_name' => $client->name,
						'issue' => 'Service application has no scopes assigned',
						'severity' => 'high',
					];
				}

				if ($client->client_type === 'native_application' && count($scopes) > 5) {
					$issues[] = [
						'client_id' => $client->getClientId(),
						'client_name' => $client->name,
						'issue' => 'Native application has excessive scopes assigned',
						'severity' => 'medium',
						'scope_count' => count($scopes),
					];
				}

				$availableScopes = self::getAvailableScopes();
				foreach ($scopes as $scope) {
					if (!array_key_exists($scope, $availableScopes)) {
						$issues[] = [
							'client_id' => $client->getClientId(),
							'client_name' => $client->name,
							'issue' => 'Invalid scope assigned: ' . $scope,
							'severity' => 'high',
						];
					}
				}
			}
		}

		return $issues;
	}

	/**
	 * Log scope assignments for audit trail
	 */
	private static function logScopeAssignment(string $clientId, array $scopes, string $action, string $detail = ''): void {
		$logEntry = [
			'timestamp' => date('Y-m-d H:i:s'),
			'client_id' => $clientId,
			'action' => $action,
			'scopes' => $scopes,
			'scope_count' => count($scopes),
			'detail' => $detail,
			'user_id' => UserAccount::getActiveUserId() ?? 'system',
		];

		$logFile = ROOT_DIR . '/logs/oauth2_scope_assignments.log';
		if (!file_exists(dirname($logFile))) {
			mkdir(dirname($logFile), 0755, true);
		}

		file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);
	}

	/**
	 * Export a detailed scope audit log
	 */
	public static function getAuditLog(int $lines = 50): array {
		$logFile = ROOT_DIR . '/logs/oauth2_scope_assignments.log';
		if (!file_exists($logFile)) {
			return [];
		}

		$logs = [];
		$handle = fopen($logFile, 'r');
		$allLines = [];

		while (($line = fgets($handle)) !== false) {
			$allLines[] = trim($line);
		}
		fclose($handle);

		$lastLines = array_slice($allLines, -$lines);

		foreach ($lastLines as $line) {
			if (!empty($line)) {
				$logs[] = json_decode($line, true);
			}
		}

		return $logs;
	}
}

