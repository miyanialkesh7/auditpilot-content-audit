/* global siteInspector, jQuery */
( function ( $ ) {
	'use strict';

	var SI = {
		scanId: null,
		postIds: [],
		totalPosts: 0,
		scannedCount: 0,
		batchSize: siteInspector.batchSize || 10,
		isRunning: false,

		init: function () {
			this.bindEvents();
		},

		bindEvents: function () {
			$( '#si-start-scan' ).on( 'click', function ( e ) {
				e.preventDefault();
				SI.startScan();
			} );
		},

		startScan: function () {
			if ( this.isRunning ) {
				return;
			}

			this.isRunning = true;
			this.scannedCount = 0;

			$( '#si-start-scan' ).prop( 'disabled', true ).text( siteInspector.i18n.scanning );
			$( '#si-scan-progress' ).slideDown( 200 );
			this.updateProgress( 0, siteInspector.i18n.starting );

			$.ajax( {
				url: siteInspector.ajaxUrl,
				type: 'POST',
				data: {
					action: 'si_start_scan',
					nonce: siteInspector.nonce,
				},
				success: function ( response ) {
					if ( response.success ) {
						SI.scanId   = response.data.scan_id;
						SI.postIds  = response.data.post_ids;
						SI.totalPosts = response.data.total;
						SI.processBatch();
					} else {
						SI.handleError( response.data ? response.data.message : siteInspector.i18n.error );
					}
				},
				error: function () {
					SI.handleError( siteInspector.i18n.error );
				},
			} );
		},

		processBatch: function () {
			if ( this.postIds.length === 0 ) {
				this.completeScan();
				return;
			}

			var batch = this.postIds.splice( 0, this.batchSize );
			var progressLabel = siteInspector.i18n.progress
				.replace( '%1$d', this.scannedCount + 1 )
				.replace( '%2$d', this.totalPosts );

			this.updateProgress( this.scannedCount / this.totalPosts * 100, progressLabel );

			$.ajax( {
				url: siteInspector.ajaxUrl,
				type: 'POST',
				data: {
					action: 'si_scan_batch',
					nonce: siteInspector.nonce,
					scan_id: this.scanId,
					post_ids: batch,
				},
				success: function ( response ) {
					if ( response.success ) {
						SI.scannedCount += response.data.scanned;
						SI.processBatch();
					} else {
						SI.handleError( response.data ? response.data.message : siteInspector.i18n.error );
					}
				},
				error: function () {
					SI.handleError( siteInspector.i18n.error );
				},
			} );
		},

		completeScan: function () {
			this.updateProgress( 100, siteInspector.i18n.complete );

			$.ajax( {
				url: siteInspector.ajaxUrl,
				type: 'POST',
				data: {
					action: 'si_get_scan_status',
					nonce: siteInspector.nonce,
					scan_id: this.scanId,
					complete: '1',
				},
				success: function ( response ) {
					if ( response.success ) {
						$( '#si-progress-detail' ).text( siteInspector.i18n.redirecting );
						setTimeout( function () {
							window.location.href = response.data.results_url;
						}, 1500 );
					}
				},
			} );
		},

		updateProgress: function ( percent, label ) {
			percent = Math.min( 100, Math.max( 0, Math.round( percent ) ) );
			$( '#si-progress-bar' ).css( 'width', percent + '%' );
			$( '#si-progress-percent' ).text( percent + '%' );
			$( '#si-progress-label' ).text( label );
		},

		handleError: function ( message ) {
			this.isRunning = false;
			$( '#si-start-scan' ).prop( 'disabled', false ).text( siteInspector.i18n.startScan );
			$( '#si-progress-label' ).text( message );
			$( '#si-progress-bar' ).css( { 'background': '#d63638', 'width': '100%' } );
		},
	};

	$( document ).ready( function () {
		SI.init();
	} );

} )( jQuery );
