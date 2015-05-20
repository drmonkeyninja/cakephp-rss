<?php
/**
 * All RSS plugin tests
 *
 */
class AllRssTest extends CakeTestCase {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Datasources test');

		$path = CakePlugin::path('Rss') . 'Test' . DS . 'Case' . DS;
		$suite->addTestDirectoryRecursive($path);

		return $suite;
	}
}