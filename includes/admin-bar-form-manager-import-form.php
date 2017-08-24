<?php
/**
 * Plugin Name: Admin Bar Form Manager - New Form
 * Plugin URI: http://ounceoftalent.com
 * Description: Adds a "New Form" option to the Form Manager menu in the admin bar.
 * Author: David Smith
 * Version: 0.1
 * Author URI: http://ounceoftalent.com
 */
class GW_ABFM_Import_Form {

	private static $instance = null;

	public static function get_instance() {
		if( null == self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

	private function __construct() {

		add_filter( 'gwabfm_menu_items', array( $this, 'add_menu_item' ), 10, 2 );

	}

	public function add_menu_item( $items, $defaults ) {

		$item = wp_parse_args( array(
			'id' => sanitize_title_with_dashes( 'import-form' ),
			'title' => sprintf( '<span style="opacity:0.3;">cmd:</span> %s', __( 'Import Form' ) ),
			'href' => add_query_arg( array(
				'page' => 'gf_export',
				'view' => 'import_form',
			), admin_url( 'admin.php' ) ),
			'meta' => array( 'class' => '' )
		), $defaults );

		array_unshift( $items, $item );

		return $items;
	}

}

function gw_abfm_import_form() {
	return GW_ABFM_Import_Form::get_instance();
}

gw_abfm_import_form();