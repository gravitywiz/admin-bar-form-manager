<?php

class GW_ABFM_Export_Form {

	private static $instance = null;

	public static function get_instance() {
		if( null == self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

	private function __construct() {

		add_filter( 'gwabfm_menu_items', array( $this, 'add_menu_item' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'maybe_export_form' ) );

	}

	public function add_menu_item( $items, $defaults ) {

		if( ! GFForms::get_page() || ! rgget( 'id' ) ) {
			return $items;
		}

		$item = wp_parse_args( array(
			'id' => sanitize_title_with_dashes( 'export-form' ),
			'title' => sprintf( '<span style="opacity:0.3;">cmd:</span> %s', __( 'Export Current Form' ) ),
			'href' => add_query_arg( array(
				'gwabfm_action' => 'export_form',
			) ),
			'meta' => array( 'class' => '' )
		), $defaults );

		array_unshift( $items, $item );

		return $items;
	}

	public function maybe_export_form() {

		if( rgget( 'gwabfm_action' ) != 'export_form' ) {
			return;
		}

		$this->export_form();

	}

	public function export_form() {
		GFExport::export_forms( array( rgget( 'id' ) ) );
	}

}

function gw_abfm_export_form() {
	return GW_ABFM_Export_Form::get_instance();
}

gw_abfm_export_form();