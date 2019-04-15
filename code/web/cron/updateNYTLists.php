<?php

require_once __DIR__ . '/../bootstrap.php';
require_once ROOT_DIR . '/services/API/ListAPI.php';

// instantiate class with api key
require_once ROOT_DIR . '/sys/NYTApi.php';

global $configArray;
$api_key = $configArray['NYT_API']['books_API_key'];
$nyt_api = new NYTApi($api_key);

//Get the raw response from the API with a list of all the names
$availableListsRaw = $nyt_api->get_list('names');
//Convert into an object that can be processed
$availableLists = json_decode($availableListsRaw);

$listAPI = new ListAPI();

if (isset($availableLists->results)) {
    $allListsNames = [];
    foreach ($availableLists->results as $listInfo) {
        $allListsNames[] = $listInfo->list_name_encoded;
    }

    foreach ($allListsNames as $listName) {
        echo("Updating $listName\r\n");
        $listAPI->createUserListFromNYT($listName);
        //Make sure we don't hit our quota.  Wait between updates
        sleep(6);
    }
}

echo("Finished updating lists\r\n");