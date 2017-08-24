<?php

class GW_ABFM_Debug {

	private static $instance = null;

	public static function get_instance() {
		if( null == self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

	private function __construct() {

		add_filter( 'gwabfm_menu_items', array( $this, 'add_menu_item' ), 10, 2 );
		add_action( 'admin_init',        array( $this, 'maybe_enable_conflict_tester' ) );

	}

	public function add_menu_item( $items, $defaults ) {

		$item = wp_parse_args( array(
			'id' => sanitize_title_with_dashes( 'debug' ),
			'title' => sprintf( '<span style="opacity:0.3;">cmd:</span> %s', __( 'Enable Debug' ) ),
			'href' => add_query_arg( array(
				'page'        => 'gravityformsdebug',
				'auto_enable' => true
			), admin_url( 'admin.php' ) ),
			'meta' => array( 'class' => '' )
		), $defaults );

		array_unshift( $items, $item );

		return $items;
	}

	public function maybe_enable_conflict_tester() {

		if( rgget( 'page' ) == 'gravityformsdebug' && rgget( 'auto_enable' ) ) {
			gravity_forms_debug()->enable_conflict_tester();
			wp_redirect( remove_query_arg( 'auto_enable' ) );
			exit;
		}

	}

}

function gw_abfm_debug() {
	return GW_ABFM_Debug::get_instance();
}

gw_abfm_debug();