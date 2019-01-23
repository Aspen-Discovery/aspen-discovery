<?php
/**
 * Base class for building Selenium tests.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @category VuFind
 * @package  Tests
 * @author   Preetha Rao <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */

/**
 * Set up test environment.
 */
require_once dirname(__FILE__) . '/../../web/prepend.inc.php';
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

/**
 * Base class for building Selenium tests.
 *
 * @category VuFind
 * @package  Tests
 * @author   Preetha Rao <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */
class SeleniumTestCase extends PHPUnit_Extensions_SeleniumTestCase
{
    protected $baseUrl;             // Base Url in our case http://localhost/vufind/"
    protected $hostname;            // name of the host on which application runs
    protected $selenium;
    protected $timeout = 30000;     // Default value of timeout is 30000ms ( 30 sec )

    /* Variables read from ini files */
    protected $sms_sending;         // string for sms sending message
    protected $sms_failure;         // string for sms error message
    protected $sms_success;         // string for sms success message

    protected $email_sending;       // string for email sending message
    protected $email_failure;       // string for email failure message
    protected $email_success;       // string for email success message
    protected $email_rcpt_err;      // string for email recipient error
    protected $email_sndr_err;      // string for email sender error

    protected $apa_text;            // APA citation text title
    protected $mla_test;            // MLA citation text title

    /* Record section table column names */
    protected $rec_row0,$rec_row1,$rec_row3,$rec_row4,$rec_row5,$rec_row6,$rec_row7;

    protected $def_rec_tab;          // Default record tab
    protected $debug =  false;       // Debug Flag

    protected $searchArray;          // Array of search options from searches.ini
    protected $languages;            // Array of language options from config.ini

    /**
     * Standard setup method.
     *
     * @return void
     * @access public
     */
    public function setUp()
    {
        $this->debug = getenv('TEST_DEBUG');
        $debStatus = $this->debug
            ? "SET" : "NOT SET (set it by setting env variable TEST_DEBUG to 1)";
        $this->debugPrint("Debug Flag is $debStatus\n");

        //echo "\nCalling Setup\n";
        $configArray = parse_ini_file(
            dirname(__FILE__) . '/../../../web/conf/config.ini', true
        );

        $this->searchArray = parse_ini_file(
            dirname(__FILE__) . '/../../../web/conf/searches.ini', true
        );

        // create the ini file name based on the langauge of the site.
        $langfile =  dirname(__FILE__) . '/../../../web/lang/' .
            $configArray['Site']['language'] . '.ini';
        $iniArray = parse_ini_file($langfile, false);

        // Read base URL from config file:
        $this->baseUrl = $configArray['Site']['url'];

        // Extract hostname from base URL:
        preg_match('/https?:\/\/([^\/]*).*/', $this->baseUrl, $matches);
        $this->hostname = $matches[1];

        // Set the debug flag value
         /*if ($configArray['System']['debug'])
         {
                 $this->debug = true;
         }*/

        // Extract the variables from en.ini file

        /*  CITE,EMAIL,TEXT functionality constants BEGIN */
        $this->sms_sending = $iniArray['sms_sending'];
        $this->sms_failure = preg_replace('/\s\s+/', ' ', $iniArray['sms_failure']);
        $this->sms_success = preg_replace('/\s\s+/', ' ', $iniArray['sms_success']);

        $this->email_sending  = preg_replace(
            '/\s\s+/', ' ', $iniArray['email_sending']
        );
        $this->email_failure  = preg_replace(
            '/\s\s+/', ' ', $iniArray['email_failure']
        );
        $this->email_success  = preg_replace(
            '/\s\s+/', ' ', $iniArray['email_success']
        );
        $this->email_rcpt_err = preg_replace(
            '/\s\s+/', ' ', $iniArray['Invalid Recipient Email Address']
        );
        $this->email_sndr_err = preg_replace(
            '/\s\s+/', ' ', $iniArray['Invalid Sender Email Address']
        );
        $this->apa_text    = preg_replace('/\s\s+/', ' ', $iniArray['APA Citation']);
        $this->mla_text    = preg_replace('/\s\s+/', ' ', $iniArray['MLA Citation']);

        /*  CITE,EMAIL,TEXT functionality constants END */

        /*  Bibliographic Details constants BEGIN */
        $this->rec_row0 = $iniArray['New Title'];
        $this->rec_row1 = $iniArray['Previous Title'];
        $this->rec_row2 = $iniArray['Other Authors'];
        $this->rec_row3 = $iniArray['Format'];
        $this->rec_row4 = $iniArray['Language'];
        $this->rec_row5 = $iniArray['Published'];
        $this->rec_row6 = $iniArray['Subjects'];
        $this->rec_row7 = $iniArray['Tags'];

        /*  Bibliographic Details constants END */

        $this->def_rec_tab = $configArray['Site']['defaultRecordTab'];

        /* Language options in config file */
        $this->languages = $configArray['Languages'];

        $this->setBrowser('*firefox');
        $this->setBrowserUrl($this->baseUrl);
    }

    /**
     * Standard teardown method.
     *
     * @return void
     * @access public
     */
    public function tearDown()
    {
        $this->stop();
    }

    /**
     * Common function to verify that a given table contents are correct
     * Output : Message stating the matching rows and unmatched rows
     *
     * @param string $tablename Class of table to read
     * @param string $parentdiv Class of div containing table to read
     * @param array  $tablearr  Associative array of key=> value pair where "key"
     * is the column1 value and "value" is column2 value of table
     *
     * @return void
     * @access public
     */
    public function validateTable($tablename, $parentdiv, $tablearr)
    {
        $tablecnt = $this->getXpathCount(
            "//div[@class='$parentdiv']/table[@class='$tablename']/tbody/tr"
        );

        if (count($tablearr)) {
            $this->debugPrint(
                "\nValidating table class $tablename under class $parentdiv\n"
            );

            try {
                // Scan through the table
                for ($i = 1;$i <= $tablecnt;$i++) {
                    $baseXpath = "css=div.$parentdiv table.$tablename" .
                        ">tbody>tr:nth-child($i)";
                    $table_key = chop($this->getText($baseXpath . ">th"), ":");
                    if (array_key_exists($table_key, $tablearr)) {
                        $this->debugPrint(
                            "\n$i Test Key:$table_key\t " .
                            "Test Value:$tablearr[$table_key]" .
                            "\n   Table Key:" . $this->getText($baseXpath . ">th").
                            "\t Table Value:" . $this->getText($baseXpath . ">td")
                        );
                        $this->assertElementContainsText(
                            $baseXpath . ">td", $tablearr[$table_key], "Matched"
                        );
                    }
                }
            } catch (PHPUnit_Framework_Exception $e) {
                 $this->debugPrint("Mismatch in record # $i");
            }
        } else {
            $this->debugPrint("\nNo input records to validate\n");
        }
    }

    /**
     * Echo a message if debug is enabled.
     *
     * @param string $msg Message to print
     *
     * @return void
     * @access private
     */
    protected function debugPrint($msg)
    {
        if ($this->debug) {
            echo $msg;
        }
    }
}
?>
