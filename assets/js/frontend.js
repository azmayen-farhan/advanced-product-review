( function () {
	'use strict';

	if ( typeof aprData === 'undefined' ) {
		return;
	}

	var MAX_IMAGES = aprData.maxImages || 3;
	var MAX_SIZE_BYTES = ( aprData.maxSizeMB || 2 ) * 1024 * 1024;
	var ALLOWED_TYPES = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];

	function initWidget( widget ) {
		var form = widget.querySelector( '.apr-review-form' );
		if ( ! form ) {
			return;
		}

		var dropzone = widget.querySelector( '.apr-dropzone' );
		var fileInput = widget.querySelector( '.apr-file-input' );
		var previewList = widget.querySelector( '.apr-preview-list' );
		var messageEl = widget.querySelector( '.apr-form-message' );
		var submitBtn = widget.querySelector( '.apr-submit-btn' );
		var hasUpload = !! ( dropzone && fileInput && previewList );

		var selectedFiles = [];

		function renderPreviews() {
			if ( ! previewList ) {
				return;
			}
			previewList.innerHTML = '';
			selectedFiles.forEach( function ( file, index ) {
				var item = document.createElement( 'div' );
				item.className = 'apr-preview-item';

				var img = document.createElement( 'img' );
				img.src = URL.createObjectURL( file );
				item.appendChild( img );

				var removeBtn = document.createElement( 'button' );
				removeBtn.type = 'button';
				removeBtn.className = 'apr-preview-remove';
				removeBtn.innerHTML = '&times;';
				removeBtn.setAttribute( 'aria-label', 'Remove image' );
				removeBtn.addEventListener( 'click', function () {
					selectedFiles.splice( index, 1 );
					renderPreviews();
				} );
				item.appendChild( removeBtn );

				previewList.appendChild( item );
			} );
		}

		function setMessage( text, type ) {
			messageEl.textContent = text || '';
			messageEl.className = 'apr-form-message';
			if ( type ) {
				messageEl.classList.add( 'apr-message--' + type );
			}
		}

		function addFiles( fileList ) {
			var incoming = Array.prototype.slice.call( fileList );

			for ( var i = 0; i < incoming.length; i++ ) {
				var file = incoming[ i ];

				if ( selectedFiles.length >= MAX_IMAGES ) {
					setMessage( aprData.i18n.tooManyImages, 'error' );
					break;
				}

				if ( ALLOWED_TYPES.indexOf( file.type ) === -1 ) {
					setMessage( aprData.i18n.invalidType, 'error' );
					continue;
				}

				if ( file.size > MAX_SIZE_BYTES ) {
					setMessage( aprData.i18n.imageTooBig, 'error' );
					continue;
				}

				selectedFiles.push( file );
			}

			renderPreviews();
		}

		if ( hasUpload ) {
			// Click to browse.
			dropzone.addEventListener( 'click', function () {
				fileInput.click();
			} );
			dropzone.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					fileInput.click();
				}
			} );

			fileInput.addEventListener( 'change', function () {
				addFiles( fileInput.files );
				fileInput.value = '';
			} );

			// Drag & drop.
			[ 'dragenter', 'dragover' ].forEach( function ( evtName ) {
				dropzone.addEventListener( evtName, function ( e ) {
					e.preventDefault();
					e.stopPropagation();
					dropzone.classList.add( 'apr-dropzone--drag' );
				} );
			} );

			[ 'dragleave', 'drop' ].forEach( function ( evtName ) {
				dropzone.addEventListener( evtName, function ( e ) {
					e.preventDefault();
					e.stopPropagation();
					dropzone.classList.remove( 'apr-dropzone--drag' );
				} );
			} );

			dropzone.addEventListener( 'drop', function ( e ) {
				if ( e.dataTransfer && e.dataTransfer.files ) {
					addFiles( e.dataTransfer.files );
				}
			} );
		}

		// Submit.
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			setMessage( '', null );

			var ratingField = form.querySelector( '[name="rating"]' );
			var textField = form.querySelector( '[name="review_text"]' );

			if ( ! ratingField.value ) {
				setMessage( aprData.i18n.selectRating, 'error' );
				return;
			}

			if ( ! textField.value.trim() ) {
				setMessage( aprData.i18n.writeReview, 'error' );
				return;
			}

			var formData = new FormData( form );
			formData.append( 'action', 'apr_submit_review' );

			selectedFiles.forEach( function ( file ) {
				formData.append( 'review_images[]', file );
			} );

			submitBtn.disabled = true;
			var originalLabel = submitBtn.textContent;
			submitBtn.textContent = aprData.i18n.submitting;

			fetch( aprData.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( data ) {
					submitBtn.disabled = false;
					submitBtn.textContent = originalLabel;

					if ( data && data.success ) {
						setMessage( data.data.message, 'success' );
						form.reset();
						selectedFiles = [];
						renderPreviews();
					} else {
						var msg = data && data.data && data.data.message ? data.data.message : aprData.i18n.genericError;
						setMessage( msg, 'error' );
					}
				} )
				.catch( function () {
					submitBtn.disabled = false;
					submitBtn.textContent = originalLabel;
					setMessage( aprData.i18n.genericError, 'error' );
				} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var widgets = document.querySelectorAll( '.apr-review-widget' );
		widgets.forEach( initWidget );
	} );
} )();
