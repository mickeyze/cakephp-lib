<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.7953
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       cake.tests.fixtures
 */
class FruitFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Fruit'
 * @access public
 */
	public $name = 'Fruit';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => 255),
		'color' => array('type' => 'string', 'length' => 13),
		'shape' => array('type' => 'string', 'length' =>  255),
		'taste' => array('type' => 'string', 'length' => 255)
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	public $records = array(
		array(
			'id' => '481fc6d0-b920-43e0-a40d-6d1740cf8569', 'name' => 'Orange',
			'color' => 'orange', 'shape' => 'Spherical', 'taste' => 'Tangy & Sweet'
		)
	);
}
