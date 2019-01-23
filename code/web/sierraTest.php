<?php
ini_set('display_errors', true);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once 'bootstrap.php';

require_once ROOT_DIR . '/Drivers/Sierra.php';

$driver = new Sierra();

/*$bibData = $driver->getBib('1341330');
print_r($bibData);

$marcData = $driver->getMarc('1341330');
print_r($marcData);*/

//Load a particular item
/*$itemData = $driver->getItemInfo('1341330');
print_r($itemData);*/

//Load items for a bib
/*$itemData = $driver->getItemsForBib('3924426');
print_r($itemData);*/

//Load items for a magazine
echo("<h2>Items for climbing magazine</h2>");
$itemData = $driver->getItemsForBib('1075325');
print_r($itemData);


//Load bibs changed since a specific date
echo("<h2>Bibs Added since 3-1-2014</h2>");
$bibsCreated = $driver->getBibsCreatedSince('2014-03-01T00:00:00Z');
echo('Found ' . count($bibsCreated) . ' records.<br/>');
echo(implode(", ", $bibsCreated));

//Load bibs changed since a specific date
echo("<h2>Bibs Changed since 4-1-2014</h2>");
$bibsChanged = $driver->getBibsChangedSince('2014-04-01T00:00:00Z');
echo('Found ' . count($bibsChanged) . ' records.<br/>');
echo(implode(", ", $bibsChanged));

//Load bibs changed since a specific date
echo("<h2>Bibs Deleted since 3-1-2014</h2>");
$bibsDeleted = $driver->getBibsDeletedSince('2014-03-01T00:00:00Z');
echo('Found ' . count($bibsDeleted) . ' records.<br/>');
echo(implode(", ", $bibsDeleted));