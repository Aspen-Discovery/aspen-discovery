<?php
require_once ROOT_DIR . '/Action.php';
class Help_CookieConsentPrivacyPolicy extends Action {
    /** @var Library */
    private $library;

    function __construct() {
        parent::__construct();

        require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';

        global $interface;

        $id = strip_tags($_REQUEST['id']);
        $this->library = new Library();
        $this->library->id = $id;

        if (!$this->library->find(true)) {
            $interface->assign('module', 'Error');
            $interface->assign('action', 'Handle404');
            require_once ROOT_DIR . '/services/Error/Handle404.php';
            $actionClass = new Error_Handle404();
            $actionClass->launch->launch();
            die();
        } elseif (!$this->canView()) {
            $interface->assign('module', 'Error');
			$interface->assign('action', 'Handle401');
			$interface->assign('followupModule', 'Help');
			$interface->assign('followupAction', 'CookieConsentPrivacyPolicy');
			$interface->assign('id', $id);
            require_once ROOT_DIR . "/services/Error/Handle401.php";
			$actionClass = new Error_Handle401();
			$actionClass->launch();
			die();
        }
    }

    function launch() {
        global $interface;
        $cookieConsentPolicyHTML = $this->library->cookiePolicyHTML;
        $interface->assign('cookieConsentPolicyHTML', $cookieConsentPolicyHTML);
        $this->display('cookieConsentPrivacyPolicy.tpl', 'CookieConsentPrivacyPolicy');
    }

    function canView(): bool {
        return true;
    }

    function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        return $breadcrumbs;
    }

}