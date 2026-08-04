<?php

use PHPUnit\Framework\TestCase;
define('PATH_TO_ROOT', __DIR__ . '/../../../../../');

require_once PATH_TO_ROOT . 'code/web/sys/ECommerce/HeyCentricUrlBuilder.php';

class HeyCentricUrlBuilderTests extends TestCase {
	public function testSingle(): void {
		$builder = new HeyCentricURLBuilder(
			'https://payments.example.com/?',
			'not_a_real_private_key',
			['client', 'entity', 'pmtTyp', 'am']
		);
		$builder->addParam('client', 'EX');
		$builder->addParam('area', 'EXAMPLE');
		$builder->addParam('till', 'EXAMPLE');
		$builder->addParam('entity', 'EX1');
		$builder->addParam('pmtTyp', 'EXP');
		$builder->addParam('val1', '42');
		$builder->addParam('val1Desc', 'Payment');
		$builder->addParam('val2', '');
		$builder->addParam('val2Desc', '');
		$builder->addParam('am', '42');
		$builder->addParam('cmt', '');
		$builder->addParam('extRef', '');
		$builder->addParam('email', '');
		$builder->addParam('rurl', 'https://return.example.com');
		$builder->addParam('sid', '42');
		$result = $builder->build();

		$expected = 'https://payments.example.com/?client=EX&area=EXAMPLE&till=EXAMPLE&entity=EX1&pmtTyp=EXP&val1=42&val1Desc=Payment&val2=&val2Desc=&am=42&cmt=&extRef=&email=&rurl=https%3A%2F%2Freturn.example.com&sid=42&hash=MjFhOTU1OTU0MGJiNDg4YjczZGRjZTdmNDA4YWIyMWQ%3D';

		$this->assertEquals($expected, $result);
	}

	public function testMulti(): void {
		$builder = new HeyCentricURLBuilder(
			'https://payments.example.com/?',
			'not_a_real_private_key',
			['client', 'entity', 'pmtTyp', 'am']
		);
		$builder->addParam('client', 'EX');
		$builder->addParam('area', 'EXAMPLE');
		$builder->addParam('till', 'EXAMPLE');
		$builder->addParam('entity', 'EX1');

		$builder->addParam('pmtTyp', 'EXP', 0);
		$builder->addParam('val1', '42', 0);
		$builder->addParam('val1Desc', 'Payment', 0);
		$builder->addParam('val2', '', 0);
		$builder->addParam('val2Desc', '', 0);
		$builder->addParam('am', '42', 0);

		$builder->addParam('pmtTyp', 'EXP', 1);
		$builder->addParam('val1', '84', 1);
		$builder->addParam('val1Desc', 'Another payment', 1);
		$builder->addParam('val2', '', 1);
		$builder->addParam('val2Desc', '', 1);
		$builder->addParam('am', '84', 1);

		$builder->addParam('pmtTyp', 'EXP', 2);
		$builder->addParam('val1', '4200', 2);
		$builder->addParam('val1Desc', 'Yet another payment', 2);
		$builder->addParam('val2', '', 2);
		$builder->addParam('val2Desc', '', 2);
		$builder->addParam('am', '4200', 2);

		$builder->addParam('cmt', '');
		$builder->addParam('extRef', '');
		$builder->addParam('email', '');
		$builder->addParam('rurl', 'https://return.example.com');
		$builder->addParam('sid', '42');
		$result = $builder->build();

		$expected = 'https://payments.example.com/?client=EX&area=EXAMPLE&till=EXAMPLE&entity=EX1&pmtTyp=EXP&val1=42&val1Desc=Payment&val2=&val2Desc=&am=42&pmtTyp_1=EXP&val1_1=84&val1Desc_1=Another+payment&val2_1=&val2Desc_1=&am_1=84&pmtTyp_2=EXP&val1_2=4200&val1Desc_2=Yet+another+payment&val2_2=&val2Desc_2=&am_2=4200&cmt=&extRef=&email=&rurl=https%3A%2F%2Freturn.example.com&sid=42&hash=MWExZTc0YjhhMDA5ZTA0ZDI5NWYyMTFlZDlkMDc3YmI%3D';

		$this->assertEquals($expected, $result);
	}
}
