<?php
/**
 * Image widget class.
 *
 * @package Blazer_Six_Widget_Image
 *
 * @since 0.1.0
 */
class Blazer_Six_Widget_Image extends WP_Widget {
	/**
	 * Setup widget options.
	 *
	 * @since 0.1.0
	 */
	function __construct() {
		$widget_options = array( 'classname' => 'widget_image', 'description' => __( 'An image from the media library', 'blazersix-widget-image-i18n' ) );
		$control_options = array( 'width' => 300 );
		parent::__construct( 'blazersix-widget-image', __( 'Image', 'blazersix-widget-image-i18n' ), $widget_options, $control_options );
		
		// Flush widget group cache when an attachment is saved, deleted, or the theme is switched.
		add_action( 'save_post', array( $this, 'flush_group_cache' ) );
		add_action( 'delete_attachment', array( $this, 'flush_group_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_group_cache' ) );
	}
	
	/**
	 * Widget front end display method.
	 *
	 * @since 0.1.0
	 */
	function widget( $args, $instance ) {
		$cache = (array) wp_cache_get( 'blazersix_widget_image', 'widget' );
		
		if ( isset( $cache[ $this->id ] ) ) {
			echo $cache[ $this->id ];
			return;
		}
		
		// Copy the original title so it can be passed to hooks.
		$instance['saved_title'] = $instance['title'];
		$instance['title'] = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		
		// Copy the original text so it can be passed to hooks.
		$instance['saved_text'] = $instance['text'];
		$instance['text'] = apply_filters( 'widget_text', empty( $instance['text'] ) ? '' : $instance['text'], $instance, $this->id_base );
		
		$link_open = '';
		$link_close = '';
		if ( ! empty ( $instance['link'] ) ) {
			$link_open = '<a href="' . esc_url( $instance['link'] ) . '">';
			$link_close = '</a>';
		}
		
		// Start building the output.
		$output = '';
		
		// Make sure the image ID is a valid attachment.
		$image = get_post( $instance['image_id'] );
		if ( empty( $instance['image_id'] ) || ! $image || 'attachment' != get_post_type( $image ) ) {
			$output = '<!-- Image Widget Error: Invalid Attachment ID -->';
		}
		
		if ( empty( $output ) ) {
			
			$output .= $args['before_widget'];
				
				// Allow custom output to override the default HTML.
				if ( $inside = apply_filters( 'blazersix_widget_image_output', '', $args, $instance ) ) {
					$output .= $inside;
				} else {
					$output .= ( empty( $instance['title'] ) ) ? '' : $args['before_title']. $instance['title'] . $args['after_title'];
					
					// Add the image.
					if ( ! empty( $instance['image_id'] ) ) {
						$image_size = ( ! empty( $instance['image_size'] ) ) ? $instance['image_size'] : apply_filters( 'blazersix_widget_image_output_default_size', 'medium' );
						
						$output .= sprintf( '<p>%s%s%s</p>',
							$link_open,
							wp_get_attachment_image( $instance['image_id'], $image_size ),
							$link_close
						);
					}
					
					// Add the text.
					if ( ! empty( $instance['text'] ) ) {
						$output .= apply_filters( 'the_content', $instance['text'] );
					}
					
					// Add a more link.
					if ( ! empty( $link_open ) && ! empty( $instance['link_text'] ) ) {
						$output .= '<p class="more">' . $link_open . $instance['link_text'] . $link_close . '</p>';
					}
				}
			
			$output .= $args['after_widget'];
		}
		
		echo $output;
		
		$cache[ $this->id ] = $output;
		wp_cache_set( 'blazersix_widget_image', array_filter( $cache ), 'widget' );
	}

	/**
	 * Form for modifying widget settings.
	 *
	 * @since 0.1.0
	 */
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array(
			'image_id'   => '',
			'image_size' => 'full',
			'link'       => '',
			'link_text'  => '',
			'title'      => '',
			'text'       => ''
		) );
		
		$instance['image_id'] = absint( $instance['image_id'] );
		$instance['title'] = wp_strip_all_tags( $instance['title'] );
		
		$button_class = array( 'button', 'button-hero', 'blazersix-media-control-choose' );
		$image_id = $instance['image_id'];
		
		// The order of fields can be modified, new fields can be registered, or existing fields can be removed here.
		$fields = (array) apply_filters( 'blazersix_widget_image_fields', array( 'image_size', 'link', 'link_text', 'text') );
		?>
		
		<div class="blazersix-widget-image-form">
			
			<?php do_action( 'blazersix_widget_image_form_before', $instance ); ?>
			
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'blazersix-widget-image-i18n' ); ?></label>
				<input type="text" name="<?php echo $this->get_field_name( 'title' ); ?>" id="<?php echo $this->get_field_id( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat">
			</p>
			
			<?php
			// @todo Implement data-media-type="image|?|?".
			// @todo Implement data-media-size="thumbnail|medium|full|etc".
			?>
			<p class="blazersix-widget-image-control blazersix-media-control<?php echo ( $image_id ) ? ' has-image' : ''; ?>"
				data-title="<?php esc_attr_e( 'Choose an Image for the Widget', 'blazersix-widget-image-i18n' ); ?>"
				data-update-text="<?php esc_attr_e( 'Update Image', 'blazersix-widget-image-i18n' ); ?>"
				data-target=".image-id"
				data-select-multiple="false">
				<?php
				if ( $image_id ) {
					echo wp_get_attachment_image( $image_id, 'medium', false );
					unset( $button_class[ array_search( 'button-hero', $button_class ) ] );
				}
				?>
				<input type="hidden" name="<?php echo $this->get_field_name( 'image_id' ); ?>" id="<?php echo $this->get_field_id( 'image_id' ); ?>" value="<?php echo $image_id; ?>" class="image-id blazersix-media-control-target">
				<a href="#" class="<?php echo join( ' ', $button_class ); ?>"><?php _e( 'Choose an Image', 'blazersix-widget-image-i18n' ); ?></a>
			</p>
			
			<?php
			if ( ! empty( $fields ) ) {
				foreach ( $fields as $field ) {
					switch ( $field ) {
						case 'image_size' :
							$sizes = $this->get_image_sizes( $image_id );
							?>
							<p>
								<label for="<?php echo $this->get_field_id( 'image_size' ); ?>"><?php _e( 'Size:', 'blazersix-widget-image-i18n' ); ?></label>
								<select name="<?php echo $this->get_field_name( 'image_size' ); ?>" id="<?php echo $this->get_field_id( 'image_size' ); ?>" class="widefat image-size"<?php echo ( sizeof( $sizes ) < 2 ) ? ' disabled="disabled"' : ''; ?>>
									<?php
									foreach ( $sizes as $id => $label ) {
										printf( '<option value="%s"%s>%s</option>',
											esc_attr( $id ),
											selected( $instance['image_size'], $id, false ),
											esc_html( $label )
										);
									}
									?>
								</select>
							</p>
							<?php
							break;
						
						case 'link' :
							?>
							<p>
								<label for="<?php echo $this->get_field_id( 'link' ); ?>"><?php _e( 'Link:', 'blazersix-widget-image-i18n' ); ?></label>
								<input type="text" name="<?php echo $this->get_field_name( 'link' ); ?>" id="<?php echo $this->get_field_id( 'link' ); ?>" value="<?php echo esc_url_raw( $instance['link'] ); ?>" class="widefat">
							</p>
							<?php		
							break;
						
						case 'link_text' :
							?>
							<p>
								<label for="<?php echo $this->get_field_id( 'link_text' ); ?>"><?php _e( 'Link Text:', 'blazersix-widget-image-i18n' ); ?></label>
								<input type="text" name="<?php echo $this->get_field_name( 'link_text' ); ?>" id="<?php echo $this->get_field_id( 'link_text' ); ?>" value="<?php echo esc_attr( $instance['link_text'] ); ?>" class="widefat">
							</p>
							<?php
							break;
						
						case 'text' :
							?>
							<p>
								<label for="<?php echo $this->get_field_id( 'text' ); ?>"><?php _e( 'Text:', 'blazersix-widget-image-i18n' ); ?></label>
								<textarea name="<?php echo $this->get_field_name( 'text' ); ?>" id="<?php echo $this->get_field_id( 'text' ); ?>" rows="4" class="widefat"><?php echo esc_textarea( $instance['text'] ); ?></textarea>
							</p>
							<?php
							break;
						
						default :
							// Custom fields can be added using this action.
							do_action( 'blazersix_widget_image_field-' . sanitize_key( $field ), $this, $instance );
					}
				}
			}
			
			do_action( 'blazersix_widget_image_form_after', $instance );
			?>
			
		</div>
		<?php
	}
	
	/**
	 * Save widget settings.
	 *
	 * @since 0.1.0
	 */
	function update( $new_instance, $old_instance ) {
		$instance = wp_parse_args( $new_instance, $old_instance );
		
		$instance = apply_filters( 'blazersix_widget_image_instance', $instance, $new_instance, $old_instance );
		
		$instance['title'] = wp_strip_all_tags( $new_instance['title'] );
		$instance['image_id'] = absint( $new_instance['image_id'] );
		$instance['link'] = esc_url_raw( $new_instance['link'] );
		
		$this->flush_widget_cache();
		
		return $instance;
	}
	
	/**
	 * Get the various sizes of an images.
	 *
	 * @since 0.1.0
	 *
	 * @param int $image_id Image attachment ID.
	 * @return array List of image size keys and their localized labels.
	 */
	function get_image_sizes( $image_id ) {
		$sizes = array( 'full' => __( 'Full Size', 'blazersix-widget-image-i18n' ) );
		
		$imagedata = wp_get_attachment_metadata( $image_id );
		if ( isset( $imagedata['sizes'] ) ) {
			$size_names = Blazer_Six_Widget_Image_Loader::get_image_size_names();
			
			$sizes['full'] .= ( isset( $imagedata['width'] ) && isset( $imagedata['height'] ) ) ? sprintf( ' (%d&times;%d)', $imagedata['width'], $imagedata['height'] ) : '';
			
			foreach( $imagedata['sizes'] as $_size => $data ) {
				$label  = ( isset( $size_names[ $_size ] ) ) ? $size_names[ $_size ] : ucwords( $_size );
				$label .= sprintf( ' (%d&times;%d)', $data['width'], $data['height'] );
				
				$sizes[ $_size ] = $label;
			}
		}
		
		return $sizes;
	}
	
	/**
	 * Remove a single image widget from the cache.
	 *
	 * @since 0.1.0
	 */
	function flush_widget_cache() {
		$cache = (array) wp_cache_get( 'blazersix_widget_image', 'widget' );
		
		if ( isset( $cache[ $this->id ] ) ) {
			unset( $cache[ $this->id ] );
		}
		
		wp_cache_set( 'blazersix_widget_image', array_filter( $cache ), 'widget' );
	}
	
	/**
	 * Flush the cache for all image widgets.
	 *
	 * @since 0.1.0
	 */
	function flush_group_cache( $post_id = null ) {
		if ( 'save_post' == current_filter() && 'attachment' != get_post_type( $post_id ) ) {
			return;
		}
		
		wp_cache_delete( 'blazersix_widget_image', 'widget' );
	}
}
?>