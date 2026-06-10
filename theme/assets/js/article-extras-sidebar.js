( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.editPost || ! wp.components || ! wp.data || ! wp.element ) {
		return;
	}

	var registerPlugin = wp.plugins.registerPlugin;
	var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
	var TextareaControl = wp.components.TextareaControl;
	var Notice = wp.components.Notice;
	var createElement = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;

	function ArticleExtrasPanel() {
		var postType = useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		}, [] );
		var meta = useSelect( function ( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		}, [] );
		var editPost = useDispatch( 'core/editor' ).editPost;

		if ( postType !== 'post' ) {
			return null;
		}

		return createElement(
			PluginDocumentSettingPanel,
			{
				name: 'plainmark-article-extras',
				title: '変更履歴',
				className: 'plainmark-article-extras-panel',
			},
			createElement(
				Fragment,
				null,
				createElement(
					Notice,
					{ status: 'info', isDismissible: false },
					'1行につき「YYYY-MM-DD | 変更内容」の形式で入力してください。'
				),
				createElement( TextareaControl, {
					label: 'Changelog',
					value: meta._plainmark_changelog || '',
					rows: 8,
					placeholder: '2026-06-10 | コード例を更新\n2026-06-08 | 初版公開',
					onChange: function ( value ) {
						var nextMeta = Object.assign( {}, meta, { _plainmark_changelog: value } );
						editPost( { meta: nextMeta } );
					},
				} )
			)
		);
	}

	registerPlugin( 'plainmark-article-extras-sidebar', {
		render: ArticleExtrasPanel,
	} );
} )( window.wp );
