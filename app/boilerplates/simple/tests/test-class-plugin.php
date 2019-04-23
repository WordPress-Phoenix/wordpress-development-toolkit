<?php
/**
 * PHPUnit Test
 *
 * @package <%= PKG %>
 */

/**
 * Plugin Test
 */
final class Plugin_Test extends WP_UnitTestCase {

	/**
	 * Test onload
	 *
	 * @covers OneCMS\Taxonomy_Paths\Plugin::activate
	 */
	public function test_activate() {
		$plugin   = new OneCMS\Taxonomy_Paths\Plugin();

		$this->assertNull(
			$plugin->activate()
		);
	}

	/**
	 * Test onload
	 *
	 * @covers OneCMS\Taxonomy_Paths\Plugin::onload
	 */
	public function test_onload() {
		$instance = new stdClass();
		$plugin   = new OneCMS\Taxonomy_Paths\Plugin();

		$this->assertNull(
			$plugin->onload( $instance )
		);
	}

	/**
	 * Test init
	 *
	 * @covers OneCMS\Taxonomy_Paths\Plugin::init
	 */
	public function test_init() {
		global $wp_actions;

		$plugin = new OneCMS\Taxonomy_Paths\Plugin();
		$init   = $plugin->init();

		$this->assertTrue( isset( $wp_actions['onecms_taxonomy_paths_before_init'] ) );
		$this->assertTrue( isset( $wp_actions['onecms_taxonomy_paths_after_init'] ) );
		$this->assertNotFalse(
			has_filter(
				'onecms_enable_taxonomy_paths',
				false
			)
		);
		$this->assertNull( $init );
	}

	/**
	 * Test authenticated_init when user is not logged in
	 *
	 * @covers OneCMS\Taxonomy_Paths\Plugin::authenticated_init
	 */
	public function test_authenticated_init_when_user_is_not_logged_in() {
		$plugin             = new OneCMS\Taxonomy_Paths\Plugin();
		$authenticated_init = $plugin->authenticated_init();

		$this->assertFalse( isset( $plugin->admin ) );
		$this->assertNull( $authenticated_init );
	}

	/**
	 * Test authenticated_init when user is logged in
	 *
	 * @covers OneCMS\Taxonomy_Paths\Plugin::authenticated_init
	 */
	public function test_authenticated_init_when_user_is_logged_in() {
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		$plugin             = new OneCMS\Taxonomy_Paths\Plugin();
		$authenticated_init = $plugin->authenticated_init();

		$this->assertTrue( isset( $plugin->admin ) );
		$this->assertNull( $authenticated_init );
	}

}
