<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {implode} function plugin
 *
 * Name:     implode<br>
 * Purpose:  glue an array together as a string, with supplied string glue, and assign it to the template
 * @link http://smarty.php.net/manual/en/language.function.implode.php {implode}
 *       (Smarty online manual)
 * @author Will Mason <will at dontblinkdesign dot com>
 * @param array $params
 * @param UInterface $smarty
 * @return null|string
 */
function smarty_function_implode($params, &$smarty)
{
	if (!isset($params['subject'])) {
		$smarty->trigger_error("implode: missing 'subject' parameter");
		return;
	}

	if (!isset($params['glue'])) {
		$params['glue'] = ", ";
	}

	$subject = $params['subject'];

	$implodedValue = null;
	if (is_array($subject)){
		if (isset($params['sort'])){
			sort($subject);
		}
		$implodedValue = implode($params['glue'], $subject);
	}else{
		$implodedValue = $subject;
	}

	if (!isset($params['assign'])) {
		return $implodedValue;
	}else{
		$smarty->assign($params['assign'], $implodedValue);
	}
}