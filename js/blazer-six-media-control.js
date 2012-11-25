(function($) {
	BlazerSixMediaControl.updateSizeDropdownOptions = function( field, sizes ) {
		var currentValue = field.val(),
			options;
		
		if ( sizes ) {
			$.each( sizes, function( key, size ) {
				var name;
				
				if ( key in BlazerSixMediaControl.imageSizeNames ) {
					name = BlazerSixMediaControl.imageSizeNames[ key ];
				}
				
				options += '<option value="' + key + '">' + name + ' (' + size.width + '&times;' + size.height + ')</option>';
			});
		}
		
		if ( ! options ) {
			name = BlazerSixMediaControl.imageSizeNames['full'] || BlazerSixMediaControl.fullSizeLabel;
			options = '<option value="full">' + name + '</option>';
		}
		
		// Try to maintain the previously selected size if it still exists.
		field.html( options ).val( currentValue ).removeAttr('disabled');
	};
})(jQuery);

/**
 * Media control frame popup functionality.
 *
 * This script listens for a click on an element with a
 * 'blazersix-media-control-choose' class residing within an element with
 * class of 'blazersix-media-control'. When the click is detected it looks for
 * custom data attributes to modify the behavior of the media frame popup.
 *
 * @see post_thumbnail_meta_box()
 */
jQuery(function($) {
	var Attachment = wp.media.model.Attachment,
		frame, title, updateText, $control, $controlTarget;
	
	$('#wpbody').on('click', '.blazersix-media-control-choose', function(e) {
		var mediaIds = false,
			attachment, options, selectedIds, targetSelector;
		
		e.preventDefault();
		
		$control = $(this).closest('.blazersix-media-control');
		title = $control.data('title') || BlazerSixMediaControl.frameTitle;
		updateText = $control.data('update-text') || BlazerSixMediaControl.frameUpdateText;
		
		targetSelector = $control.data('target') || '.blazersix-media-control-target';
		if ( 0 === targetSelector.indexOf('#') ) {
			// Context doesn't matter if the selector is an ID.
			$controlTarget = $( targetSelector );
		} else {
			// Search for other selectors within the context of the control.
			$controlTarget = $control.find( targetSelector );
		}
		
		if ( $controlTarget.length ) {
			selectedIds = $controlTarget.val();
			if ( selectedIds && -1 !== selectedIds && '0' !== selectedIds ) {
				mediaIds = selectedIds;
				// @todo Account for multiple, comma-separated ids here.
				
				// Make sure the attachment is available when the media frame opens.
				// @see https://core.trac.wordpress.org/ticket/22494
				attachment = Attachment.get( mediaIds );
				attachment.fetch();
			}
		}
		
		if ( frame ) {
			if ( mediaIds ) {
				frame.state().get('selection').add( attachment );
			} else {
				frame.state().get('selection').clear();
			}
			
			frame.open();
			return;
		}
		
		options = {
			title: title,
			library: {
				type: 'image' // @todo Other types?
				
			},
			multiple: $control.data( 'select-multiple' ) || false,
			selection: ( mediaIds ) ? [ attachment ] : null
		};
		
		frame = wp.media( options );
		
		frame.get('library').set( 'filterable', 'uploaded' );
		
		frame.toolbar.on( 'activate:select', function() {
			frame.toolbar.view().set({
				select: {
					style: 'primary',
					text: updateText,

					click: function() {
						var selection = frame.state().get('selection');
						
						frame.close();
						
						// Insert the selected attachment ids into the target element.
						if ( $controlTarget.length ) {
							$controlTarget.val( selection.pluck('id') );
						}
						
						$control.trigger( 'selectionChange.blazersixMediaControl', [ selection ] );
					}
				}
			});
		});
		
		frame.toolbar.mode('select');
	});
});