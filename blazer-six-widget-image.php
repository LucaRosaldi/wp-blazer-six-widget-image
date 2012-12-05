<?php
/**
 * Plugin Name: Blazer Six Image Widget
 * Plugin URI: https://github.com/bradyvercher/wp-blazer-six-widget-image
 * Description: A simple image widget utilizing the new WordPress media manager.
 * Version: 0.1.1-beta
 * Author: Blazer Six, Inc.
 * Author URI: http://www.blazersix.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 3.5
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc., 59
 * Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package Blazer_Six_Widget_Image
 * @author Brady Vercher <brady@blazersix.com>
 * @copyright Copyright (c) 2012, Blazer Six, Inc.
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @todo Consider how to package as an include (paths need to be configurable).
 */

/**
 * Include the image widget class early to make it easy to extend.
 */
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-blazer-six-widget-image.php' );

/**
 * Load the plugin when plugins are loaded.
 *
 * Set a lower priority so it can easily be unhooked and potentially included
 * in a "Widget Pack" in the future.
 */
add_action( 'plugins_loaded', array( 'Blazer_Six_Widget_Image_Loader', 'load' ), 11 );

/**
 * The main plugin class for loading the widget and attaching necessary hooks.
 *
 * @since 0.1.0
 */
class Blazer_Six_Widget_Image_Loader {
	/**
	 * Setup functionality needed by the widget.
	 *
	 * @since 0.1.0
	 */
	public static function load() {
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'widgets_init', array( __CLASS__, 'register_widget' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );
		add_action( 'admin_head-widgets.php', array( __CLASS__, 'admin_head_widgets' ) );
		add_action( 'admin_footer-widgets.php', array( __CLASS__, 'admin_footer_widgets' ) );
	}
	
	/**
	 * Register and localize generic script libraries.
	 *
	 * The script 'blazersix-media-control' is loosely based on the 'Set
	 * Featured Image' functionality in the post thumbnail meta box. A
	 * preliminary attempt has been made to abstract it a bit in order to
	 * allow it to be re-used anywhere a similiar media selection feature is
	 * needed.
	 *
	 * Custom image size labels need to be added using the
	 * 'image_size_names_choose' filter.
	 *
	 * @since 0.1.0
	 */
	public static function init() {
		// @todo Check to see if this is already registered first?.
		wp_register_script( 'blazersix-media-control', plugin_dir_url( __FILE__ ) . 'js/blazer-six-media-control.js', array( 'media-upload', 'media-views' ) );
		
		wp_localize_script( 'blazersix-media-control', 'BlazerSixMediaControl', array(
			'frameTitle'      => __( 'Choose an Attachment', 'blazersix-widget-image-i18n' ),
			'frameUpdateText' => __( 'Update Attachment', 'blazersix-widget-image-i18n' ),
			'fullSizeLabel'   => __( 'Full Size', 'blazersix-widget-image-i18n' ),
			'imageSizeNames'  => self::get_image_size_names()
		) );
	}
	
	/**
	 * Register the image widget.
	 *
	 * @since 0.1.0
	 */
	public static function register_widget() {
		register_widget( 'Blazer_Six_Widget_Image' );
	}
	
	/**
	 * Enqueue scripts needed for selecting media.
	 *
	 * @since 0.1.0
	 */
	public static function admin_scripts( $hook_suffix ) {
		if ( 'widgets.php' == $hook_suffix ) {
			wp_enqueue_media();
			wp_enqueue_script( 'blazersix-media-control' );
		}
	}
	
	/**
	 * Output CSS for styling the image widget in the dashboard.
	 *
	 * @since 0.1.0
	 */
	public static function admin_head_widgets() {
		?>
		<style type="text/css">
		.widget .widget-inside .blazersix-widget-image-form .blazersix-media-control { padding: 20px 0; text-align: center; border: 1px dashed #aaa;}
		.widget .widget-inside .blazersix-widget-image-form .blazersix-media-control.has-image { padding: 10px; text-align: left; border: 1px dashed #aaa;}
		.widget .widget-inside .blazersix-widget-image-form .blazersix-media-control img { display: block; margin-bottom: 10px; max-width: 100%; height: auto;}
		.widget .widget-inside .blazersix-widget-image-form .blazersix-media-control img:not(.attachment-thumbnail) { width: 100%;}
		</style>
		<?php
	}
	
	/**
	 * Output custom handler for when an image is selected in the media popup
	 * frame.
	 *
	 * @since 0.1.0
	 */
	public static function admin_footer_widgets() {
		?>
		<script type="text/javascript">
		jQuery(function($) {
			$('#wpbody').on('selectionChange.blazersix', '.blazersix-media-control', function( e, selection, test ) {
				var $control = $( e.target ),
					$sizeField = $control.closest('.blazersix-widget-image-form').find('select.image-size'),
					model = selection.first(),
					sizes = model.get('sizes'),
					size, image;
				
				if ( sizes ) {
					// The image size to display in the widget.
					size = sizes['post-thumbnail'] || sizes.medium;
				}
				
				if ( $sizeField.length ) {
					// Builds the option elements for the size dropdown.
					BlazerSixMediaControl.updateSizeDropdownOptions( $sizeField, sizes );
				}
				
				size = size || model.toJSON();
				
				image = $( '<img />', { src: size.url, width: size.width } );
						
				$control.find('img').remove().end()
					.prepend( image )
					.addClass('has-image')
					.find('a.blazersix-media-control-choose').removeClass('button-hero');
			});
		});
		</script>
		<?php	
	}
	
	/**
	 * Get localized image size names.
	 *
	 * The 'image_size_names_choose' filter exists in core and should be
	 * hooked by plugin authors to provide localized labels for custom image
	 * sizes added using add_image_size().
	 *
	 * @see image_size_input_fields()
	 * @see http://core.trac.wordpress.org/ticket/20663
	 *
	 * @since 0.1.0
	 */
	public static function get_image_size_names() {
		return apply_filters( 'image_size_names_choose', array(
			'thumbnail'   => __( 'Thumbnail', 'blazersix-widget-image-i18n' ),
			'medium'      => __( 'Medium', 'blazersix-widget-image-i18n' ),
			'large'       => __( 'Large', 'blazersix-widget-image-i18n' ),
			'full'        => __( 'Full Size', 'blazersix-widget-image-i18n' )
		) );
	}
}
?>