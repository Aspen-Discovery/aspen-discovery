<?php
require_once ROOT_DIR . '/Action.php';

/** @noinspection PhpUnused */
class RBdigital_AccessOnline extends Action
{
    function launch(){
        global $interface;

        $id = strip_tags($_REQUEST['id']);
        $interface->assign('id', $id);
        require_once ROOT_DIR . '/RecordDrivers/RBdigitalRecordDriver.php';
        $recordDriver = new RBdigitalRecordDriver($id);

        $user = UserAccount::getLoggedInUser();

        if ($user){
            $patronId = $_REQUEST['patronId'];
            $patron = $user->getUserReferredTo($patronId);
            if ($patron){
                if (!$recordDriver->isValid()){
                    $this->display('../Record/invalidRecord.tpl', 'Invalid Record');
                    die();
                }

                //Do the redirection
                require_once ROOT_DIR . '/Drivers/RBdigitalDriver.php';
                $rbdigitalDriver = new RBdigitalDriver();
                $rbdigitalDriver->redirectToRBdigital($patron, $recordDriver);
                //We don't actually get to here since the redirect happens above
                die();
            }else{
                AspenError::raiseError('Sorry, it looks like you don\'t have permissions to place holds for that user.');
            }
        }else{
            AspenError::raiseError('You must be logged in to place a hold.');
        }
    }
}