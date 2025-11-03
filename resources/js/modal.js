/*!
 * Scripts for admin modal windows.
 * @since 2.1.1-alpha
 *
 * @package ReallySimpleCMS
 */
jQuery(document).ready($ => {
	'use strict';
	
	let clicked_button = null;
	
	/**
	 * Launch a modal window.
	 * @since 2.1.1-alpha
	 */
	$('.modal-launch').on('click', function() {
		clicked_button = this;
		
		$('body').addClass('modal-open');
		$('.modal').fadeIn(100);
		$('.modal').addClass('in');
		
		// Fetch the type of media that should display in the media library tab
		$('#media-type').text($(this).data('type'));
		
		// Load the media library
		$('.media-wrap').load($('.tabber #media.tab').children().data('href') + '?media_type=' + $('#media-type').text());
		
		// Check whether to insert media into the post content
		if($(this).data('insert') === true)
			$('#media-select').data('insert', true);
	});
	
	/**
	 * Close an open modal window.
	 * @since 2.1.1-alpha
	 */
	$('#modal-close').on('click', function() {
		modalClose();
	});
	
	/**
	 * Delete a specified item from the database.
	 * @since 2.1.8-alpha
	 */
	$('.delete-item').on('click', function(e) {
		e.preventDefault();
		
		// Replace the default warning text with the appropriate item type
		$('.delete-wrap h2 span').text($(this).data('item'));
		
		// Fetch the delete link from the data table and link the 'Confirm Delete' button to it
		$('#confirm-delete').attr('href', $(this).attr('href'));
	});
	
	/**
	 * Switch the modal tabs.
	 * @since 2.1.1-alpha
	 */
	$('.modal-header .tabber .tab').on('click', function() {
		if(!$(this).hasClass('active')) {
			$('.modal-header .tabber .tab').toggleClass('active');
			$('.modal-body .tab').toggleClass('active');
			
			// Check which tab is now active
			if($('#upload').hasClass('active')) {
				// Clean up the upload tab
				$('.upload-result').empty();
				$('#media-upload').trigger('reset');
				$('#media-select').prop('disabled', true);
			} else if($('#media').hasClass('active')) {
				// Clean up the media tab and load the media library
				$('.media-wrap').empty();
				$('.media-wrap').load($(this).children().data('href') + '?media_type=' + $('#media-type').text());
				$('.media-details .field').empty();
				$('#media-select').prop('disabled', true);
			}
		}
	});
	
	/**
	 * Submit the upload form.
	 * @since 2.1.6-alpha
	 */
	$('#media-upload').on('submit', function(e) {
		e.preventDefault();
		
		$.ajax({
			contentType: false,
			data: new FormData(this),
			method: 'POST',
			processData: false,
			success: result => {
				// Display the result
				$('.upload-result').html(result);
				
				// Check whether the upload was successful
				if(result.indexOf('success') !== -1)
					$('#media-select').prop('disabled', false);
			},
			url: $(this).attr('action')
		});
	});
	
	/**
	 * Select a media item.
	 * @since 2.1.2-alpha
	 */
	$(document).on('click', '.media-item', function() {
		if(!$(this).hasClass('selected')) {
			// Select the media item
			$('.media-item').removeClass('selected');
			$(this).addClass('selected');
			
			let fields = $(this).find('.hidden');
			let field = '';
			
			$(fields).each(function() {
				field = $(this).data('field');
				
				// Populate each field in the details section
				$('.media-details .' + field).html($(this).html());
			});
			
			$('#media-select').prop('disabled', false);
		} else {
			// Deselect the media item
			$(this).removeClass('selected');
			$('.media-details .field').empty();
			$('#media-select').prop('disabled', true);
		}
	});
	
	/**
	 * Select and insert media (via upload or media library).
	 * @since 2.1.3-alpha
	 */
	$('#media-select').on('click', function() {
		let data = {
			id: 0,
			title: '',
			filepath: '',
			mime_type: '',
			alt_text: '',
			width: 0
		};
		
		if($('#upload').hasClass('active')) {
			// Check whether the hidden fields are in the result
			if($('.upload-result .hidden[data-field="id"]').length && $('.upload-result .hidden[data-field="filepath"]').length) {
				data.id = $('.upload-result .hidden[data-field="id"]').text();
				data.title = $('.upload-result .hidden[data-field="title"]').text();
				data.filepath = $('.upload-result .hidden[data-field="filepath"]').text();
				data.mime_type = $('.upload-result .hidden[data-field="mime_type"]').text();
				data.width = $('.upload-result .hidden[data-field="width"]').text();
				
				// Check whether to insert media into the post content
				if($(this).data('insert') === true) {
					insertMedia($('.content .textarea-input[name="content"]'), data);
				} else {
					$(clicked_button).siblings('input[data-field="id"]').val(data.id);
					$(clicked_button).siblings('.image-wrap').children('img[data-field="thumb"]').attr('src', data.filepath);
				}
			}
		} else if($('#media').hasClass('active')) {
			if($('.media-item').hasClass('selected')) {
				data.id = $('.media-item.selected .hidden[data-field="id"]').text();
				data.title = $('.media-item.selected .hidden[data-field="title"]').text();
				data.filepath = $('.media-item.selected .hidden[data-field="filepath"] a').attr('href');
				data.mime_type = $('.media-item.selected .hidden[data-field="mime_type"]').text();
				data.alt_text = $('.media-item.selected .hidden[data-field="alt_text"]').text();
				data.width = $('.media-item.selected .hidden[data-field="width"]').text();
				
				// Check whether to insert media into the post content
				if($(this).data('insert') === true) {
					insertMedia($('.content .textarea-input[name="content"]'), data);
				} else {
					$(clicked_button).siblings('input[data-field="id"]').val(data.id);
					$(clicked_button).siblings('.image-wrap').children('img[data-field="thumb"]').attr('src', data.filepath);
				}
			} else {
				$(clicked_button).siblings('input[data-field="id"]').val(0);
				$(clicked_button).siblings('.image-wrap').children('img[data-field="thumb"]').attr('src', '//:0');
			}
		}
		
		// Check whether the thumbnail's source points to an image
		if($(clicked_button).siblings('.image-wrap').children('img[data-field="thumb"]').attr('src') !== '//:0' && $(clicked_button).siblings('.image-wrap').children('img[data-field="thumb"]').attr('src') !== '') {
			// Display the image
			$(clicked_button).siblings('.image-wrap').children('img[data-field="thumb"]').css({ maxWidth: 'none' });
			$(clicked_button).siblings('.image-wrap').addClass('visible');
			$(clicked_button).siblings('.image-wrap').children('img[data-field="thumb"]').removeClass('greyout');
			$(clicked_button).siblings('.image-wrap').width(data.width);
			$(clicked_button).siblings('.image-wrap').children('img[data-field="thumb"]').css({ maxWidth: '100%' });
		} else {
			// Hide the image
			$(clicked_button).siblings('.image-wrap').removeClass('visible');
		}
		
		modalClose();
	});
	
	/**
	 * Insert a media item into post content.
	 * @since 2.1.10-alpha
	 *
	 * @param object container
	 * @param object data
	 */
	function insertMedia(container, data) {
		let text = $(container).val();
		
		let content = {
			selection_start: $(container).prop('selectionStart'),
			selection_end: $(container).prop('selectionEnd'),
			text_before: '',
			text_after: ''
		};
		
		// Fetch the text before and after the selection
		content.text_before = text.substring(0, content.selection_start);
		content.text_after = text.substring(content.selection_end, text.length);
		
		let media = '';
		
		// Determine what kind of HTML tag to construct based on the media's MIME type
		if(data.mime_type.indexOf('image') !== -1) {
			// Construct an image tag
			media = '<img src="' + data.filepath + '" alt="' + (data.hasOwnProperty('alt_text') ? data.alt_text : '') + '">';
		} else if(data.mime_type.indexOf('audio') !== -1) {
			// Construct an audio tag
			media = '<audio src="' + data.filepath + '"></audio>';
		} else if(data.mime_type.indexOf('video') !== -1) {
			// Construct a video tag
			media = '<video src="' + data.filepath + '"></video>';
		} else {
			// Construct an anchor tag
			media = '<a href="' + data.filepath + '">' + data.title + '</a>';
		}
		
		$(container).val(content.text_before + media + content.text_after);
	}
	
	/**
	 * Close an open modal and perform cleanup.
	 * @since 2.1.3-alpha
	 */
	function modalClose() {
		$('body').removeClass('modal-open');
		$('.modal').fadeOut(500);
		$('.modal').removeClass('in');
		
		// Clean up the modal data
		if($('.modal').attr('id') === 'modal-upload') {
			$('.upload-result').empty();
			$('#media-upload').trigger('reset');
			$('.media-wrap').empty();
			$('.media-details .field').empty();
			$('#media-select').prop('disabled', true);
		}
		
		$('#media-select').data('insert', false);
	}
});