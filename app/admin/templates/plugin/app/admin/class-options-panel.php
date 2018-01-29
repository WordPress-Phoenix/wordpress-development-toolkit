<?php

namespace <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Admin;

use WPOP\V_3_1 as Opts;

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
	protected $options;

	/**
	 * Options_Panel constructor.
	 *
	 * @param string $installed_dir
	 * @param string $installed_url
	 */
	function __construct( $installed_dir, $installed_url ) {
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;

		$this->setup_options();
	}

	/**
	 * Register Options Panel
	 */
	function setup_options() {

		$sections = array(
			'simple'    => $this->general_section(),
			'advanced'  => $this->advanced_section(),
			'media'     => $this->media_section(),
			'editors'   => $this->editors_section(),
			'wordpress' => $this->wordpress_section(),
			'encrypted' => $this->encrypted_section(),
			'includes'  => $this->include_section(),
		);

		$this->options = new Opts\page(
			$this->options_config(),
			$sections
		);

		// initialize_panel() is a function in the opt panel Container class
		$this->options->initialize_panel();
	}

	function options_config() {
		return array(
			'parent_page_id' => 'options-general.php',
			'id'             => '<%= SLUG %>-example',
			'page_title'     => '<%= NAME %> Settings',
			'menu_title'     => '<%= NAME %> Panel',
			'dashicon'       => 'dashicons-admin-settings',
			'api'            => 'site'
		);
	}

	function general_section() {
		return array(
			'label'    => 'General',
			'dashicon' => 'dashicons-admin-generic',
			'parts'    => array(
				'<%= SLUG %>_plugin_text'     => array(
					'label' => 'Text Field',
					'part'  => 'text',
				),
				'<%= SLUG %>_plugin_textarea' => array(
					'label' => 'Textarea Field',
					'part'  => 'textarea',
				),
				'<%= SLUG %>_plugin_number'   => array(
					'label' => 'Number Field',
					'part'  => 'number',
				),
				'<%= SLUG %>_plugin_url'      => array(
					'label' => 'URL Field',
					'part'  => 'url',
				),
				'<%= SLUG %>_plugin_email'    => array(
					'label' => 'Email Field',
					'part'  => 'email',
				),
			),
		);
	}

	function advanced_section() {
		return array(
			'label'    => 'Advanced',
			'dashicon' => 'dashicons-forms',
			'parts'    => array(
				'<%= SLUG %>_plugin_select'        => array(
					'label'  => 'Select Field',
					'part'   => 'select',
					'values' => array(
						'uno'  => 'First',
						'dos'  => 'Second',
						'tres' => 'Third',
					),
				),
				'<%= SLUG %>_plugin_multiselect'   => array(
					'label'  => 'Multiselect Field',
					'part'   => 'multiselect',
					'values' => array(
						'party'   => 'Party',
						'fiesta'  => 'Fiesta',
						'cookout' => 'Cookout',
					),
				),
				'<%= SLUG %>_plugin_toggle_switch' => array(
					'label' => 'Toggle Switch Field',
					'part'  => 'toggle_switch',
				),
				'<%= SLUG %>_plugin_radios'        => array(
					'label'  => 'Radio Field',
					'part'   => 'radio_buttons',
					'values' => array(
						'party'   => 'Party',
						'fiesta'  => 'Fiesta',
						'cookout' => 'Cookout',
					),
				),
			),
		);
	}

	function media_section() {
		return array(
			'label'    => 'Media',
			'dashicon' => 'dashicons-admin-media',
			'parts'    => array(
				'<%= SLUG %>_plugin_media' => array(
					'label' => 'Media Field',
					'part'  => 'media'
				)
			),
		);
	}

	function editors_section() {
		return array(
			'label'    => 'Editors',
			'dashicon' => 'dashicons-edit',
			'parts'    => array(
				'<%= SLUG %>_plugin_editor' => array(
					'label' => 'Editor Field',
					'part'  => 'editor',
				),
				'<%= SLUG %>_plugin_nohtml' => array(
					'label'        => 'Editor - No HTML Toggle',
					'part'         => 'Editor',
					'no_quicktags' => 'true',
				),
				'<%= SLUG %>_plugin_simple' => array(
					'label'        => 'Editor Simple',
					'part'         => 'editor',
					'teeny'        => 'true',
					'no_media'     => 'true',
					'no_quicktags' => 'true'
				),
			)
		);
	}

	function wordpress_section() {
		return array(
			'label'    => 'WordPress',
			'dashicon' => 'dashicons-wordpress-alt',
			'parts'    => array(
				'<%= SLUG %>_plugin_color' => array(
					'label' => 'Color Field',
					'part'  => 'color',
				)
			),
		);
	}

	function encrypted_section() {
		return array(
			'label'    => 'Encrypted',
			'dashicon' => 'dashicons-lock',
			'parts'    => array(
				'<%= SLUG %>_plugin_password' => array(
					'label' => 'Password Field',
					'part'  => 'password',
				),
			),
		);
	}

	function include_section() {
		return array(
			'label'     => 'Includes',
			'dashicons' => 'dashicons-file',
			'parts'     => array(
				'<%= SLUG %>_plugin_markdown_file' => array(
					'label'    => 'Markdown Field',
					'part'     => 'markdown',
					'filename' => $this->installed_dir . 'assets/example_include_markdown.md'
				),
			),
		);
	}

}
