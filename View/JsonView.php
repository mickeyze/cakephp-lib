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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\View;
use Cake\Controller\Controller;
use Cake\Network\Response;

/**
 * A view class that is used for JSON responses.
 *
 * By setting the '_serialize' key in your controller, you can specify a view variable
 * that should be serialized to JSON and used as the response for the request.
 * This allows you to omit views + layouts, if your just need to emit a single view
 * variable as the JSON response.
 *
 * In your controller, you could do the following:
 *
 * `$this->set(array('posts' => $posts, '_serialize' => 'posts'));`
 *
 * When the view is rendered, the `$posts` view variable will be serialized 
 * into JSON.
 *
 * You can also define `'_serialize'` as an array.  This will create a top level object containing
 * all the named view variables:
 *
 * {{{
 * $this->set(compact('posts', 'users', 'stuff'));
 * $this->set('_serialize', array('posts', 'users'));
 * }}}
 * 
 * The above would generate a JSON object that looks like:
 *
 * `{"posts": [...], "users": [...]}`
 *
 * If you don't use the `_serialize` key, you will need a view.  You can use extended
 * views to provide layout like functionality.
 *
 * @package       Cake.View
 * @since         CakePHP(tm) v 2.1.0
 */
class JsonView extends View {

/**
 * JSON views are always located in the 'json' sub directory for a 
 * controllers views.
 * 
 * @var string
 */
	public $subDir = 'json';

/**
 * Constructor
 *
 * @param Controller $controller
 */
	public function __construct(Controller $controller = null) {
		parent::__construct($controller);
		if (isset($controller->response) && $controller->response instanceof Response) {
			$controller->response->type('json');
		}
	}

/**
 * Render a JSON view.
 *
 * Uses the special '_serialize' parameter to convert a set of
 * view variables into a JSON response.  Makes generating simple 
 * JSON responses very easy.  You can omit the '_serialize' parameter, 
 * and use a normal view + layout as well.
 *
 * @param string $view The view being rendered.
 * @param string $layout The layout being rendered.
 * @return string The rendered view.
 */
	public function render($view = null, $layout = null) {
		if (isset($this->viewVars['_serialize'])) {
			$serialize = $this->viewVars['_serialize'];
			if (is_array($serialize)) {
				$data = array();
				foreach ($serialize as $key) {
					$data[$key] = $this->viewVars[$key];
				}
			} else {
				$data = isset($this->viewVars[$serialize]) ? $this->viewVars[$serialize] : null;
			}
			$content = json_encode($data);
			$this->Blocks->set('content', $content);
			return $content;
		}
		if ($view !== false && $viewFileName = $this->_getViewFileName($view)) {
			if (!$this->_helpersLoaded) {
				$this->loadHelpers();
			}
			$content = $this->_render($viewFileName);
			$this->Blocks->set('content', $content);
			return $content;
		}
	}

}
