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
 * Media control frame popup.
 *
 * This script listens for a click on an element with a
 * 'blazersix-media-control-choose' class residing within an element with
 * class of 'blazersix-media-control'. When the click is detected it looks for
 * custom data attributes to modify the behavior of the media manager.
 */
jQuery(function($) {
	var Attachment = wp.media.model.Attachment,
		$control, $controlTarget, mediaControl;
		
	mediaControl = {
		// Initializes a new media manage or returns an existing frame.
		// @see wp.media.featuredImage.frame()
		frame: function() {
			if ( this._frame )
				return this._frame;

			this._frame = wp.media({
				title: $control.data('title') || BlazerSixMediaControl.frameTitle,
				library: {
					type: 'image'
				},
				button: {
					text: $control.data('update-text') || BlazerSixMediaControl.frameUpdateText
				},
				multiple: $control.data( 'select-multiple' ) || false
			});
			
			this._frame.on( 'open', this.updateLibrarySelection ).state('library').on( 'select', this.select );
			
			return this._frame;
		},
		
		// Updates the control when an image is selected from the media library.
		select: function() {
			var selection = this.get('selection');
			
			// Insert the selected attachment ids into the target element.
			if ( $controlTarget.length ) {
				$controlTarget.val( selection.pluck('id') );
			}
			
			// Trigger an event on the control to allow custom updates.
			$control.trigger( 'selectionChange.blazersix', [ selection ] );
		},
		
		// Updates the selected image in the media library based on the image in the control.
		updateLibrarySelection: function() {
			var selection = this.get('library').get('selection'),
				attachment, selectedIds;
			
			if ( $controlTarget.length ) {
				selectedIds = $controlTarget.val();
				if ( selectedIds && '' !== selectedIds && -1 !== selectedIds && '0' !== selectedIds ) {
					attachment = Attachment.get( selectedIds );
					attachment.fetch();
				}
			}
			
			selection.reset( attachment ? [ attachment ] : [] );
		},
		
		init: function() {
			$('#wpbody').on('click', '.blazersix-media-control-choose', function(e) {
				var targetSelector;
				
				e.preventDefault();
				
				$control = $(this).closest('.blazersix-media-control');
				
				targetSelector = $control.data('target') || '.blazersix-media-control-target';
				if ( 0 === targetSelector.indexOf('#') ) {
					// Context doesn't matter if the selector is an ID.
					$controlTarget = $( targetSelector );
				} else {
					// Search for other selectors within the context of the control.
					$controlTarget = $control.find( targetSelector );
				}
				
				mediaControl.frame().open();
			});
		}
	};
	
	mediaControl.init();
});