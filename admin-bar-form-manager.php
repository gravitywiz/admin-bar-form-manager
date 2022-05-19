<?php
/**
 * Plugin Name: Admin Bar Form Manager
 * Plugin URI: http://gravitywiz.com
 * Description: Adds an admin bar menu item for Gravity Forms, allowing you to easily access your forms.
 * Author: Gravity Wiz
 * Version: 0.3
 * Author URI: http://gravitywiz.com
 */
class GW_Admin_Bar_Form_Manager {

	private static $instance = null;

	public static function get_instance() {
		if( null == self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

	private function __construct() {

		add_action( 'gform_loaded', array( $this, 'init' ) );

	}

	public function init() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_head', array( $this, 'enhance_admin_bar_scripts_styles' ), 99 );
		add_action( 'admin_bar_menu', array( $this, 'enhance_admin_bar' ) );

		// These will be included in reverse order in the menu.
		require_once( 'includes/admin-bar-form-manager-export-form.php' );
		require_once( 'includes/admin-bar-form-manager-import-form.php' );
		require_once( 'includes/admin-bar-form-manager-new-nested-form.php' );
		require_once( 'includes/admin-bar-form-manager-new-form.php' );

	}

	public function enqueue_admin_scripts() {

		wp_enqueue_script( 'jquery-ui-autocomplete' );

	}

	public function enhance_admin_bar( $wp_admin_bar ) {
		global $wp_admin_bar;

		if( ! is_admin() || ! is_admin_bar_showing() || ! current_user_can( 'activate_plugins' ) )
			return null;

		$classes = array( 'gwabfm-admin-bar' );
		$menu_id = 'gw-admin-bar-form-manager';

		$args = array(
			'id'        => $menu_id,
			'parent'    => 'top-secondary',
			'title'     => __( 'Forms', 'gw-admin-bar-plugin-manager' ),
			'meta'      => array( 'class' => implode( ' ', $classes ) )
		);

		$wp_admin_bar->add_node( $args );

		$items = $this->get_menu_items( array( 'parent' => $menu_id ) );

		foreach( $items as $item ) {
			$wp_admin_bar->add_node( $item );
		}

	}

	public function get_menu_items( $defaults ) {

		$forms = GFFormsModel::get_forms( null, 'id', 'DESC' );
		$items = array();

		foreach( $forms as $form ) {

			$items[] = wp_parse_args( array(
				'id' => sanitize_title_with_dashes( $form->title . '-' . $form->id ),
				'title' => sprintf( '%s <span style="opacity:0.3;">ID: %s</span>', GFCommon::truncate_middle( $form->title, 50 ), $form->id ),
				'href' => add_query_arg( array(
					'page' => 'gf_edit_forms',
					'id'   => $form->id
				), admin_url( 'admin.php' ) ),
				'meta' => array( 'class' => '' )
			), $defaults );

		}

		return apply_filters( 'gwabfm_menu_items', $items, $defaults );
	}

	public function enhance_admin_bar_scripts_styles() {
		?>

		<style type="text/css">
			.gwabfm-admin-bar .inactive { opacity: 0.5; }
			.gwabfm-admin-bar .ab-sub-wrapper { display: block; overflow-y: auto; overflow-x: hidden; }
			.gwabfm-admin-bar > div.ab-item.search-active { padding: 0 !important; }
			.gwabfm-admin-bar input { padding: 0 10px !important; background-color: #333; border: 0; color: #ccc; }
			.gwabfm-admin-bar .ab-submenu li.selected { background-color: #666 !important; }
			.gwabfm-admin-bar .ab-submenu li span { line-height: inherit !important; }
		</style>

		<script type="text/javascript">
			jQuery( document ).ready( function($) {

				var menu             = $( '#wp-admin-bar-gw-admin-bar-form-manager' ),
					rootItem         = menu.children( 'div.ab-item' ),
					rootText         = $( '<span></span>' ).text( rootItem.text() ),
					searchInput      = $( '<input type="text" placeholder="Search forms..." style="display:none;" />' ),
					subMenuWrap      = menu.find( '.ab-sub-wrapper' ),
					subMenu          = subMenuWrap.children( 'ul' ),
					origHTML         = subMenu.html(),
					selectedIndex    = -1,
					delta            = 500,
					lastKeypressTime = 0,
					keyMap           = [],
					triggerKeyCode   = 70;

				$( document ).on( 'keydown keyup', function( e ) {

					e = e || event; // to deal with IE
					keyMap[ e.keyCode ] = e.type == 'keydown';

					// listen for shift + double f
					var isKeyComboPressed = keyMap[16] && keyMap[ triggerKeyCode ];

					if( ! isKeyComboPressed ) {
						return;
					} else if( isKeyComboPressed && e.keyCode != triggerKeyCode ) {
						lastKeypressTime = 0;
						keyMap = [];
					}

					var thisKeypressTime = new Date();

					if ( thisKeypressTime - lastKeypressTime <= delta ) {
						rootItem.click();
						thisKeypressTime = 0;
						return false;
					}

					lastKeypressTime = thisKeypressTime;

				} );

				rootItem.html( '' ).append( rootText, searchInput );
				subMenuWrap.css( {
					'maxHeight':  $( window ).height() - $( '#wpadminbar' ).height(),
					'minWidth':   subMenuWrap.width()
				} );

				rootItem.click( function() {

					if( searchInput.is( ':visible' ) ) {
						return;
					}

					rootText.hide();
					rootItem.addClass( 'search-active' );
					searchInput.show().focus();

				} );

				searchInput.keyup( function( e ) {

					// any character except enter, up arrow, down arrow
					if( $.inArray( e.which, [ 13, 38, 40 ] ) == -1 ) {
						selectedIndex = -1;
						filterList();
					}

				} ).keydown( function( e ) {

					if( e.which == 13 /* enter */ ) {
						location.href = subMenu.children().eq( selectedIndex ).find( 'a' ).attr( 'href' );
					} else if( e.which == 38 /* up arrow */ ) {
						navigateList( -1 );
					} else if( e.which == 40 /* down arrow */ ) {
						navigateList( 1 );
					} else if( e.which == 27 /* escape */ ) {
						searchInput.blur();
					}

				} ).focus( function() {

					menu.addClass( 'hover' );

				} ).blur( function() {
					rootText.show();
					rootItem.removeClass( 'search-active' );
					searchInput
						.val( '' )
						.hide();
				} );

				function navigateList( move ) {

					var $items = subMenu.children();

					selectedIndex = Math.max( selectedIndex + move, 0 );
					selectedIndex = Math.min( selectedIndex, $items.length - 1 );

					$items.removeClass( 'selected' ).eq( selectedIndex ).addClass( 'selected' );

				}

				function filterList() {

					var listItems = $( origHTML ).filter( 'li' );

					subMenu.html( '' );

					var $filteredList = listItems.filter( function() {

						var search = searchInput.val(),
							regex  = new RegExp( search, 'i' );

						// Always show "Add New" commands if search is numeric (and modify links accordingly)
						var $this = $( this );
						if ( $this.text().indexOf( 'cmd: Add' ) === 0 ) {
							if ( search && search.match(/^[0-9]*$/) ) {
								$this.find('span').after('<span> (' + search + ')</span>');
								$this.find('a').get(0).href += '&formTitle=' + encodeURIComponent( search );
								return true;
							}
						}
						return regex.test( $( this ).text() );
					} );

					$filteredList.appendTo( subMenu );

				}

			} );
		</script>

		<?php
	}

}

function gw_admin_bar_form_manager() {
	return GW_Admin_Bar_Form_Manager::get_instance();
}

gw_admin_bar_form_manager();
