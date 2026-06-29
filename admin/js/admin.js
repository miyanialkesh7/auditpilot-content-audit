/* global cawpData, jQuery */
( function ( $ ) {
	'use strict';

	var CAWP = {
		scanId: null,
		postIds: [],
		totalPosts: 0,
		scannedCount: 0,
		batchSize: cawpData.batchSize || 10,
		isRunning: false,

		init: function () {
			this.bindEvents();
		},

		bindEvents: function () {
			$( '#cawp-start-scan' ).on( 'click', function ( e ) {
				e.preventDefault();
				CAWP.startScan();
			} );
		},

		startScan: function () {
			if ( this.isRunning ) {
				return;
			}

			this.isRunning = true;
			this.scannedCount = 0;

			$( '#cawp-start-scan' ).prop( 'disabled', true ).text( cawpData.i18n.scanning );
			$( '#cawp-scan-progress' ).slideDown( 200 );
			this.updateProgress( 0, cawpData.i18n.starting );

			$.ajax( {
				url: cawpData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cawp_start_scan',
					nonce: cawpData.nonce,
				},
				success: function ( response ) {
					if ( response.success ) {
						CAWP.scanId    = response.data.scan_id;
						CAWP.postIds   = response.data.post_ids;
						CAWP.totalPosts = response.data.total;
						CAWP.processBatch();
					} else {
						CAWP.handleError( response.data ? response.data.message : cawpData.i18n.error );
					}
				},
				error: function () {
					CAWP.handleError( cawpData.i18n.error );
				},
			} );
		},

		processBatch: function () {
			if ( this.postIds.length === 0 ) {
				this.completeScan();
				return;
			}

			var batch = this.postIds.splice( 0, this.batchSize );
			var progressLabel = cawpData.i18n.progress
				.replace( '%1$d', this.scannedCount + 1 )
				.replace( '%2$d', this.totalPosts );

			this.updateProgress( this.scannedCount / this.totalPosts * 100, progressLabel );

			$.ajax( {
				url: cawpData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cawp_scan_batch',
					nonce: cawpData.nonce,
					scan_id: this.scanId,
					post_ids: batch,
				},
				success: function ( response ) {
					if ( response.success ) {
						CAWP.scannedCount += response.data.scanned;
						CAWP.processBatch();
					} else {
						CAWP.handleError( response.data ? response.data.message : cawpData.i18n.error );
					}
				},
				error: function () {
					CAWP.handleError( cawpData.i18n.error );
				},
			} );
		},

		completeScan: function () {
			this.updateProgress( 100, cawpData.i18n.complete );

			$.ajax( {
				url: cawpData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cawp_get_scan_status',
					nonce: cawpData.nonce,
					scan_id: this.scanId,
					complete: '1',
				},
				success: function ( response ) {
					if ( response.success ) {
						$( '#cawp-progress-detail' ).text( cawpData.i18n.redirecting );
						setTimeout( function () {
							window.location.href = response.data.results_url;
						}, 1500 );
					}
				},
			} );
		},

		updateProgress: function ( percent, label ) {
			percent = Math.min( 100, Math.max( 0, Math.round( percent ) ) );
			$( '#cawp-progress-bar' ).css( 'width', percent + '%' );
			$( '#cawp-progress-percent' ).text( percent + '%' );
			$( '#cawp-progress-label' ).text( label );
		},

		handleError: function ( message ) {
			this.isRunning = false;
			$( '#cawp-start-scan' ).prop( 'disabled', false ).text( cawpData.i18n.startScan );
			$( '#cawp-progress-label' ).text( message );
			$( '#cawp-progress-bar' ).css( { 'background': '#d63638', 'width': '100%' } );
		},
	};

	$( document ).ready( function () {
		CAWP.init();
	} );

} )( jQuery );
