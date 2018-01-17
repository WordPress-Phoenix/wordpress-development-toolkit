<?php

namespace <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Admin;

use WPOP\V_2_9 as Opts;

/**
 * Class Options_Panel
 */
class Options_Panel {

	/**
	 * @var string
	 */
	public $installed_dir;

	/**
	 * @var string
	 */
	public $installed_url;

	/**
	 * @var string
	 */
	protected $site_options;

	/**
	 * Options_Panel constructor.
	 *
	 * @param string $installed_dir
	 * @param string $installed_url
	 */
	function __construct( $installed_dir, $installed_url ) {
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;

		$this->setup_site_options();
	}

	/**
	 * Register Options Panel
	 */
	function setup_site_options() {
		$page = new Opts\page(
			array(
				'parent_id'  => 'options-general.php',
				'id'         => '<%= SLUG %>-opts',
				'page_title' => '<%= NAME %> Settings' .
				                ' <small style="font-size:0.66rem;"><code><%= SLUG %></code></small>',
				'menu_title' => '<%= NAME %>',
				'dashicon'   => 'dashicons-admin-settings',
			)
		);

		$this->site_options = ( $page );

		// setup sections
		$this->site_options->add_part(
			$general_section = new Opts\Section(
				'general', array(
					'title'    => 'General',
					'dashicon' => 'dashicons-admin-generic',
				)
			)
		);

		/**
		 * General Configuration Fields
		 */
		$slug = '<%= SLUG %>_';
		$general_section->add_part(
			$text_field = new Opts\Text(
				$slug . 'text', array(
					'label' => 'Text',
				)
			)
		);

		$general_section->add_part(
			$textarea = new Opts\Textarea(
				$slug . 'textarea', array(
					'label' => 'Textarea',
				)
			)
		);

		$general_section->add_part(
			$number = new Opts\Number(
				$slug . 'number', array(
					'label' => 'Number',
				)
			)
		);



		$general_section->add_part(
			$media = new Opts\Media(
				$slug . 'media', array(
					'label' => 'Media',
				)
			)
		);

		$general_section->add_part(
			$toggle = new Opts\Toggle_Switch(
				$slug . 'toggle', array(
					'label' => 'Toggle',
					'value' => 1,
				)
			)
		);

		$general_section->add_part(
			$select_field = new Opts\Select(
				$slug . 'select', array(
					'label'  => 'Select',
					'values' => array(
						'uno'  => 'Uno',
						'dos'  => 'Dos',
						'tres' => 'Tres',
					),
				)
			)
		);

		// initialize_panel() is a function in the opt panel Container class
		$this->site_options->initialize_panel();
	}

}
