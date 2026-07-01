/* global apcaData, jQuery */
( function ( $ ) {
	'use strict';

	var APCA = {
		scanId: null,
		postIds: [],
		totalPosts: 0,
		scannedCount: 0,
		batchSize: apcaData.batchSize || 10,
		isRunning: false,

		init: function () {
			this.bindEvents();
		},

		bindEvents: function () {
			$( '#apca-start-scan' ).on( 'click', function ( e ) {
				e.preventDefault();
				APCA.startScan();
			} );
		},

		startScan: function () {
			if ( this.isRunning ) {
				return;
			}

			this.isRunning = true;
			this.scannedCount = 0;

			$( '#apca-start-scan' ).prop( 'disabled', true ).text( apcaData.i18n.scanning );
			$( '#apca-scan-progress' ).slideDown( 200 );
			this.updateProgress( 0, apcaData.i18n.starting );

			$.ajax( {
				url: apcaData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'apca_start_scan',
					nonce: apcaData.nonce,
				},
				success: function ( response ) {
					if ( response.success ) {
						APCA.scanId    = response.data.scan_id;
						APCA.postIds   = response.data.post_ids;
						APCA.totalPosts = response.data.total;
						APCA.processBatch();
					} else {
						APCA.handleError( response.data ? response.data.message : apcaData.i18n.error );
					}
				},
				error: function () {
					APCA.handleError( apcaData.i18n.error );
				},
			} );
		},

		processBatch: function () {
			if ( this.postIds.length === 0 ) {
				this.completeScan();
				return;
			}

			var batch = this.postIds.splice( 0, this.batchSize );
			var progressLabel = apcaData.i18n.progress
				.replace( '%1$d', this.scannedCount + 1 )
				.replace( '%2$d', this.totalPosts );

			this.updateProgress( this.scannedCount / this.totalPosts * 100, progressLabel );

			$.ajax( {
				url: apcaData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'apca_scan_batch',
					nonce: apcaData.nonce,
					scan_id: this.scanId,
					post_ids: batch,
				},
				success: function ( response ) {
					if ( response.success ) {
						APCA.scannedCount += response.data.scanned;
						APCA.processBatch();
					} else {
						APCA.handleError( response.data ? response.data.message : apcaData.i18n.error );
					}
				},
				error: function () {
					APCA.handleError( apcaData.i18n.error );
				},
			} );
		},

		completeScan: function () {
			this.updateProgress( 100, apcaData.i18n.complete );

			$.ajax( {
				url: apcaData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'apca_get_scan_status',
					nonce: apcaData.nonce,
					scan_id: this.scanId,
					complete: '1',
				},
				success: function ( response ) {
					if ( response.success ) {
						$( '#apca-progress-detail' ).text( apcaData.i18n.redirecting );
						setTimeout( function () {
							window.location.href = response.data.results_url;
						}, 1500 );
					}
				},
			} );
		},

		updateProgress: function ( percent, label ) {
			percent = Math.min( 100, Math.max( 0, Math.round( percent ) ) );
			$( '#apca-progress-bar' ).css( 'width', percent + '%' );
			$( '#apca-progress-percent' ).text( percent + '%' );
			$( '#apca-progress-label' ).text( label );
		},

		handleError: function ( message ) {
			this.isRunning = false;
			$( '#apca-start-scan' ).prop( 'disabled', false ).text( apcaData.i18n.startScan );
			$( '#apca-progress-label' ).text( message );
			$( '#apca-progress-bar' ).css( { 'background': '#d63638', 'width': '100%' } );
		},
	};

	$( document ).ready( function () {
		APCA.init();
	} );

} )( jQuery );
