<?php

namespace ||PLUGIN_PRIMARY_NAMESPACE||\||PLUGIN_SECONDARY_NAMESPACE||\Admin;

use WPOP\V_2_8 as Opts;

/**
 * Class Options_Panel
 */
class Options_Panel {
	/**
	 * @var
	 */
	public $installed_dir;
	/**
	 * @var
	 */
	public $installed_url;

	protected $site_options;

	/**
	 * Options_Panel constructor.
	 *
	 * @param $installed_dir
	 * @param $installed_url
	 */
	function __construct( $installed_dir, $installed_url ) {
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;
		$this->setup_site_options();
	}
	function setup_site_options() {
		$page = new Opts\page(
			array(
				'parent_id'  => 'options-general.php',
				'id'         => '||PLUGIN_SLUG||-opts',
				'page_title' => '||PLUGIN_NAME|| Settings' .
				                ' <small style="font-size:0.66rem;"><code>||PLUGIN_SLUG||</code></small>',
				'menu_title' => '||PLUGIN_NAME||',
				'dashicon'   => 'dashicons-admin-settings',
			)
		);
		$this->site_options = ( $page );
		// setup sections
		$this->site_options->add_part(
			$general_section = new Opts\section(
				'general', array(
					'title'    => 'General',
					'dashicon' => 'dashicons-admin-generic',
				)
			)
		);

		/**
		 * General Configuration Fields
		 */
		$slug = strtolower('||PLUGIN_SLUG||_');
		$general_section->add_part(
			$text_field = new Opts\text(
				$slug . 'text', array(
					'label' => 'Text',
				)
			)
		);

		$general_section->add_part(
			$number = new Opts\number(
				$slug . 'number', array(
					'label' => 'Color',
				)
			)
		);

		$general_section->add_part(
			$color_field = new Opts\color_picker(
				$slug . 'color', array(
					'label' => 'Color',
				)
			)
		);

		$general_section->add_part(
			$toggle = new Opts\toggle_switch(
				$slug . 'toggle', array(
					'label' => 'Toggle',
					'value' => 1,
				)
			)
		);

		$general_section->add_part(
			$select_field = new Opts\select(
				$slug . 'select', array(
					'label' => 'Select',
					'values' => array(
						'uno' => 'Uno',
						'dos' => 'Dos',
						'tres' => 'Tres',
					),
				)
			)
		);



		$this->site_options->initialize_panel();
	}
}