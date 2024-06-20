<?php
 require_once ROOT_DIR . '/sys/WebBuilder/Template.php';

class WebBuilder_GrapesJSTemplates extends Action {

    /** @var Template */
    private $template;

    function launch() {
        $this->display('createTemplatejs.tpl', '', '', false);
    }

    function getBreadCrumbs(): array {
        $breadcrumbs = [];
        $breadcrumbs[] = new Breadcrumb('/', 'Home');
        if ($this->template != null) {
            $breadcrumbs[] = new Breadcrumb('', $this->template->title, true);
            // $breadcrumbs[] = new Breadcrumb('/WebBuilder/GrapesPages?id=')
        }
        return $breadcrumbs;
    }
}