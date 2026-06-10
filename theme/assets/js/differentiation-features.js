( function () {
	'use strict';

	function initReaderContexts() {
		var contexts = Array.prototype.slice.call( document.querySelectorAll( '[data-reader-context]' ) );
		if ( ! contexts.length ) {
			return;
		}

		var first = contexts[ 0 ];
		var controls = document.createElement( 'div' );
		controls.className = 'reader-context-switcher';
		controls.innerHTML =
			'<span class="reader-context-switcher__label">表示:</span>' +
			'<button type="button" data-context-mode="all">すべて</button>' +
			'<button type="button" data-context-mode="beginner">初心者向け</button>' +
			'<button type="button" data-context-mode="advanced">経験者向け</button>';
		first.parentNode.insertBefore( controls, first );

		function setMode( mode ) {
			contexts.forEach( function ( element ) {
				var level = element.getAttribute( 'data-reader-context' );
				var visible = mode === 'all' || level === 'all' || level === mode;
				element.hidden = ! visible;
			} );

			controls.querySelectorAll( 'button' ).forEach( function ( button ) {
				var active = button.getAttribute( 'data-context-mode' ) === mode;
				button.classList.toggle( 'is-active', active );
				button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			} );

			try {
				window.localStorage.setItem( 'plainmarkReaderContext', mode );
			} catch ( error ) {
				// Storage can be unavailable in privacy modes.
			}
		}

		controls.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-context-mode]' );
			if ( button ) {
				setMode( button.getAttribute( 'data-context-mode' ) );
			}
		} );

		var initial = 'all';
		try {
			initial = window.localStorage.getItem( 'plainmarkReaderContext' ) || 'all';
		} catch ( error ) {
			initial = 'all';
		}
		setMode( initial );
	}

	function initCodeTabs() {
		document.querySelectorAll( '[data-code-tabs]' ).forEach( function ( group ) {
			var tabs = Array.prototype.slice.call( group.querySelectorAll( '[data-code-tab]' ) );
			var panels = Array.prototype.slice.call( group.querySelectorAll( '[data-code-panel]' ) );

			function activate( index, focus ) {
				tabs.forEach( function ( tab, tabIndex ) {
					var active = tabIndex === index;
					tab.classList.toggle( 'is-active', active );
					tab.setAttribute( 'aria-selected', active ? 'true' : 'false' );
					tab.tabIndex = active ? 0 : -1;
				} );
				panels.forEach( function ( panel, panelIndex ) {
					panel.classList.toggle( 'is-active', panelIndex === index );
					panel.hidden = panelIndex !== index;
				} );
				if ( focus ) {
					tabs[ index ].focus();
				}
			}

			tabs.forEach( function ( tab, index ) {
				tab.addEventListener( 'click', function () {
					activate( index, false );
				} );
				tab.addEventListener( 'keydown', function ( event ) {
					if ( event.key === 'ArrowRight' || event.key === 'ArrowLeft' ) {
						event.preventDefault();
						var direction = event.key === 'ArrowRight' ? 1 : -1;
						activate( ( index + direction + tabs.length ) % tabs.length, true );
					}
				} );
			} );

			activate( 0, false );
		} );
	}

	function initKnowledgeMap() {
		var container = document.querySelector( '[data-knowledge-map]' );
		if ( ! container ) {
			return;
		}

		var svg = container.querySelector( 'svg' );
		var empty = container.querySelector( '.knowledge-map__empty' );
		var input = document.querySelector( '[data-knowledge-search]' );
		var data;

		try {
			data = JSON.parse( container.getAttribute( 'data-graph' ) || '{}' );
		} catch ( error ) {
			return;
		}

		var allNodes = data.nodes || [];
		var allLinks = data.links || [];

		function render( query ) {
			var keyword = ( query || '' ).trim().toLowerCase();
			var nodes = allNodes.filter( function ( node ) {
				var haystack = [ node.title ].concat( node.terms || [] ).join( ' ' ).toLowerCase();
				return ! keyword || haystack.indexOf( keyword ) !== -1;
			} );
			var nodeIds = {};
			nodes.forEach( function ( node ) { nodeIds[ node.id ] = true; } );
			var links = allLinks.filter( function ( link ) {
				return nodeIds[ link.source ] && nodeIds[ link.target ];
			} );

			svg.innerHTML = '';
			empty.hidden = nodes.length > 0;
			if ( ! nodes.length ) {
				return;
			}

			var width = Math.max( container.clientWidth, 640 );
			var height = Math.max( Math.min( 760, nodes.length * 34 ), 480 );
			svg.setAttribute( 'viewBox', '0 0 ' + width + ' ' + height );

			var centerX = width / 2;
			var centerY = height / 2;
			var radius = Math.max( 140, Math.min( width, height ) * 0.36 );
			var positions = {};

			nodes.forEach( function ( node, index ) {
				var angle = ( Math.PI * 2 * index ) / nodes.length - Math.PI / 2;
				var ring = 0.62 + ( index % 3 ) * 0.18;
				positions[ node.id ] = {
					x: centerX + Math.cos( angle ) * radius * ring,
					y: centerY + Math.sin( angle ) * radius * ring,
				};
			} );

			links.forEach( function ( link ) {
				var source = positions[ link.source ];
				var target = positions[ link.target ];
				if ( ! source || ! target ) { return; }
				var line = document.createElementNS( 'http://www.w3.org/2000/svg', 'line' );
				line.setAttribute( 'x1', source.x );
				line.setAttribute( 'y1', source.y );
				line.setAttribute( 'x2', target.x );
				line.setAttribute( 'y2', target.y );
				line.setAttribute( 'class', 'knowledge-map__link' );
				svg.appendChild( line );
			} );

			nodes.forEach( function ( node ) {
				var position = positions[ node.id ];
				var link = document.createElementNS( 'http://www.w3.org/2000/svg', 'a' );
				link.setAttribute( 'href', node.url );
				link.setAttribute( 'class', 'knowledge-map__node is-' + node.type );

				var circle = document.createElementNS( 'http://www.w3.org/2000/svg', 'circle' );
				circle.setAttribute( 'cx', position.x );
				circle.setAttribute( 'cy', position.y );
				circle.setAttribute( 'r', node.type === 'portfolio' ? 11 : 8 );

				var title = document.createElementNS( 'http://www.w3.org/2000/svg', 'title' );
				title.textContent = node.title;
				circle.appendChild( title );
				link.appendChild( circle );

				var text = document.createElementNS( 'http://www.w3.org/2000/svg', 'text' );
				text.setAttribute( 'x', position.x + 14 );
				text.setAttribute( 'y', position.y + 4 );
				text.textContent = node.title.length > 18 ? node.title.slice( 0, 18 ) + '…' : node.title;
				link.appendChild( text );
				svg.appendChild( link );
			} );
		}

		if ( input ) {
			input.addEventListener( 'input', function () { render( input.value ); } );
		}
		window.addEventListener( 'resize', function () { render( input ? input.value : '' ); } );
		render( '' );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		initReaderContexts();
		initCodeTabs();
		initKnowledgeMap();
	} );
} )();
