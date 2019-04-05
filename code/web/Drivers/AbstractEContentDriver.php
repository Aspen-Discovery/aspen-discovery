<?php

require_once ROOT_DIR . '/Drivers/AbstractDriver.php';
abstract class AbstractEContentDriver extends AbstractDriver
{
    public abstract function getAccountSummary($patron);
}