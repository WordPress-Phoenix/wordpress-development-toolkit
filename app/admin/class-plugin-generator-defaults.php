<?php

namespace WP_PHX_Dev_Kit\V_1_0\Admin;

use WPOP\V_2_9 as Opts;

/**
 * Class Plugin_Generator_Defaults
 */
class Plugin_Generator_Defaults {

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
	protected $generator_defaults;

	/**
	 * Plugin_Generator_Defaults constructor.
	 *
	 * @param string $installed_dir
	 * @param string $installed_url
	 */
	function __construct( $installed_dir, $installed_url ) {
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;

		$this->setup_defaults_panel();
	}

	/**
	 * Register Plugin_Generator_Defaults
	 */
	function setup_defaults_panel() {

		$panel_sections_and_fields = array(
			'general'         => $this->general_section(),
			'accounts-author' => $this->accounts_section(),
			'plugin-features' => $this->features_section(),
		);

		// create main panel object
		$page = new Opts\Page( $this->build_config(), $panel_sections_and_fields );

		// initialize_panel() is a function in the opt panel Container class
		$page->initialize_panel();
	}

	function general_section() {
		return array(
			'title'    => 'General',
			'dashicon' => 'dashicons-admin-generic',
			'parts'    => array(
				'wpop_pgen_plugin_arch_type'    => array(
					'label'  => 'Plugin Type',
					'field'  => 'select',
					'values' => array(
						'Simple'   => 'Simple',
						'Standard' => 'Standard',
					),
					'value'  => 1,
				),
				'wpop_pgen_primary_namespace'   => array(
					'label' => 'Primary Namespace',
					'field' => 'text',
				),
				'wpop_pgen_secondary_namespace' => array(
					'label' => 'Secondary Namespace',
					'field' => 'text',
				),
				'wpop_pgen_license_type'        => array(
					'label'  => 'License Type',
					'field'  => 'select',
					'values' => array(
						'private' => 'Private',
						'gpl'     => 'GPL 2+',
					),
					'value'  => 1,
				)
			),
		);
	}

	function accounts_section() {
		return array(
			'title'    => 'Accounts & Authorship',
			'dashicon' => 'dashicons-admin-generic',
			'parts'    => array(
				'wpop_pgen_author_custom' => array(
					'label' => 'Author(s) Text',
					'field'  => 'text',
				),
				'wpop_pgen_teamorg_name' => array(
					'label' => 'Team / Organization Name',
					'field'  => 'text',
				),
				'wpop_pgen_gh_username' => array(
					'label' => 'GitHub Username',
					'field'  => 'text',
				),
				'wpop_pgen_slack_id' => array(
					'label' => 'Slack Organization ID',
					'field'  => 'text',
				),
				'wpop_pgen_slack_channel_name' => array(
					'label' => 'Slack Main #channel (display name)',
					'field'  => 'text',
				),
				'wpop_pgen_slack_channel_id', array(
					'label' => 'Slack Main #channel corresponding ID',
					'field'  => 'text',
				)
			),
		);
	}

	function features_section() {
		return array(
			'title'    => 'Features',
			'dashicon' => 'dashicons-admin-generic',
			'fields'   => array(
				'wpop_pgen_include_options_panel' => array(
					'label' => 'Include Site Options Panel',
					'value' => 'on',
					'field' => 'toggle_switch'
				),
				'wpop_pgen_disable_assets' => array(
					'label' => 'Disable Default Asset Generation',
					'value' => 'disable',
					'field' => 'toggle_switch'
				)
			),
		);
	}

	function build_config() {
		return array(
			'parent_page_id' => 'wp-phx-dev-kit',
			'id'             => 'plugin-generator-defaults',
			'page_title'     => 'Plugin Generator Options',
			'menu_title'     => 'Plugin Options',
			'dashicon'       => 'dashicons-admin-settings',
		);
	}

}
