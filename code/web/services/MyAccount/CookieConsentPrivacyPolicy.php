<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';
class CookieConsentPrivacyPolicy extends MyAccount {

    function launch() {
        $this->display('cookieConsentPrivacyPolicy.tpl', 'CookieConsentPrivacyPolicy');
    }

    function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        return $breadcrumbs;
    }

}