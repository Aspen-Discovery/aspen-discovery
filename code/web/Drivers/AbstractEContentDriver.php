<?php

require_once ROOT_DIR . '/Drivers/AbstractDriver.php';
abstract class AbstractEContentDriver extends AbstractDriver
{
    public abstract function getAccountSummary($patron);

    /**
     * @param User $user
     * @param string $titleId
     *
     * @return array
     */
    public abstract function checkOutTitle($user, $titleId) ;
}