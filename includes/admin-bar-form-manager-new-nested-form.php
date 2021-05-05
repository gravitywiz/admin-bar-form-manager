<?php
/**
 * Plugin Name: Admin Bar Form Manager - New Nested Form
 * Plugin URI: http://ounceoftalent.com
 * Description: Adds a "New Form" option to the Form Manager menu in the admin bar.
 * Author: David Smith
 * Version: 0.1
 * Author URI: http://ounceoftalent.com
 */
class GW_ABFM_New_Nested_Form {

	public $use_roman_numerals = false;

	private static $instance = null;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {

		add_filter( 'gwabfm_menu_items', array( $this, 'add_menu_item' ), 10, 2 );
		add_action( 'load-forms_page_gf_new_form', array( $this, 'maybe_add_new_form' ) );
		add_action( 'load-forms1_page_gf_new_form', array( $this, 'maybe_add_new_form' ) ); // @hack: if GF has updated, it will be included as a 1 in the menu label

	}

	public function add_menu_item( $items, $defaults ) {

		if ( ! is_callable( 'gp_nested_forms' ) ) {
			return $items;
		}

		$item = wp_parse_args( array(
			'id'    => sanitize_title_with_dashes( 'add-new-nested-form' ),
			'title' => sprintf( '<span style="opacity:0.3;">cmd:</span> %s', __( 'Add New Nested Form' ) ),
			'href'  => add_query_arg( array(
				'page'          => 'gf_new_form',
				'gpnf_auto_add' => true,
			), admin_url( 'admin.php' ) ),
			'meta'  => array( 'class' => '' )
		), $defaults );

		array_unshift( $items, $item );

		return $items;
	}

	public function maybe_add_new_form() {

		if ( ! rgget( 'gpnf_auto_add' ) ) {
			return;
		}

		$child_form  = $this->add_new_form();
		$parent_form = $this->add_new_form( $child_form['id'] );

		wp_redirect( add_query_arg( array(
			'page' => 'gf_edit_forms',
			'id'   => $parent_form['id']
		), admin_url( 'admin.php' ) ) );

		exit;

	}

	public function add_new_form( $child_form_id = false ) {

		$placeholder_title = uniqid();

		$fields = array();
		if ( $child_form_id ) {
			$fields[] = array(
				'id'         => 1,
				'type'       => 'form',
				'label'      => 'Nested Form A',
				'gpnfFields' => array( 1, 2 ),
				'gpnfForm'   => $child_form_id,
			);
		} else {
			$fields[] = array(
				'id'      => 1,
				'type'    => 'select',
				'label'   => 'Drop Down A',
				'choices' => array(
					array(
                        'text'       => 'First Choice',
                        'value'      => 'First Choice',
                        'isSelected' => false,
                    ),
					array(
                        'text'       => 'Second Choice',
                        'value'      => 'Second Choice',
                        'isSelected' => false,
                    ),
					array(
                        'text'       => 'Third Choice',
                        'value'      => 'Third Choice',
                        'isSelected' => false,
                    ),
				),
                'size'    => 'medium',
			);
			$fields[] = array(
				'id'    => 2,
				'type'  => 'text',
				'label' => 'Single Line Text B',
				'size'  => 'medium',
			);
		}

		$form = array(
			'title'                => $placeholder_title,
			'description'          => '',
			'labelPlacement'       => 'top_label',
			'fields'               => $fields,
			'button'               => array(
				'type'     => 'text',
				'text'     => esc_html__( 'Submit', 'gravityforms' ),
				'imageUrl' => ''
			),
			'descriptionPlacement' => 'below',
		);

		require_once( GFCommon::get_base_path() . '/form_detail.php' );

		$result = GFFormDetail::save_form_info( 0, json_encode( $form ) );
		$form   = false;

		if ( absint( $result ) > 0 ) {

			$form = $result['meta'];
			$formTitle = rgget( 'formTitle' );
			// Set form title to form title base + form ID.
			$base              = $child_form_id ? 'Parent' : 'Child';
			$title_form_id     = $child_form_id ? $child_form_id : $form['id'];
			if ( ! empty( $formTitle ) ) {
				$form['title'] = sprintf( '%s %s', $formTitle, $base );
			} else {
				$form['title'] = sprintf( '%s %s', $base, $title_form_id );
			}
			$form['is_active'] = true;

			GFAPI::update_form( $form );

		}

		return $form;
	}

}

function gw_abfm_new_nested_form() {
	return GW_ABFM_New_Nested_Form::get_instance();
}

gw_abfm_new_nested_form();
