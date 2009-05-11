<?php
/* SVN FILE: $Id$ */
/**
 * TestTaskTest file
 *
 * Test Case for test generation shell task
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.0.7726
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', 'Shell');

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

if (!class_exists('FixtureTask')) {
	require CAKE . 'console' .  DS . 'libs' . DS . 'bake.php';
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'fixture.php';
}

Mock::generatePartial(
	'ShellDispatcher', 'TestFixtureTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);

Mock::generatePartial(
	'FixtureTask', 'MockFixtureTask',
	array('in', 'out', 'err', 'createFile', '_stop')
);

Mock::generatePartial(
	'Shell', 'MockFixtureModelTask',
	array('in', 'out', 'err', 'createFile', '_stop', 'getName', 'getTable', 'listAll')
);
/**
 * FixtureTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class FixtureTaskTest extends CakeTestCase {
/**
 * fixtures
 *
 * @var array
 **/
	var $fixtures = array('core.article', 'core.comment');
/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestFixtureTaskMockShellDispatcher();
		$this->Task =& new MockFixtureTask();
		$this->Task->Model =& new MockFixtureModelTask();
		$this->Task->Dispatch = new $this->Dispatcher;
		$this->Task->Dispatch->shellPaths = Configure::read('shellPaths');
	}
/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function endTest() {
		unset($this->Task, $this->Dispatcher);
		ClassRegistry::flush();
	}
/**
 * test that initialize sets the path
 *
 * @return void
 **/
	function testInitialize() {
		$this->Task->params['working'] = '/my/path';
		$this->Task->initialize();

		$expected = '/my/path/tests/fixtures/';
		$this->assertEqual($this->Task->path, $expected);
	}
/**
 * test import option array generation
 *
 * @return void
 **/
	function testImportOptions() {
		$this->Task->setReturnValueAt(0, 'in', 'y');
		$this->Task->setReturnValueAt(1, 'in', 'y');

		$result = $this->Task->importOptions('Article');
		$expected = array('schema' => 'Article', 'records' => true);
		$this->assertEqual($result, $expected);

		$this->Task->setReturnValueAt(2, 'in', 'n');
		$this->Task->setReturnValueAt(3, 'in', 'n');

		$result = $this->Task->importOptions('Article');
		$expected = array();
		$this->assertEqual($result, $expected);
	}
/**
 * test that execute passes runs bake depending with named model.
 *
 * @return void
 **/
	function testExecuteWithNamedModel() {
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('article');
		$filename = '/my/path/article_fixture.php';
		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation('/class ArticleFixture/')));
		$this->Task->execute();
	}

/**
 * test that execute runs all() when args[0] = all
 *
 * @return void
 **/
	function testExecuteIntoAll() {
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');
		$this->Task->Model->setReturnValue('listAll', array('articles', 'comments'));

		$filename = '/my/path/article_fixture.php';
		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation('/class ArticleFixture/')));
		$this->Task->execute();

		$filename = '/my/path/comment_fixture.php';
		$this->Task->expectAt(1, 'createFile', array($filename, new PatternExpectation('/class CommentFixture/')));
		$this->Task->execute();
	}

/**
 * test interactive mode of execute
 *
 * @return void
 **/
	function testExecuteInteractive() {
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		
		$this->Task->setReturnValue('in', 'y');
		$this->Task->Model->setReturnValue('getName', 'Article');
		$this->Task->Model->setReturnValue('getTable', 'articles', array('Article'));

		$filename = '/my/path/article_fixture.php';
		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation('/class ArticleFixture/')));
		$this->Task->execute();
	}
/**
 * Test that bake works
 *
 * @return void
 **/
	function testBake() {
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';

		$result = $this->Task->bake('Article');
		$this->assertPattern('/class ArticleFixture extends CakeTestFixture/', $result);
		$this->assertPattern('/var \$fields/', $result);
		$this->assertPattern('/var \$records/', $result);
		$this->assertNoPattern('/var \$import/', $result);

		$result = $this->Task->bake('Article', 'comments');
		$this->assertPattern('/class ArticleFixture extends CakeTestFixture/', $result);
		$this->assertPattern('/var \$name \= \'Article\';/', $result);
		$this->assertPattern('/var \$table \= \'comments\';/', $result);

		$result = $this->Task->bake('Article', 'comments', array('records' => true));
		$this->assertPattern("/var \\\$import \= array\('records' \=\> true\);/", $result);
		$this->assertNoPattern('/var \$records/', $result);

		$result = $this->Task->bake('Article', 'comments', array('schema' => 'Article'));
		$this->assertPattern("/var \\\$import \= array\('model' \=\> 'Article'\);/", $result);
		$this->assertNoPattern('/var \$fields/', $result);

		$result = $this->Task->bake('Article', 'comments', array('schema' => 'Article', 'records' => true));
		$this->assertPattern("/var \\\$import \= array\('model' \=\> 'Article'\, 'records' \=\> true\);/", $result);
		$this->assertNoPattern('/var \$fields/', $result);
		$this->assertNoPattern('/var \$records/', $result);
	}
/**
 * Test that file generation includes headers and correct path for plugins.
 *
 * @return void
 **/
	function testGenerateFixtureFile() {
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$filename = '/my/path/article_fixture.php';

		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation('/Article/')));
		$result = $this->Task->generateFixtureFile('Article', array());

		$this->Task->expectAt(1, 'createFile', array($filename, new PatternExpectation('/\<\?php(.*)\?\>/ms')));
		$result = $this->Task->generateFixtureFile('Article', array());
	}
}
?>