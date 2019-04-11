<?php


abstract class AbstractDriver
{
    public abstract function hasNativeReadingHistory();
    public function performsReadingHistoryUpdatesOfILS(){
        return false;
    }
    public function getReadingHistory(
        /** @noinspection PhpUnusedParameterInspection */ $user,
        /** @noinspection PhpUnusedParameterInspection */ $page = 1,
        /** @noinspection PhpUnusedParameterInspection */ $recordsPerPage = -1,
        /** @noinspection PhpUnusedParameterInspection */ $sortOption = "checkedOut") {
        return array('historyActive'=>false, 'titles'=>array(), 'numTitles'=> 0);
    }
    public function doReadingHistoryAction(
        /** @noinspection PhpUnusedParameterInspection */ $user,
        /** @noinspection PhpUnusedParameterInspection */ $action,
        /** @noinspection PhpUnusedParameterInspection */ $selectedTitles){
        return;
    }

    /**
     * Get Patron Checkouts
     *
     * This is responsible for retrieving all checkouts (i.e. checked out items)
     * by a specific patron.
     *
     * @param User $user    The user to load transactions for
     *
     * @return array        Array of the patron's transactions on success
     * @access public
     */
    public abstract function getCheckouts($user);

    /**
     * @return boolean true if the driver can renew all titles in a single pass
     */
    public abstract function hasFastRenewAll();

    /**
     * Renew all titles currently checked out to the user
     *
     * @param $patron  User
     * @return mixed
     */
    public abstract function renewAll($patron);

    /**
     * Renew a single title currently checked out to the user
     *
     * @param $patron     User
     * @param $recordId   string
     * @return mixed
     */
    public abstract function renewCheckout($patron, $recordId);

    public function hasHolds(){
        return true;
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds for a specific patron.
     *
     * @param User $user    The user to load transactions for
     *
     * @return array        Array of the patron's holds
     * @access public
     */
    public abstract function getHolds($user);

    /**
     * Place Hold
     *
     * This is responsible for both placing holds as well as placing recalls.
     *
     * @param   User        $patron         The User to place a hold for
     * @param   string      $recordId       The id of the bib record
     * @return  array                 An array with the following keys
     *                                result - true/false
     *                                message - the message to display (if item holds are required, this is a form to select the item).
     *                                needsItemLevelHold - An indicator that item level holds are required
     *                                title - the title of the record the user is placing a hold on
     * @access  public
     */
    public abstract function placeHold($patron, $recordId);

    /**
     * Cancels a hold for a patron
     *
     * @param   User    $patron     The User to cancel the hold for
     * @param   string  $recordId   The id of the bib record
     * @return  array
     */
    abstract function cancelHold($patron, $recordId);
}