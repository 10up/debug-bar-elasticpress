( function( $ ) {

	$( document ).ready( function() {
		var queries = document.querySelectorAll( '.ep-queries-debug' );

		if ( queries.length > 0 ) {
			queries = queries[0];

			queries.addEventListener( 'click', function( event ) {
				var queryWrapper = event.target;

				while ( event.currentTarget.contains( queryWrapper ) ) {
					if ( queryWrapper.nodeName === 'LI' ) {


						if ( event.target.className.match( /query-body-toggle/i ) ) {
							if ( queryWrapper.className.match( /hide-query-body/i ) ) {
								queryWrapper.className = queryWrapper.className.replace( /hide-query-body/i, '' );
							} else {
								queryWrapper.className += ' hide-query-body';
							}
						}

						if ( event.target.className.match( /query-result-toggle/i ) ) {
							if ( queryWrapper.className.match( /hide-query-results/i ) ) {
								queryWrapper.className = queryWrapper.className.replace( /hide-query-results/i, '' );
							} else {
								queryWrapper.className += ' hide-query-results';
							}
						}

						break;
					} else {
						queryWrapper = queryWrapper.parentNode;
					}
				}
			} );
		}
	} );

})( jQuery );