<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsModifier
 */
/**
 * Smarty format_datetime_locale modifier plugin
 * Type:     modifier
 * Name:     format_datetime_locale
 * Purpose:  format a datetime as a locale-aware date plus a forced 12-hour time
 * Input:
 *          - value: input datetime (DateTime object, string, or timestamp)
 *          - date_style: date style (short, medium, long, full)
 *
 * @param mixed  $value      input datetime (DateTime object, string, or timestamp)
 * @param string $date_style date style (short, medium, long, full)
 *
 * @return string formatted date and 12-hour time
 */
function smarty_modifier_format_datetime_locale($value, $date_style = 'long')
{
	require_once ROOT_DIR . '/sys/Utils/DateUtils.php';
	return DateUtils::formatDateTimeLocale($value, $date_style);
}
