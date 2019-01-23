<?php
/**
 * Integration testing of Record module.
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

require_once dirname(__FILE__) . '/../lib/SeleniumTestCase.php';
//error_reporting(E_ALL);

/**
 * Integration testing of Record module.
 *
 * @category VuFind
 * @package  Tests
 * @author   Preetha Rao <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */
class Record_Functions extends SeleniumTestCase
{
    private $_lightboxVisibleJS;

    /**
     * Standard setup method.
     *
     * @return void
     * @access public
     */
    public function setUp()
    {
        parent::setUp();

        // Set up Javascript to be used throughout the test:
        $this->_lightboxVisibleJS = "{selenium.browserbot.getCurrentWindow()" .
            ".document.getElementById('popupDetails').style.display == \"block\";}";
    }

    /**
     * Test various features on the record view page.
     *
     * @return void
     * @access public
     */
    public function testRecord()
    {
        $this->open($this->baseUrl);
        $this->assertContains("Search Home", $this->getTitle());
        $this->click("link=A - General Works");
        $this->waitForPageToLoad($this->timeout);
        $this->click(
            "link=Journal of rational emotive therapy : " .
            "the journal of the Institute for Rational-Emotive Therapy."
        );
        $this->assertElementPresent("link=Advanced");

        $this->waitForPageToLoad($this->timeout);

        $title = $this->def_rec_tab . ": Journal of rational emotive therapy";
        $this->assertTitle($title);

        $this->debugPrint(
            "\nAsserting Basic Search Select Option Values match the searches.ini\n"
        );
        foreach ($this->searchArray['Basic_Searches'] as $value) {
            $this->assertContains(
                $value,
                $this->getText(
                    "css=div.searchheader div.searchcontent div.searchform " .
                    "form#searchForm.search select#type"
                )
            );
        }

        $this->debugPrint(
            "\nAsserting Language Select Option Values match the config.ini\n"
        );
        $lang_count = $this->getEval(
            "{selenium.browserbot.getCurrentWindow().document" .
            ".getElementById('mylang').length;}"
        );
        // Proceed only if count matches:
        $this->assertEquals($lang_count, count($this->languages));
        for ($i = 0; $i < $lang_count; $i++) {
            $selObj = $this->getEval(
                "{selenium.browserbot.getCurrentWindow().document" .
                ".getElementById('mylang').options[$i].value}"
            );
            $this->assertTrue(array_key_exists($selObj, $this->languages));
        }

        /* Test the Cite This functionality */
        $this->debugPrint("Testing Cite This Functionality");

        $this->assertElementPresent("link=Cite this"); //  Cite this link present
        $this->click("link=Cite this");
        $this->waitForElementPresent("id=popupboxContent");

        $this->debugPrint("\nAsserting if Cite This Lightbox was opened");
        //Asserting if Lightbox was opened:
        $this->assertElementPresent("id=popupboxContent");
        $this->assertContains("Cite this", $this->getText("id=popupboxHeader"));

        $this->debugPrint("\nAsserting Lightbox Contents");

        $this->verifyTextPresent($this->apa_text);
        $this->verifyTextPresent(
            "Institute for Rational-Emotive Therapy (New York, N. (1983). Journal " .
            "of rational emotive therapy: The journal of the Institute for " .
            "Rational-Emotive Therapy. [New York]: The Institute. "
        );
        $this->verifyTextPresent($this->mla_text);
        $this->verifyTextPresent(
            "Institute for Rational-Emotive Therapy (New York, N.Y.). Journal of " .
            "Rational Emotive Therapy: The Journal of the Institute for " .
            "Rational-Emotive Therapy. [New York]: The Institute, 1983. "
        );
        $this->verifyTextPresent(
            "Warning: These citations may not always be 100% accurate."
        );

        $this->click("link=close"); // Close the light box

        $this->debugPrint("\nAsserting if Lightbox was closed\n");

        $this->assertNotVisible("id=popupbox"); // Assert that lightbox is invisible

        /* Test the Text This functionlaity */
        $this->debugPrint("\nTesting Text This Functionality");

        $this->click("link=Text this");
        $this->waitForElementPresent("id=popupboxContent");

        $this->debugPrint("\nAsserting if Text This Lightbox was opened");

        // Asserting if Lightbox was opened:
        $this->assertElementPresent("id=popupboxContent");
        $this->assertContains("Text this", $this->getText("id=popupboxHeader"));

        $this->debugPrint("\nSubmitting without any data");
        $this->click("name=submit");
        $this->debugPrint("\nAsserting Error message");

        $this->waitForCondition($this->_lightboxVisibleJS, $this->timeout);
        $this->verifyTextPresent($this->sms_failure);

        $this->click("link=close"); // Close the light box
        $this->debugPrint("\nAsserting if Lightbox was closed\n");
        $this->assertNotVisible("id=popupbox"); // Assert that lightbox is invisible

        /* Test the Email This functionlaity  */
        $this->debugPrint("\n\nTesting Email This Functionality");

        $this->click("link=Email this");
        $this->waitForElementPresent("id=popupboxContent");

        $this->debugPrint("\nAsserting if Email This Lightbox was opened");

        // Asserting if Lightbox was opened:
        $this->assertElementPresent("id=popupboxContent");
        $this->assertContains("Email this", $this->getText("id=popupboxHeader"));

        $this->debugPrint("\nSubmitting without any data");

        $this->click("name=submit");

        $this->debugPrint("\nAsserting Error message");

        $this->waitForCondition($this->_lightboxVisibleJS, $this->timeout);
        $this->verifyTextPresent(
            preg_replace(
                '/\s\s+/', ' ', $this->email_failure . ": " . $this->email_rcpt_err
            )
        );

        $this->debugPrint("\nSubmitting without sender data");

        $this->type("name=to", "abc@xyz.com");  // Valid email id format
        $this->type("name=from", "abc");        // Invalid email id format
        $this->click("name=submit");

        $this->debugPrint("\nAsserting Error message");

        $this->waitForCondition($this->_lightboxVisibleJS, $this->timeout);
        $this->verifyTextPresent(
            preg_replace(
                '/\s\s+/', ' ', $this->email_failure . ": " . $this->email_sndr_err
            )
        );

        $this->click("link=close");             // Close the light box

        $this->debugPrint("\nAsserting if Lightbox was closed\n");

        $this->assertNotVisible("id=popupbox"); // Assert that lightbox is invisible

        /* Test the Add Fav functionlaity  */
        $this->debugPrint("\n\nTesting Add Fav Functionality");


        // Check if user logged in
        $this->assertElementPresent("css=div#loginOptions"); // no user logged in
        $this->click("link=Add to Favorites");
        $this->waitForElementPresent("id=popupboxContent");
        $this->assertTextPresent("You must be logged in first");
        $this->assertElementPresent("name=username");
        $this->assertElementPresent("name=password");
        $this->assertElementPresent("name=submit value=Login");

        $this->click("link=close"); // Close the light box

        $this->debugPrint("\nAsserting if Lightbox was closed\n");

        $this->assertNotVisible("id=popupbox"); // Assert that lightbox is invisible

        $this->assertElementPresent("xpath=//div[@id='yui-main']/div/div[2]/table");

        // Assert the number of rows:
        $table_row_cnt = $this->getXpathCount(
            "//div[@id='yui-main']/div/div[2]/table/tbody/tr"
        );
        $this->assertEquals($table_row_cnt, 8);

        $record_array = array(
            "New Title" =>
                "Journal of rational-emotive and cognitive-behavior therapy",
            "Previous Title" => "Rational living",
            "Other Authors" =>
                "Institute for Rational-Emotive Therapy (New York, N.Y.)",
            "Format"  => "Journal",
            "Language" => "English",
            "Published" => "[New York] : The Institute, 1983",
            "Subjects"  => "Rational-emotive psychotherapy",
            "Tags"    => "No Tags, Be the first to tag this record!"
        );

        // verify the record citation table values
        $this->validateTable("citation", "record", $record_array);

        // verify Add tag button
        $this->click("link=Add");
        $this->waitForElementPresent("id=popupboxContent");
        $this->assertTextPresent("You must be logged in first");
        $this->assertElementPresent("name=username");
        $this->assertElementPresent("name=password");
        $this->assertElementPresent("name=submit value=Login");
        $this->click("link=close"); // Close the light box
        $this->debugPrint("\nAsserting if Lightbox was closed\n");
        $this->assertNotVisible("id=popupbox"); // Assert that lightbox is invisible

        $this->debugPrint("\nAsserting that the active tab is the default one\n");
        $this->assertEquals(
            $this->def_rec_tab, $this->getText("css=div#tabnav ul li.active")
        );

        $this->debugPrint("\nAsserting that the tabs are in order\n");
        $this->assertEquals(
            "Holdings", $this->getText("css=div#tabnav li:nth-child(1)")
        );
        $this->assertEquals(
            "Description", $this->getText("css=div#tabnav li:nth-child(2)")
        );
        $this->assertEquals(
            "Comments", $this->getText("css=div#tabnav li:nth-child(3)")
        );
        $this->assertEquals(
            "Staff View", $this->getText("css=div#tabnav li:nth-child(4)")
        );


        $this->debugPrint("\nAsserting the individual tabs contents\n");

        $this->click("link=Holdings"); // Holdings Tab
        $this->waitForPageToLoad($this->timeout);
        $this->assertEquals(
            "Holdings", $this->getText("css=div#tabnav ul li.active")
        );
        $this->assertTitle("Holdings: Journal of rational emotive therapy");
        $this->assertEquals(
            "3rd Floor Main Library", $this->getText("css=div.recordsubcontent h3")
        );
        $this->assertEquals(
            "Call Number:",
            $this->getText("css=div.recordsubcontent table.citation tbody tr th")
        );
        $this->assertEquals(
            "A1234.567",
            $this->getText("css=div.recordsubcontent table.citation tbody tr td")
        );

        $this->click("link=Description"); // Description Tab
        $this->waitForPageToLoad($this->timeout);
        $this->assertEquals(
            "Description", $this->getText("css=div#tabnav ul li.active")
        );
        $this->assertTitle("Description: Journal of rational emotive therapy");

        $desc_array = array(
            "Published" => "Vol. 1, no. 1 (fall 1983)-v. 5, no. 4 (winter 1987)",
            "Item Description" =>
                "Vols. for <spring 1985-> published by Human Sciences Press, Inc",
            "Physical Description" => "5 v. : ill. ; 26 cm",
            "Publication Frequency" => "Two no. a year",
            "ISSN" => "0748-1985"
        );
        $this->validateTable("citation", "recordsubcontent", $desc_array);

        $this->debugPrint("\nAsserting Similar Items tab\n");
        $this->assertContains(
            "Psychotherapy in private practice. Published: (1983)",
            $this->getText(
                "css=div#bd div.yui-b div.sidegroup ul.similar li:nth-child(1)"
            )
        );
        $this->assertContains(
            "Rational living",
            $this->getText(
                "css=div#bd div.yui-b div.sidegroup ul.similar li:nth-child(2)"
            )
        );
        $this->assertContains(
            "The Journal of psychology",
            $this->getText(
                "css=div#bd div.yui-b div.sidegroup ul.similar li:nth-child(3)"
            )
        );
        $this->assertContains(
            "The journal of sex research Published: (1965)",
            $this->getText(
                "css=div#bd div.yui-b div.sidegroup ul.similar li:nth-child(4)"
            )
        );
        $this->assertContains(
            "Journal of mathematics and mechanics. Published: (1957)",
            $this->getText(
                "css=div#bd div.yui-b div.sidegroup ul.similar li:nth-child(5)"
            )
        );

        $this->debugPrint("\nAsserting Other Editions tab\n");
        $this->assertContains(
            "Rational living.",
            $this->getText(
                "css=div#bd div.yui-b div.sidegroup ul.similar li span.microfilm "
            )
        );

        $this->debugPrint("\nAsserting the footer links\n");
        $this->assertElementPresent("link=Search History");
        $this->assertElementPresent("link=Advanced Search");
        $this->assertElementPresent("link=Browse the Catalog");
        $this->assertElementPresent("link=Browse Alphabetically");
        $this->assertElementPresent("link=Course Reserves");
        $this->assertElementPresent("link=New Items");
        $this->assertElementPresent("link=Search Tips");
        $this->assertElementPresent("link=Ask a Librarian");
        $this->assertElementPresent("link=FAQs");
    }
}
?>

