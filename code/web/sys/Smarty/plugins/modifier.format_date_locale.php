<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsModifier
 */
/**
 * Smarty format_date_locale modifier plugin
 * Type:     modifier
 * Name:     format_date_locale
 * Purpose:  format dates using locale-aware formatting via IntlDateFormatter
 * Input:
 *          - string: input date string or timestamp
 *          - style: date style (short, medium, long, full)
 *          - time_style: time style (none, short, medium, long, full)
 *          - pattern: optional custom ICU date pattern (e.g., 'MMMM yyyy' for "March 2026")
 *
 * @param mixed  $string     input date string or timestamp
 * @param string $style      date style (short, medium, long, full)
 * @param string $time_style time style (none, short, medium, long, full)
 * @param string $pattern    optional custom ICU date pattern
 *
 * @return string formatted date
 */
function smarty_modifier_format_date_locale($string, $style = 'medium', $time_style = 'none', $pattern = null)
{
	require_once ROOT_DIR . '/sys/Utils/DateUtils.php';
	return DateUtils::formatDateLocale($string, $style, $time_style, $pattern);
}
