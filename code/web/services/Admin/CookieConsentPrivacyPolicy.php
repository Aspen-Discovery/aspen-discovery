<?php

class Admin_CookieConsentPrivacyPolicy extends Action {

    function launch() {
        $this->display('cookieConsentPrivacyPolicy.tpl', 'Cookie Conset Privacy Policy');
    }

    function getBreadcrumbs(): array {
        $breadcrumbs = [];
        return $breadcrumbs;
    }
}