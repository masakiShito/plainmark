( function () {
	'use strict';

	function initPlaygrounds() {
		document.querySelectorAll( '[data-code-playground]' ).forEach( function ( playground ) {
			var editor = playground.querySelector( '.code-playground__editor' );
			var button = playground.querySelector( '[data-playground-run]' );
			var iframe = playground.querySelector( 'iframe' );
			var log = playground.querySelector( 'pre' );
			var language = playground.getAttribute( 'data-language' ) || 'javascript';

			function buildDocument( code ) {
				if ( language === 'html' ) {
					return code;
				}

				return '<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:ui-monospace,Menlo,monospace;padding:16px;line-height:1.6}button{font:inherit}</style></head><body><script>var log=function(){parent.postMessage({type:"plainmark-playground-log",message:Array.from(arguments).join(" ")},"*");};console.log=log;console.error=log;try{' + code.replace( /<\/script/gi, '<\\/script' ) + '}catch(error){log(error && error.stack ? error.stack : String(error));}<\/script></body></html>';
			}

			function run() {
				if ( ! editor || ! iframe ) {
					return;
				}
				if ( log ) {
					log.textContent = '';
				}
				iframe.srcdoc = buildDocument( editor.value );
			}

			if ( button ) {
				button.addEventListener( 'click', run );
			}

			run();
		} );

		window.addEventListener( 'message', function ( event ) {
			if ( event.origin !== 'null' ) {
				return;
			}
			if ( ! event.data || event.data.type !== 'plainmark-playground-log' ) {
				return;
			}
			var playgrounds = document.querySelectorAll( '[data-code-playground]' );
			for ( var i = 0; i < playgrounds.length; i++ ) {
				var iframe = playgrounds[ i ].querySelector( 'iframe' );
				if ( iframe && iframe.contentWindow === event.source ) {
					var pre = playgrounds[ i ].querySelector( 'pre' );
					if ( pre ) {
						pre.textContent += event.data.message + '\n';
					}
					break;
				}
			}
		} );
	}

	function initPersonaSwitchers() {
		var blocks = Array.prototype.slice.call( document.querySelectorAll( '[data-reader-persona]' ) );
		if ( ! blocks.length ) {
			return;
		}

		var levels = [ 'all' ];
		var frameworks = [ 'all' ];

		blocks.forEach( function ( block ) {
			var level = block.getAttribute( 'data-persona-level' ) || 'all';
			var framework = block.getAttribute( 'data-persona-framework' ) || 'all';
			if ( levels.indexOf( level ) === -1 ) {
				levels.push( level );
			}
			if ( frameworks.indexOf( framework ) === -1 ) {
				frameworks.push( framework );
			}
		} );

		var controls = document.createElement( 'div' );
		controls.className = 'reader-persona-switcher';
		controls.innerHTML = '<label>Level <select data-persona-level-select></select></label><label>Framework <select data-persona-framework-select></select></label>';

		var contentContainer = document.querySelector( '.entry-content' ) || document.querySelector( '.article__body' );
		if ( contentContainer ) {
			contentContainer.insertBefore( controls, contentContainer.firstChild );
		} else {
			blocks[ 0 ].parentNode.insertBefore( controls, blocks[ 0 ] );
		}

		var levelSelect = controls.querySelector( '[data-persona-level-select]' );
		var frameworkSelect = controls.querySelector( '[data-persona-framework-select]' );

		levels.forEach( function ( level ) {
			var option = document.createElement( 'option' );
			option.value = level;
			option.textContent = level;
			levelSelect.appendChild( option );
		} );

		frameworks.forEach( function ( framework ) {
			var option = document.createElement( 'option' );
			option.value = framework;
			option.textContent = framework;
			frameworkSelect.appendChild( option );
		} );

		function apply() {
			var level = levelSelect.value;
			var framework = frameworkSelect.value;

			blocks.forEach( function ( block ) {
				var blockLevel = block.getAttribute( 'data-persona-level' ) || 'all';
				var blockFramework = block.getAttribute( 'data-persona-framework' ) || 'all';
				var levelMatch = level === 'all' || blockLevel === 'all' || blockLevel === level;
				var frameworkMatch = framework === 'all' || blockFramework === 'all' || blockFramework === framework;
				block.hidden = ! ( levelMatch && frameworkMatch );
			} );

			try {
				localStorage.setItem( 'plainmarkPersonaLevel', level );
				localStorage.setItem( 'plainmarkPersonaFramework', framework );
			} catch ( error ) {}
		}

		try {
			levelSelect.value = localStorage.getItem( 'plainmarkPersonaLevel' ) || 'all';
			frameworkSelect.value = localStorage.getItem( 'plainmarkPersonaFramework' ) || 'all';
		} catch ( error ) {}

		levelSelect.addEventListener( 'change', apply );
		frameworkSelect.addEventListener( 'change', apply );
		apply();
	}

	function initTechnologyMap() {
		var container = document.querySelector( '[data-technology-map]' );
		if ( ! container ) {
			return;
		}

		var svg = container.querySelector( 'svg' );
		var raw = container.getAttribute( 'data-graph' ) || '{}';
		var graph;
		try {
			graph = JSON.parse( raw );
		} catch ( error ) {
			return;
		}

		var nodes = graph.nodes || [];
		var links = graph.links || [];
		var width = Math.max( container.clientWidth, 720 );
		var height = Math.max( 520, Math.min( 900, 360 + nodes.length * 22 ) );
		var centerX = width / 2;
		var centerY = height / 2;
		var radius = Math.min( width, height ) * 0.34;
		var positions = {};

		svg.setAttribute( 'viewBox', '0 0 ' + width + ' ' + height );
		svg.innerHTML = '';

		nodes.forEach( function ( node, index ) {
			var angle = ( Math.PI * 2 * index ) / Math.max( 1, nodes.length ) - Math.PI / 2;
			positions[ node.id ] = {
				x: centerX + Math.cos( angle ) * radius,
				y: centerY + Math.sin( angle ) * radius,
			};
		} );

		links.forEach( function ( link ) {
			var source = positions[ link.source ];
			var target = positions[ link.target ];
			if ( ! source || ! target ) {
				return;
			}
			var line = document.createElementNS( 'http://www.w3.org/2000/svg', 'line' );
			line.setAttribute( 'x1', source.x );
			line.setAttribute( 'y1', source.y );
			line.setAttribute( 'x2', target.x );
			line.setAttribute( 'y2', target.y );
			line.setAttribute( 'stroke-width', String( Math.max( 1, Math.min( 8, link.weight || 1 ) ) ) );
			line.setAttribute( 'class', 'technology-map__link' );
			svg.appendChild( line );
		} );

		nodes.forEach( function ( node ) {
			var position = positions[ node.id ];
			var group = document.createElementNS( 'http://www.w3.org/2000/svg', 'a' );
			group.setAttribute( 'href', node.url );
			group.setAttribute( 'class', 'technology-map__node' );

			var circle = document.createElementNS( 'http://www.w3.org/2000/svg', 'circle' );
			circle.setAttribute( 'cx', position.x );
			circle.setAttribute( 'cy', position.y );
			circle.setAttribute( 'r', String( Math.max( 8, Math.min( 26, 7 + node.count * 2 ) ) ) );

			var text = document.createElementNS( 'http://www.w3.org/2000/svg', 'text' );
			text.setAttribute( 'x', position.x );
			text.setAttribute( 'y', position.y + 42 );
			text.setAttribute( 'text-anchor', 'middle' );
			text.textContent = node.label;

			var title = document.createElementNS( 'http://www.w3.org/2000/svg', 'title' );
			title.textContent = node.label + ' / ' + node.count + ' outputs';
			circle.appendChild( title );
			group.appendChild( circle );
			group.appendChild( text );
			svg.appendChild( group );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		initPlaygrounds();
		initPersonaSwitchers();
		initTechnologyMap();
	} );
} )();
