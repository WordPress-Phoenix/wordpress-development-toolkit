<?php
/**
 * PHPUnit Test
 *
 * @package <%= PKG %>
 */

/**
 * App_Test
 */
final class App_Test extends WP_UnitTestCase {

	/**
	 * Test __construct
	 *
	 * @covers <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Admin\App::__construct
	 */
	public function test_construct() {
		$installed_dir = '/var/installed/dir';
		$installed_url = '/var/installed/url';
		$version       = 1.0;

		$app = new <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Admin\App( $installed_dir, $installed_url, $version );

		$this->assertEquals( $installed_dir, $app->installed_dir );
		$this->assertEquals( $installed_url, $app->installed_url );
		$this->assertEquals( $version, $app->version );
	}

}
