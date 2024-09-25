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

	public $form_title_base    = 'Form';

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

		add_action( 'admin_footer', array( $this, 'output_default_field_name_script' ) );

	}

	public function add_menu_item( $items, $defaults ) {

		$item = wp_parse_args( array(
			'id'    => sanitize_title_with_dashes( 'add-new-form' ),
			'title' => sprintf( '<span style="opacity:0.3;">cmd:</span> %s', __( 'New Form' ) ),
			'href'  => add_query_arg( array(
				'page'     => 'gf_new_form',
				'auto_add' => true,
			), admin_url( 'admin.php' ) ),
			'meta'  => array( 'class' => '' ),
		), $defaults );

		array_unshift( $items, $item );

		return $items;
	}

	public function maybe_add_new_form() {

		if ( ! rgget( 'auto_add' ) ) {
			return;
		}

		$form = $this->add_new_form();

		wp_redirect( add_query_arg( array(
			'page' => 'gf_edit_forms',
			'id'   => $form['id'],
		), admin_url( 'admin.php' ) ) );

		exit;

	}

	public function add_new_form() {

		$highest_number    = 0;
		$placeholder_title = uniqid();

		$form = array(
			'title'                => $placeholder_title,
			'description'          => '',
			'labelPlacement'       => 'top_label',
			'fields'               => array(),
			'button'               => array(
				'type'     => 'text',
				'text'     => esc_html__( 'Submit', 'gravityforms' ),
				'imageUrl' => '',
			),
			'descriptionPlacement' => 'below',
		);

		require_once( GFCommon::get_base_path() . '/form_detail.php' );

		$result = GFFormDetail::save_form_info( 0, json_encode( $form ) );
		$form   = false;

		if ( absint( $result ) > 0 ) {

			$form      = $result['meta'];
			$formTitle = rgget( 'formTitle' );
			// Set form title to form title base + form ID.
			$form['title']     = empty( $formTitle ) ? sprintf( '%s %s', $this->form_title_base, $form['id'] ) : $formTitle;
			$form['is_active'] = true;

			GFAPI::update_form( $form );

		}

		return $form;
	}

	public function output_default_field_name_script() {

		if ( GFForms::get_page() != 'form_editor' ) {
			return;
		}

		$field_types       = GF_Fields::get_all();
		$field_type_labels = array();
		foreach ( $field_types as $field_type ) {
			$field_title = $field_type->get_form_editor_field_title();
			// Check if $field_title is not null before using ucwords
			if ( $field_title !== null ) {
				$field_type_labels[ $field_type->type ] = ucwords( $field_title );
			} else {
				// Handle the case where the title is null, if necessary
				$field_type_labels[ $field_type->type ] = 'Untitled';
			}
		}

		?>

		<script type="text/javascript">

			( function() {

				var fieldTypeLabels = <?php echo json_encode( $field_type_labels ); ?>;

				var gfCreateField = window.CreateField;
				window.CreateField = function( nextId, type, index ) {
					var field = gfCreateField( nextId, type, index );
					if ( type === 'submit' ) {
						return field;
					}
					if( field.label === '<?php _e( 'Untitled', 'gravityforms' ); ?>' ) {
						field.label = fieldTypeLabels[ field.type ];
					}
					field.label += ' ' + getNextAvailableLetter().toUpperCase();
					return field;
				};

				function getNextAvailableLetter( prefix ) {

					var letters = [ 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z' ];

					if( window.form.fields.length == 0 ) {
						return letters[0];
					}

					for( var i = 0; i < letters.length; i++ ) {

						var hasLetter = false;

						for( var j = 0; j < window.form.fields.length; j++ ) {
							var bits = window.form.fields[j].label.split( ' ' );
							if( bits[ bits.length - 1 ].toUpperCase() == letters[i].toUpperCase() ) {
								hasLetter = true;
								break;
							}
						}

						if( ! hasLetter ) {
							return letters[i];
						}

					}

					return '';
				}

				// Overwrite default GF choice inserstion so we can add our names.
				window.InsertFieldChoice = function( index ) {
					field = GetSelectedField();

					var price = field["enablePrice"] ? "0.00" : "";
					var new_choice = new Choice( getFullOrdinalChoice( index ), getFullOrdinalChoice( index ), price);
					if(window["gform_new_choice_" + field.type])
						new_choice = window["gform_new_choice_" + field.type](field, new_choice);

					if( typeof field.choices !== 'object' ) {
						field.choices = [];
					}

					field.choices.splice(index, 0, new_choice);

					LoadFieldChoices(field);
					UpdateFieldChoices(GetInputType(field));
				}

				function getFullOrdinalChoice( index ) {
					var ordinals = [ 'First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth', 'Ninth', 'Tenth', 'Eleventh', 'Twelfth', 'Thirteen', 'Fourteen', 'Fifteenth', 'Sixteenth', 'Seventeenth', 'Eighteenh', 'Nineteenth', 'Twentieth' ];
					var ordinal = rgar( ordinals, index, '' );
					if ( ordinal ) {
						ordinal += ' Choice';
					}
					return ordinal;
				}

			} )();

		</script>

		<?php
	}

}

function gw_abfm_new_form() {
	return GW_ABFM_New_Form::get_instance();
}

gw_abfm_new_form();
