<?php
require_once ROOT_DIR . '/JSON_Action.php';

/**
 * Exposes server-side input validation to forms so pre-submission checks
 * agree with the authoritative save-time gate. This lets the client block an
 * invalid value before submit instead of letting the save fail and drop the
 * user's unsaved changes.
 *
 * The intent is to add a method here only when the rule cannot be replicated
 * client-side (e.g. PCRE validity). Each method validates a single posted value
 * and returns whether it is valid.
 */

class InputValidation_AJAX extends JSON_Action {
	/** @noinspection PhpUnused */
	public function validateRegularExpression() : array {
		require_once ROOT_DIR . '/sys/DataObjectUtil.php';
		$valid = DataObjectUtil::isValidRegularExpression($_REQUEST['value'] ?? '');
		return [
			'valid' => $valid,
			'message' => translate([
				'text' => $valid ? '' : 'This is not a valid regular expression.',
				'isAdminFacing' => true,
			]),
		];
	}
}