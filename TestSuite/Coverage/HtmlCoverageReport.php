<?php
/**
 * Generates code coverage reports in HTML from data obtained from PHPUnit
 *
 * PHP5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'DEFAULT');

App::uses('BaseCoverageReport', 'TestSuite/Coverage');

class HtmlCoverageReport extends BaseCoverageReport {

/**
 * Generates report html to display.
 *
 * @return string compiled html report.
 */
	public function report() {
		$pathFilter = $this->getPathFilter();
		$coverageData = $this->filterCoverageDataByPath($pathFilter);
		if (empty($coverageData)) {
			return '<h3>No files to generate coverage for</h3>';
		}
		$output = $this->coverageScript();
		$output .= <<<HTML
		<h3>Code coverage results
		<a href="#" onclick="coverage_toggle_all()" class="coverage-toggle">Toggle all files</a>
		</h3>
HTML;
		foreach ($coverageData as $file => $coverageData) {
			$fileData = file($file);
			$output .= $this->generateDiff($file, $fileData, $coverageData);
		}
		return $output;
	}

/**
 * Generates an HTML diff for $file based on $coverageData.
 *
 * @param string $filename Name of the file having coverage generated
 * @param array $fileLines File data as an array. See file() for how to get one of these.
 * @param array $coverageData Array of coverage data to use to generate HTML diffs with
 * @return string HTML diff.
 */
	public function generateDiff($filename, $fileLines, $coverageData) {
		$output = ''; 
		$diff = array();

		list($covered, $total) = $this->_calculateCoveredLines($fileLines, $coverageData);

		//shift line numbers forward one;
		array_unshift($fileLines, ' ');
		unset($fileLines[0]);

		foreach ($fileLines as $lineno => $line) {
			$class = 'ignored';
			$coveringTests = array();
			if (isset($coverageData[$lineno]) && is_array($coverageData[$lineno])) {
				$coveringTests = array();
				foreach ($coverageData[$lineno] as $test) {
					$testReflection = new ReflectionClass(current(explode('::', $test['id'])));
					list($fileBasename,) = explode('.', basename($testReflection->getFileName()), 2);
					$this->_testNames[] = $fileBasename;
					$coveringTests[] = $test['id'];
				}
				$class = 'covered';
			} elseif (isset($coverageData[$lineno]) && $coverageData[$lineno] === -1) {
				$class = 'uncovered';
			} elseif (isset($coverageData[$lineno]) && $coverageData[$lineno] === -2) {
				$class .= ' dead';
			}
			$diff[] = $this->_paintLine($line, $lineno, $class, $coveringTests);
		}

		$percentCovered = round(100 * $covered / $total, 2);

		$output .= $this->coverageHeader($filename, $percentCovered);
		$output .= implode("", $diff);
		$output .= $this->coverageFooter();
		return $output;
	}

/**
 * Renders the html for a single line in the html diff.
 *
 * @return void
 */
	protected function _paintLine($line, $linenumber, $class, $coveringTests) {
		$coveredBy = '';
		if (!empty($coveringTests)) {
			$coveredBy = "Covered by:\n";
			foreach ($coveringTests as $test) {
				$coveredBy .= $test . "\n";
			}
		}

		return sprintf(
			'<div class="code-line %s" title="%s"><span class="line-num">%s</span><span class="content">%s</span></div>',
			$class,
			$coveredBy,
			$linenumber,
			htmlspecialchars($line)
		);
	}

/**
 * generate some javascript for the coverage report.
 *
 * @return void
 */
	public function coverageScript() {
		return <<<HTML
		<script type="text/javascript">
		function coverage_show_hide(selector) {
			var element = document.getElementById(selector);
			element.style.display = (element.style.display == 'none') ? '' : 'none';
		}
		function coverage_toggle_all () {
			var divs = document.querySelectorAll('div.coverage-container');
			var i = divs.length;
			while (i--) {
				if (divs[i] && divs[i].className.indexOf('primary') == -1) {
					divs[i].style.display = (divs[i].style.display == 'none') ? '' : 'none';
				}
			}
		}
		</script>
HTML;
	}

/**
 * Generate an HTML snippet for coverage headers
 *
 * @return void
 */
	public function coverageHeader($filename, $percent) {
		$filename = basename($filename);
		list($file, $ext) = explode('.', $filename);
		$display = in_array($file, $this->_testNames) ? 'block' : 'none';
		$primary = $display == 'block' ? 'primary' : '';
		return <<<HTML
	<div class="coverage-container $primary" style="display:$display;">
	<h4>
		<a href="#coverage-$filename" onclick="coverage_show_hide('coverage-$filename');">
			$filename Code coverage: $percent%
		</a>
	</h4>
	<div class="code-coverage-results" id="coverage-$filename" style="display:none;">
	<pre>
HTML;
	}

/**
 * Generate an HTML snippet for coverage footers
 *
 * @return void
 */
	public function coverageFooter() {
		return "</pre></div></div>";
	}
}