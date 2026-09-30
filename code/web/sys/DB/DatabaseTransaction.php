<?php

class DatabaseTransaction {
	/*
	* Implements atomicity for callables where multiple DB operation must run
	* for the process to be considered succesful. Prevents partial writes.
	* If applied to multi-item processing: any failure will revert 
	* changes to all relevant items.
	*
	* The callable signals failure the Aspen way, by returning false. 
	* runInTransaction catches unexpected throws and also handles false
	* callable return values.
	*
	* Must be called inside a try catch block.
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
			if ($result === false) {
				if ($transactionOwnedByCurrentCall) {
					$aspen_db->rollBack();
				}
				throw new RuntimeException('An error occurred while writing to the database and the transaction was rolledback.');
			}
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
}
