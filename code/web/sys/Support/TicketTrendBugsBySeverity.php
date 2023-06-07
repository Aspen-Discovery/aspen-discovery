<?php

class TicketTrendBugsBySeverity extends DataObject {
	public $__table = 'ticket_trend_bugs_by_severity';
	public $id;
	public $year;
	public $month;
	public $day;
	public $severity;
	public $count;
}