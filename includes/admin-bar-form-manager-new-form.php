<?php
/**
 * Plugin Name: Admin Bar Form Manager - New Form
 * Plugin URI: http://ounceoftalent.com
 * Description: Adds a "New Form" option to the Form Manager menu in the admin bar.
 * Author: David Smith
 * Version: 0.1
 * Author URI: http://ounceoftalent.com
 */
class GW_ABFM_New_Form {

	public $form_title_base    = 'Test Form';
	public $use_roman_numerals = true;

	private static $instance = null;

	public static function get_instance() {
		if( null == self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

	private function __construct() {

		add_filter( 'gwabfm_menu_items', array( $this, 'add_menu_item' ), 10, 2 );
		add_action( 'load-forms_page_gf_new_form', array( $this, 'maybe_add_new_form' ) );
		add_action( 'load-forms1_page_gf_new_form', array( $this, 'maybe_add_new_form' ) ); // @hack: if GF has updated, it will be included as a 1 in the menu label

	}

	public function add_menu_item( $items, $defaults ) {

		$item = wp_parse_args( array(
			'id' => sanitize_title_with_dashes( 'add-new-form' ),
			'title' => sprintf( '<span style="opacity:0.3;">cmd:</span> %s', __( 'Add New Form' ) ),
			'href' => add_query_arg( array(
				'page'     => 'gf_new_form',
				'auto_add' => true
			), admin_url( 'admin.php' ) ),
			'meta' => array( 'class' => '' )
		), $defaults );

		array_unshift( $items, $item );

		return $items;
	}

	public function maybe_add_new_form() {

		if( ! rgget( 'auto_add' ) ) {
			return;
		}

		$form = $this->add_new_form();

		wp_redirect( add_query_arg( array(
			'page' => 'gf_edit_forms',
			'id'   => $form['id']
		), admin_url( 'admin.php' ) ) );

		exit;

	}

	public function add_new_form() {
		global $wpdb;

		$like           = "{$this->form_title_base}%";
		$sql            = $wpdb->prepare( "select title from {$wpdb->prefix}gf_form where title like %s", $like );
		$forms          = $wpdb->get_results( $sql );
		$highest_number = 0;

		foreach( $forms as $form ) {

			$title_bits = explode( ' ', $form->title );
			$number = array_pop( $title_bits );

			if( $this->use_roman_numerals ) {
				$number = $this->convert_roman_to_numeric( $number );
			}

			if( $number > $highest_number ) {
				$highest_number = $number;
			}

		}

		$new_number     = $this->use_roman_numerals ? $this->convert_numeric_to_roman( $highest_number + 1 ) : $highest_number + 1;
		$new_form_title = sprintf( '%s %s', $this->form_title_base, $new_number );

		$form = array(
			'title'          => $new_form_title,
			'description'    => '',
			'labelPlacement' => 'top_label',
			'fields'         => array(),
			'button'         => array(
				'type'       => 'text',
				'text'       => esc_html__( 'Submit', 'gravityforms' ),
				'imageUrl'   => ''
			),
			'descriptionPlacement' => 'below',
		);

		require_once( GFCommon::get_base_path() . '/form_detail.php' );

		$result = GFFormDetail::save_form_info( 0, json_encode( $form ) );
		$form   = false;

		if( absint( $result ) > 0 ) {
			$form = $result['meta'];
		}

		return $form;
	}

	public function convert_roman_to_numeric( $roman ) {

		$number = 0;
		$romans = $this->get_roman_numerals();

		foreach ( $romans as $key => $value ) {
			while ( strpos( $roman, $key ) === 0 ) {
				$number += $value;
				$roman   = substr( $roman, strlen( $key ) );
			}
		}

		return $number;
	}

	public function convert_numeric_to_roman( $number ) {

		$n      = intval( $number );
		$result = '';
		$romans = $this->get_roman_numerals();

		foreach ( $romans as $roman => $number ) {
			$matches = intval( $n / $number );
			$result .= str_repeat( $roman, $matches );
			$n       = $n % $number;
		}

		return $result;
	}

	public function get_roman_numerals() {
		return array(
			'M'  => 1000,
			'CM' => 900,
			'D'  => 500,
			'CD' => 400,
			'C'  => 100,
			'XC' => 90,
			'L'  => 50,
			'XL' => 40,
			'X'  => 10,
			'IX' => 9,
			'V'  => 5,
			'IV' => 4,
			'I'  => 1,
		);
	}

}

function gw_abfm_new_form() {
	return GW_ABFM_New_Form::get_instance();
}

gw_abfm_new_form();