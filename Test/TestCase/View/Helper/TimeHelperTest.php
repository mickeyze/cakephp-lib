<?php
/**
 * TimeHelperTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\View\Helper;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\TimeHelper;
use Cake\View\View;

/**
 * TimeHelperTestObject class
 */
class TimeHelperTestObject extends TimeHelper {

	public function attach(TimeMock $cakeTime) {
		$this->_engine = $cakeTime;
	}

	public function engine() {
		return $this->_engine;
	}

}

/**
 * TimeMock class
 */
class TimeMock {
}

/**
 * TimeHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class TimeHelperTest extends TestCase {

	public $Time = null;

	public $CakeTime = null;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->View = new View(null);

		$this->_appNamespace = Configure::read('App.namespace');
		Configure::write('App.namespace', 'TestApp');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->View);
		Configure::write('App.namespace', $this->_appNamespace);
		parent::tearDown();
	}

/**
 * test CakeTime class methods are called correctly
 */
	public function testTimeHelperProxyMethodCalls() {
		$methods = array(
			'convertSpecifiers', 'convert', 'serverOffset', 'fromString',
			'nice', 'niceShort', 'daysAsSql', 'dayAsSql',
			'isToday', 'isThisMonth', 'isThisYear', 'wasYesterday',
			'isTomorrow', 'toQuarter', 'toUnix', 'toAtom', 'toRSS',
			'timeAgoInWords', 'wasWithinLast', 'gmt', 'format', 'i18nFormat',
		);
		$CakeTime = $this->getMock(__NAMESPACE__ . '\TimeMock', $methods);
		$Time = new TimeHelperTestObject($this->View, array('engine' => __NAMESPACE__ . '\TimeMock'));
		$Time->attach($CakeTime);
		foreach ($methods as $method) {
			$CakeTime->expects($this->at(0))->method($method);
			$Time->{$method}('who', 'what', 'when', 'where', 'how');
		}
	}

/**
 * test engine override
 */
	public function testEngineOverride() {
		App::build(array(
			'Utility' => array(CAKE . 'Test/TestApp/Utility/')
		), App::REGISTER);
		$Time = new TimeHelperTestObject($this->View, array('engine' => 'TestAppEngine'));
		$this->assertInstanceOf('TestApp\Utility\TestAppEngine', $Time->engine());

		App::build(array(
			'Plugin' => array(CAKE . 'Test/TestApp/Plugin/')
		));
		Plugin::load('TestPlugin');
		$Time = new TimeHelperTestObject($this->View, array('engine' => 'TestPlugin.TestPluginEngine'));
		$this->assertInstanceOf('TestPlugin\Utility\TestPluginEngine', $Time->engine());
		Plugin::unload('TestPlugin');
	}

/**
 * Test element wrapping in timeAgoInWords
 *
 * @return void
 */
	public function testTimeAgoInWords() {
		$Time = new TimeHelper($this->View);
		$timestamp = strtotime('+8 years, +4 months +2 weeks +3 days');
		$result = $Time->timeAgoInWords($timestamp, array(
			'end' => '1 years',
			'element' => 'span'
		));
		$expected = array(
			'span' => array(
				'title' => $timestamp,
				'class' => 'time-ago-in-words'
			),
			'on ' . date('j/n/y', $timestamp),
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $Time->timeAgoInWords($timestamp, array(
			'end' => '1 years',
			'element' => array(
				'title' => 'testing',
				'rel' => 'test'
			)
		));
		$expected = array(
			'span' => array(
				'title' => 'testing',
				'class' => 'time-ago-in-words',
				'rel' => 'test'
			),
			'on ' . date('j/n/y', $timestamp),
			'/span'
		);
		$this->assertTags($result, $expected);

		$timestamp = strtotime('+2 weeks');
		$result = $Time->timeAgoInWords(
			$timestamp,
			array('end' => '1 years', 'element' => 'div')
		);
		$expected = array(
			'div' => array(
				'title' => $timestamp,
				'class' => 'time-ago-in-words'
			),
			'2 weeks',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

}
