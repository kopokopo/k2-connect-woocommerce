/* global jQuery, KKWooData */
( function ( $, window ) {
	const NORMAL_POLLING_INTERVAL = 3000;
	const MAX_POLLING_INTERVAL = 5000;
	const MAX_RETRIES = 10;

	const PollingManager = {
		timeout: null,
		retries: 0,

		start( callbacks = {}, pollingTemplate = 'polling', isPolling = true ) {
			this.stop(); // clear any existing timeout.
			this.retries = 0;
			this.callbacks = callbacks;
			this._poll( pollingTemplate, isPolling );
		},

		stop() {
			if ( this.timeout ) {
				clearTimeout( this.timeout );
				this.timeout = null;
			}
			this.retries = 0;
		},

		_poll( pollingTemplate = 'polling', isPolling = true ) {
			$.ajax( {
				url: `${ KKWooData.site_url }/wp-json/kkwoo/v1/payment-status`,
				method: 'GET',
				data: { order_key: KKWooData.order_key },
				dataType: 'json',
				success: ( data ) => {
					if (
						data.status === 'completed' ||
						data.status === 'processing'
					) {
						this.callbacks.onSuccess?.( data, pollingTemplate );
						this.stop();
					} else if ( data.status === 'failed' ) {
						this.callbacks.onFailure?.( data, pollingTemplate );
						this.stop();
					} else {
						this._retry(
							isPolling,
							pollingTemplate,
							NORMAL_POLLING_INTERVAL
						);
					}
				},
				error: () => {
					this._retry(
						isPolling,
						pollingTemplate,
						MAX_POLLING_INTERVAL
					);
				},
			} );
		},

		_retry( isPolling, pollingTemplate, interval ) {
			this.retries++;
			if ( isPolling && this.retries <= MAX_RETRIES ) {
				this.timeout = setTimeout( () => {
					this._poll( pollingTemplate, isPolling );
				}, interval );
			} else if ( this.retries > MAX_RETRIES || ! isPolling ) {
				this.callbacks.onNoResult?.( pollingTemplate );
				this.stop();
			}
		},
	};

	window.PollingManager = PollingManager;
} )( jQuery, window );
