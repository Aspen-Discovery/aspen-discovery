<?php

class DatabaseTransaction {
	/*
	* Implements atomicity for callables where multiple DB operation must run
	* for the process to be considered succesful. Prevents partial writes.
	* If applied to multi-item processing: any failure will revert 
	* changes to all relevant items.
	*/
	public static function runInTransaction(callable $work): mixed {
		global $aspen_db;
		if (!isset($aspen_db)) {
			throw new RuntimeException('Database connection not initialized; cannot run transaction.');
		}
		$transactionOwnedByCurrentCall = !$aspen_db->inTransaction();
		if ($transactionOwnedByCurrentCall) {
			$aspen_db->beginTransaction();
		}
		try {
			$result = $work();
			if ($transactionOwnedByCurrentCall) {
				$aspen_db->commit();
			}
			return $result;
		} catch (\Throwable $e) {
			if ($transactionOwnedByCurrentCall && $aspen_db->inTransaction()) {
				$aspen_db->rollBack();
			}
			throw $e;
		}
	}

	private static int $__savepointCounter = 0;

	/*
	 * Recommended for multi-row batch flows (applies atomicity on a per item basis).
	 * Allows partial success (if failure occurs while one item is being process,
	 * the failure is clean and complete for the item while processing carries on 
	 * to the next iteration. The transaction is not interrupted).
	 * 
	 * Note: requires an active outer transaction.
	 */
	public static function runInSavepoint(callable $work): mixed {
		global $aspen_db;
		if (!isset($aspen_db)) {
			throw new RuntimeException('Database connection not initialized; cannot create savepoint.');
		}
		if (!$aspen_db->inTransaction()) {
			throw new RuntimeException('Cannot create savepoint outside an active transaction; wrap the caller in runInTransaction().');
		}
		$savepoint = 'aspen_sp_' . ++self::$__savepointCounter;
		$aspen_db->exec('SAVEPOINT ' . $savepoint);
		try {
			$result = $work();
			$aspen_db->exec('RELEASE SAVEPOINT ' . $savepoint);
			return $result;
		} catch (\Throwable $e) {
			$aspen_db->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
			$aspen_db->exec('RELEASE SAVEPOINT ' . $savepoint);
			throw $e;
		}
	}
}
