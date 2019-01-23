<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/Action.php';

class Home extends Action {

	function launch()
	{
		header('Content-type: text/xml');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		$output = '<?xml version="1.0" encoding="UTF-8"?' . ">\n" .
                  "<AJAXResponse>\n";
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
			$output .= '<result>' . $this->$_GET['method']() . "</result>\n";
		} else {
			$output .= '<Error>Invalid Method</Error>';
		}
		$output .= '</AJAXResponse>';

		echo $output;
	}

	function Login()
	{
		global $configArray;

		// Fetch Salt
		$salt = $this->generateSalt();

		// HexDecode Password
		$password = pack('H*', $_GET['password']);

		// Decrypt Password
		/*
		 require_once 'Crypt/Blowfish.php';
		 $cipher = new Crypt_Blowfish($salt);
		 $password = $cipher->decrypt($_GET['password']);
		 */
		/*
		 require_once 'Crypt/XXTEA.php';
		 $cipher = new Crypt_XXTEA();
		 $cipher->setKey($salt);
		 $password = $cipher->decrypt($password);
		 */
		require_once 'Crypt/rc4.php';
		$password = rc4Encrypt($salt, $password);

		// Put the username/password in POST fields where the authentication module
		// expects to find them:
		$_POST['username'] = $_GET['username'];
		$_POST['password'] = $password;

		// Authenticate the user:
		$user = UserAccount::login();
		if (PEAR_Singleton::isError($user)) {
			return 'Error';
		} else {
			return 'True';
		}
	}

	function GetSalt()
	{
		return $this->generateSalt();
	}

	function generateSalt()
	{
		//return md5($_ENV['REMOTE_ADDR']);
		return str_replace('.', '', $_SERVER['REMOTE_ADDR']);
	}

	function isLoggedIn(){
		return UserAccount::isLoggedIn();
	}
}