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
 * Purpose:  format a datetime as a locale-aware date and time
 * Input:
 *          - value: input datetime (DateTime object, string, or timestamp)
 *          - date_style: date style (short, medium, long, full)
 *          - format: null (default) = the timeFormat system variable; 2 forces 12-hour, 3 forces 24-hour
 *
 * @param mixed    $value      input datetime (DateTime object, string, or timestamp)
 * @param string   $date_style date style (short, medium, long, full)
 * @param int|null $format     null (default) to use the timeFormat system variable, 2 for 12-hour or 3 for 24-hour
 *
 * @return string formatted date and time
 */
function smarty_modifier_format_datetime_locale($value, $date_style = 'long', ?int $format = null)
{
	require_once ROOT_DIR . '/sys/Utils/DateUtils.php';
	return DateUtils::formatDateTimeLocale($value, $date_style, $format);
}
