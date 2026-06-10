( function () {
	'use strict';

	function initExploreMenu() {
		var explore = document.querySelector( '.site-header__explore' );
		if ( ! explore ) {
			return;
		}

		var button = explore.querySelector( '.site-header__explore-toggle' );
		if ( ! button ) {
			return;
		}

		function closeMenu() {
			explore.classList.remove( 'is-open' );
			button.setAttribute( 'aria-expanded', 'false' );
		}

		button.addEventListener( 'click', function () {
			var isOpen = explore.classList.toggle( 'is-open' );
			button.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		} );

		document.addEventListener( 'click', function ( event ) {
			if ( ! explore.contains( event.target ) ) {
				closeMenu();
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) {
				closeMenu();
				button.focus();
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', initExploreMenu );
} )();
