<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Network\Http;

use Cake\Network\Http\Client;
use Cake\Network\Http\Request;
use Cake\Network\Http\Response;
use Cake\TestSuite\TestCase;

/**
 * HTTP client test.
 */
class ClientTest extends TestCase {

/**
 * Test storing config options and modifying them.
 *
 * @return void
 */
	public function testConstructConfig() {
		$config = [
			'scheme' => 'http',
			'host' => 'example.org',
		];
		$http = new Client($config);
		$this->assertEquals($config, $http->config());

		$result = $http->config([
			'auth' => ['username' => 'mark', 'password' => 'secret']
		]);
		$this->assertSame($result, $http);

		$result = $http->config();
		$expected = [
			'scheme' => 'http',
			'host' => 'example.org',
			'auth' => ['username' => 'mark', 'password' => 'secret']
		];
		$this->assertEquals($expected, $result);
	}

/**
 * Data provider for buildUrl() tests
 *
 * @return array
 */
	public static function urlProvider() {
		return [
			[
				'http://example.com/test.html',
				'http://example.com/test.html',
				[],
				null,
				'Null options'
			],
			[
				'http://example.com/test.html',
				'http://example.com/test.html',
				[],
				[],
				'Simple string'
			],
			[
				'http://example.com/test.html',
				'/test.html',
				[],
				['host' => 'example.com'],
				'host name option',
			],
			[
				'https://example.com/test.html',
				'/test.html',
				[],
				['host' => 'example.com', 'scheme' => 'https'],
				'HTTPS',
			],
			[
				'http://example.com:8080/test.html',
				'/test.html',
				[],
				['host' => 'example.com', 'port' => '8080'],
				'Non standard port',
			],
			[
				'http://example.com/test.html',
				'/test.html',
				[],
				['host' => 'example.com', 'port' => '80'],
				'standard port, does not display'
			],
			[
				'https://example.com/test.html',
				'/test.html',
				[],
				['host' => 'example.com', 'scheme' => 'https', 'port' => '443'],
				'standard port, does not display'
			],
			[
				'http://example.com/test.html',
				'http://example.com/test.html',
				[],
				['host' => 'example.com', 'scheme' => 'https'],
				'options do not duplicate'
			],
			[
				'http://example.com/search?q=hi+there&cat%5Bid%5D%5B0%5D=2&cat%5Bid%5D%5B1%5D=3',
				'http://example.com/search',
				['q' => 'hi there', 'cat' => ['id' => [2, 3]]],
				[],
				'query string data.'
			],
			[
				'http://example.com/search?q=hi+there&id=12',
				'http://example.com/search?q=hi+there',
				['id' => '12'],
				[],
				'query string data with some already on the url.'
			],
		];
	}

	/**
	 * @dataProvider urlProvider
	 */
	public function testBuildUrl($expected, $url, $query, $opts) {
		$http = new Client();

		$result = $http->buildUrl($url, $query, $opts);
		$this->assertEquals($expected, $result);
	}

/**
 * test simple get request.
 *
 * @return void
 */
	public function testGetSimple() {
		$response = new Response();

		$mock = $this->getMock('Cake\Network\Http\Adapter\Stream', ['send']);
		$mock->expects($this->once())
			->method('send')
			->with($this->logicalAnd(
				$this->isInstanceOf('Cake\Network\Http\Request'),
				$this->attributeEqualTo('_url', 'http://cakephp.org/test.html')
			))
			->will($this->returnValue($response));

		$http = new Client([
			'host' => 'cakephp.org',
			'adapter' => $mock
		]);
		$result = $http->get('/test.html');
		$this->assertSame($result, $response);
	}

/**
 * test simple get request with headers & cookies.
 *
 * @return void
 */
	public function testGetSimpleWithHeadersAndCookies() {
		$response = new Response();

		$headers = [
			'User-Agent' => 'Cake',
			'Connection' => 'close',
			'Content-Type' => 'application/json',
		];
		$cookies = [
			'split' => 'value'
		];

		$mock = $this->getMock('Cake\Network\Http\Adapter\Stream', ['send']);
		$mock->expects($this->once())
			->method('send')
			->with($this->logicalAnd(
				$this->isInstanceOf('Cake\Network\Http\Request'),
				$this->attributeEqualTo('_url', 'http://cakephp.org/test.html'),
				$this->attributeEqualTo('_headers', $headers),
				$this->attributeEqualTo('_cookies', $cookies)
			))
			->will($this->returnValue($response));

		$http = new Client(['adapter' => $mock]);
		$result = $http->get('http://cakephp.org/test.html', [], [
			'headers' => $headers,
			'cookies' => $cookies,
		]);
		$this->assertSame($result, $response);
	}

/**
 * test get request with querystring data
 *
 * @return void
 */
	public function testGetQuerystring() {
		$response = new Response();

		$mock = $this->getMock('Cake\Network\Http\Adapter\Stream', ['send']);
		$mock->expects($this->once())
			->method('send')
			->with($this->logicalAnd(
				$this->isInstanceOf('Cake\Network\Http\Request'),
				$this->attributeEqualTo('_url', 'http://cakephp.org/search?q=hi+there&Category%5Bid%5D%5B0%5D=2&Category%5Bid%5D%5B1%5D=3')
			))
			->will($this->returnValue($response));

		$http = new Client([
			'host' => 'cakephp.org',
			'adapter' => $mock
		]);
		$result = $http->get('/search', [
			'q' => 'hi there',
			'Category' => ['id' => [2, 3]]
		]);
		$this->assertSame($result, $response);
	}
}
