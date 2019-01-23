<?php

/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 2/9/2016
 *
 */

require_once ROOT_DIR . '/RecordDrivers/IslandoraDriver.php';
class NewsPaperDriver extends IslandoraDriver {


	public function getViewAction() {
		return 'Newspaper';
	}
}