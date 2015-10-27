<?php
/**
 * ondrejd/php-utils
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @license Mozilla Public License 2.0 https://www.mozilla.org/MPL/2.0/
 * @link https://github.com/ondrejd/php-utils
 */

namespace ondrejd\Utils;

/**
 * Tests for {@see \ondrejd\Utils\CsvImport} class.
 * 
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 */
class CsvImportTest extends \PHPUnit_Framework_TestCase {

	public function testImport() {
		$csvfile1 = dirname(__DIR__).'/data/test01.csv';
		$import1 = CsvImport::import($csvfile1, CsvImport::SEP_COMMA, CsvImport::QUOTE_AUTO, false);

		$this->assertTrue($import1->success());

		$this->assertSame(5, $import1->getColumnsCount());
		$this->assertSame($csvfile1, $import1->getFilename());
		$this->assertSame(CsvImport::SEP_COMMA, $import1->getSeparator());
		$this->assertSame(CsvImport::QUOTE_AUTO, $import1->getQuotes());
		$this->assertFalse($import1->hasHeader());

		$this->assertSame(1466, $import1->count());
		$this->assertSame('Odborní pracovníci financování a úvěrování', $import1[0][0]);
		$this->assertSame('5', $import1[0][1]);

		$csvfile2 = dirname(__DIR__).'/data/test02.csv';
		$import2 = CsvImport::import($csvfile2, CsvImport::SEP_COMMA, CsvImport::QUOTE_AUTO, true);

		$this->assertTrue($import2->success());

		$this->assertSame(3, $import2->getColumnsCount());
		$this->assertSame($csvfile2, $import2->getFilename());
		$this->assertSame(CsvImport::SEP_COMMA, $import2->getSeparator());
		$this->assertSame(CsvImport::QUOTE_AUTO, $import2->getQuotes());
		$this->assertTrue($import2->hasHeader());

		$this->assertSame(29, $import2->count());
		$this->assertSame('služby, řemesla', $import2[0][0]);
		$this->assertSame('1', $import2[0][1]);
	}

    /**
     * @expectedException \Exception
     */
    public function testException()
    {
    	$import = CsvImport::import('some-bad-filename.csv');
    }
}