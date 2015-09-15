<?php
/**
 * Add admin interface
 *
 * @package   cherry_wizard
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( !class_exists( 'cherry_data_manager_interface' ) ) {

	/**
	 * Add admin interface
	 *
	 * @since 1.0.0
	 */
	class cherry_data_manager_interface {

		/**
		 * Array of Cherry Data Manager pages
		 * @var array
		 */
		public $pages = array();

		/**
		 * Trigger to define if pages already added
		 * @var boolean
		 */
		public $added = false;

		function __construct() {
			// Add the withard page and menu item.
			add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );

			global $cherry_data_manager;

			$this->pages = apply_filters(
				'cherry_data_manager_pages',
				array(
					$cherry_data_manager->import_page => array(
						'page_title' => __( 'Cherry Content Import', $cherry_data_manager->slug ),
						'menu_title' => __( 'Cherry Import', $cherry_data_manager->slug ),
						'capability' => 'manage_options',
						'menu_slug'  => $cherry_data_manager->import_page,
						'function'   => array( $this, 'show_admin_pages' ),
					),
					$cherry_data_manager->export_page => array(
						'page_title' => __( 'Cherry Content Export', $cherry_data_manager->slug ),
						'menu_title' => __( 'Cherry Export', $cherry_data_manager->slug ),
						'capability' => 'manage_options',
						'menu_slug'  => $cherry_data_manager->export_page,
						'function'   => array( $this, 'show_admin_pages' ),
					),
					$cherry_data_manager->data_page => array(
						'page_title' => __( 'Cherry Data Manager', $cherry_data_manager->slug ),
						'menu_title' => __( 'Cherry Data Manager', $cherry_data_manager->slug ),
						'capability' => 'manage_options',
						'menu_slug'  => $cherry_data_manager->data_page,
						'function'   => array( $this, 'show_admin_pages' ),
					),
				)
			);

		}

		/**
		 * Register the administration menu for this plugin into the WordPress Dashboard menu.
		 *
		 * @since 1.0.0
		 */
		public function add_admin_pages() {

			global $cherry_data_manager;

			if ( empty( $this->pages ) ) {
				return;
			}

			do_action( 'cherry_data_manager_add_pages', $this );

			if ( true === $this->added ) {
				return;
			}

			foreach ( $this->pages as $page_id => $data ) {
				add_management_page(
					$data['page_title'],
					$data['menu_title'],
					$data['capability'],
					$data['menu_slug'],
					$data['function']
				);
			}

		}

		/**
		 * show wizard management page
		 *
		 * @since 1.0.0
		 */
		public function show_admin_pages() {

			$page = isset($_GET['page']) ? $_GET['page'] : '';

			if ( !$page ) {
				return;
			}

			if ( file_exists( CHERRY_DATA_MANAGER_DIR . 'includes/views/' . $page . '.php' ) ) {
				include_once( CHERRY_DATA_MANAGER_DIR . 'includes/views/' . $page . '.php' );
			}

		}

	}

	new cherry_data_manager_interface();

}