<?php

require_once 'Action.php';
class JSON_Action extends Action
{
    function launch() {
        global $timer;
        $method = $_GET['method'];
        $timer->logTime("Starting method $method");
        //JSON Responses
        header('Content-type: application/json');
        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        echo json_encode($this->$method());
    }
}