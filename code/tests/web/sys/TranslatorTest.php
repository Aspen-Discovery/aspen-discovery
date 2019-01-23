<?php
/**
 * Translator Test Class
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
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */
require_once dirname(__FILE__) . '/../prepend.inc.php';
require_once 'PEAR.php';
require_once 'sys/Translator.php';

/**
 * Tests for the I18N_Translator class
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */
class TranslatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test the translator class.
     *
     * @return void
     * @access public
     */
    public function testTranslator()
    {
        // Save valid path to language files:
        $path = dirname(__FILE__) . '/../../../web/lang';

        // Test bad path:
        $translator = new I18N_Translator('/bad/garbage/illegal/path/', 'en');
        $error1 = $translator->error;

        // Test bad language code:
        $translator = new I18N_Translator($path, 'NOTAVALIDCODE');
        $error2 = $translator->error;

        // Make sure both error situations generated a warning and that the two
        // warnings are different!
        $this->assertTrue($error1 !== false);
        $this->assertTrue($error2 !== false);
        $this->assertTrue($error1 !== $error2);

        // Try to parse all available language files:
        $dir = opendir($path);
        if ($dir) {
            while ($file = readdir($dir)) {
                if (is_file($path . '/' . $file) && substr($file, -4) == '.ini') {
                    $lang = substr($file, 0, strlen($file) - 4);
                    $translator = new I18N_Translator($path, $lang);

                    // Make sure there were no errors loading the file and that
                    // the file contains at least ten words (an arbitrary number,
                    // but presumably a safe minimum for a legitimate language file).
                    $this->assertTrue($translator->error === false);
                    $this->assertTrue(count($translator->words) > 10);
                }
            }
        }

        // Test an actual translation:
        $translator = new I18N_Translator($path, 'es');
        $this->assertEquals($translator->translate('Author'), 'Autor');

        // Test missing translations, with and without debug mode on:
        $this->assertEquals($translator->translate('asdfasdfasdf'), 'asdfasdfasdf');
        $translator = new I18N_Translator($path, 'es', true);
        $this->assertEquals(
            $translator->translate('asdfasdfasdf'),
            'translate_index_not_found(asdfasdfasdf)'
        );
    }
}
?>
