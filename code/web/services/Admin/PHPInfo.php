<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Admin_PHPInfo extends Admin_Admin {
	function launch() {
		global $interface;

		ob_start();
		phpinfo();
		$info = ob_get_contents();
		ob_end_clean();

		// clean off unneeded html
		$info = strstr($info, '<div');
		$info = substr($info, 0,strrpos($info, '</div>')+6); //+6 to include closing tag
		// re-add slightly modified styling

		$info .= '<style type="text/css">
#maincontent {background-color: #ffffff; color: #000000;}
#maincontent, td, th, h1, h2 {font-family: sans-serif;}
pre {margin: 0px; font-family: monospace;}
#maincontent a:link {color: #000099; text-decoration: none; background-color: #ffffff;}
#maincontent a:hover {text-decoration: underline;}
#maincontent table {border-collapse: collapse;}
.center {text-align: center;}
.center table { margin-left: auto; margin-right: auto; text-align: left;}
.center th { text-align: center !important; }
td, th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
h1 {font-size: 150%;}
h2 {font-size: 125%;}
.p {text-align: left;}
.e {background-color: #ccccff; font-weight: bold; color: #000000;}
.h {background-color: #9999cc; font-weight: bold; color: #000000;}
.v {background-color: #cccccc; color: #000000;}
.vr {background-color: #cccccc; text-align: right; color: #000000;}
#maincontent img {float: right; border: 0px;}
#maincontent hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
</style>';

		$interface->assign("info", $info);
		$interface->assign('title', 'PHP Information');

		$interface->assign('sidebar', 'Search/home-sidebar.tpl');
		$interface->setTemplate('adminInfo.tpl');
		$interface->setPageTitle('PHP Information');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles() {
		return array('opacAdmin');
	}
}