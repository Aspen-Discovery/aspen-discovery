<?php

/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 2/9/2016
 *
 */

require_once ROOT_DIR . '/RecordDrivers/IslandoraRecordDriver.php';
class NewsPaperRecordDriver extends IslandoraRecordDriver {


	public function getViewAction() {
		return 'Newspaper';
	}
}