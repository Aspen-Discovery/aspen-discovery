<?php

class WebBuilder_GrapesJSEditor extends Action {
    /** @var GrapesPage */
    private $grapesPage;
  
    function __construct() {
        parent::__construct();
        require_once ROOT_DIR . '/sys/WebBuilder/GrapesPage.php';
    }

    function launch() {
        $this->display('grapesjs.tpl', '', '', false);
    }

    function getBreadcrumbs(): array {
        $breadcrumbs = [];
        $breadcrumbs[] = new Breadcrumb('/WebBuilder/GrapesPages', 'Grapes Pages');
        if ($this->grapesPage != null) {
            $breadcrumbs[] = new Breadcrumb('', $this->grapesPage->title, true);
            if (UserAccount::userHasPermission([
                'Administer All Grapes Pages',
                'Administer Library Grapes Pages',
            ])) {
                $breadcrumbs[] = new Breadcrumb('/WebBuilder/GrapesPages?id=' . $this->grapesPage->id . '&objectAction=edit', 'Edit', true);
            }
        }
        return $breadcrumbs;
    }

}