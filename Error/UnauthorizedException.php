<?php
/**
 * UnauthorizedException class
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html
 * @package       Cake.Error
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Error;

/**
 * Represents an HTTP 401 error.
 *
 * @package       Cake.Error
 */
class UnauthorizedException extends HttpException {

/**
 * Constructor
 *
 * @param string $message If no message is given 'Unauthorized' will be the message
 * @param string $code Status code, defaults to 401
 */
	public function __construct($message = null, $code = 401) {
		if (empty($message)) {
			$message = 'Unauthorized';
		}
		parent::__construct($message, $code);
	}

}
