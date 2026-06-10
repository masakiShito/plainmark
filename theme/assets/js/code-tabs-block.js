( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.blockEditor || ! wp.components || ! wp.element || ! wp.i18n ) {
		return;
	}

	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var Button = wp.components.Button;
	var Notice = wp.components.Notice;
	var createElement = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;

	function normalizeFiles( files ) {
		return Array.isArray( files ) && files.length
			? files
			: [ { file: 'example.js', language: 'javascript', code: '' } ];
	}

	registerBlockType( 'plainmark/code-tabs', {
		apiVersion: 2,
		title: __( 'コードタブ', 'plainmark' ),
		description: __( '複数ファイルのコードをタブで切り替えて表示します。', 'plainmark' ),
		icon: 'editor-code',
		category: 'plainmark',
		attributes: {
			files: {
				type: 'array',
				default: [ { file: 'example.js', language: 'javascript', code: '' } ],
			},
		},
		edit: function ( props ) {
			var files = normalizeFiles( props.attributes.files );
			var blockProps = useBlockProps( { className: 'plainmark-code-tabs-editor' } );

			function updateFile( index, key, value ) {
				var next = files.map( function ( item, itemIndex ) {
					if ( itemIndex !== index ) {
						return item;
					}
					return Object.assign( {}, item, ( function () {
						var patch = {};
						patch[ key ] = value;
						return patch;
					} )() );
				} );
				props.setAttributes( { files: next } );
			}

			function removeFile( index ) {
				if ( files.length === 1 ) {
					props.setAttributes( { files: [ { file: 'example.js', language: 'javascript', code: '' } ] } );
					return;
				}
				props.setAttributes( { files: files.filter( function ( item, itemIndex ) { return itemIndex !== index; } ) } );
			}

			return createElement(
				Fragment,
				null,
				createElement(
					InspectorControls,
					null,
					createElement(
						PanelBody,
						{ title: __( 'コードタブ設定', 'plainmark' ), initialOpen: true },
						createElement( Notice, { status: 'info', isDismissible: false }, __( 'ファイル名・言語・コードを入力します。', 'plainmark' ) ),
						files.map( function ( item, index ) {
							return createElement(
								'div',
								{ key: index, className: 'plainmark-code-tabs-editor__settings' },
								createElement( TextControl, {
									label: __( 'ファイル名', 'plainmark' ),
									value: item.file || '',
									onChange: function ( value ) { updateFile( index, 'file', value ); },
								} ),
								createElement( TextControl, {
									label: __( '言語', 'plainmark' ),
									value: item.language || '',
									onChange: function ( value ) { updateFile( index, 'language', value ); },
								} ),
								createElement( Button, { isDestructive: true, isSmall: true, onClick: function () { removeFile( index ); } }, __( '削除', 'plainmark' ) )
							);
						} ),
						createElement( Button, {
							variant: 'secondary',
							onClick: function () {
								props.setAttributes( { files: files.concat( [ { file: 'new-file.txt', language: 'text', code: '' } ] ) } );
							},
						}, __( 'ファイルを追加', 'plainmark' ) )
					)
				),
				createElement(
					'div',
					blockProps,
					createElement( 'div', { className: 'plainmark-code-tabs-editor__tabs' }, files.map( function ( item, index ) {
						return createElement( 'span', { key: index, className: index === 0 ? 'is-active' : '' }, item.file || __( '名称未設定', 'plainmark' ) );
					} ) ),
					files.map( function ( item, index ) {
						return createElement(
							'div',
							{ key: index, className: 'plainmark-code-tabs-editor__file' },
							createElement( 'strong', null, item.file || __( '名称未設定', 'plainmark' ) ),
							createElement( TextareaControl, {
								label: __( 'コード', 'plainmark' ),
								value: item.code || '',
								rows: 10,
								onChange: function ( value ) { updateFile( index, 'code', value ); },
							} )
						);
					} )
				)
			);
		},
		save: function ( props ) {
			var files = normalizeFiles( props.attributes.files );
			var blockProps = useBlockProps.save( { className: 'code-tabs', 'data-code-tabs': '' } );

			return createElement(
				'div',
				blockProps,
				createElement(
					'div',
					{ className: 'code-tabs__tablist', role: 'tablist' },
					files.map( function ( item, index ) {
						return createElement( 'button', {
							key: index,
							className: 'code-tabs__tab' + ( index === 0 ? ' is-active' : '' ),
							type: 'button',
							role: 'tab',
							'aria-selected': index === 0 ? 'true' : 'false',
							'data-code-tab': String( index ),
						}, item.file || __( 'file', 'plainmark' ) );
					} )
				),
				createElement(
					'div',
					{ className: 'code-tabs__panels' },
					files.map( function ( item, index ) {
						return createElement(
							'div',
							{
								key: index,
								className: 'code-tabs__panel' + ( index === 0 ? ' is-active' : '' ),
								role: 'tabpanel',
								'data-code-panel': String( index ),
								hidden: index !== 0,
							},
							createElement( 'pre', null, createElement( 'code', { className: item.language ? 'language-' + item.language : '' }, item.code || '' ) )
						);
					} )
				)
			);
		},
	} );
} )( window.wp );
