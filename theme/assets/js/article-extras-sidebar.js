( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.editPost || ! wp.components || ! wp.data || ! wp.element ) {
		return;
	}

	var registerPlugin = wp.plugins.registerPlugin;
	var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
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
		var works = useSelect( function ( select ) {
			return select( 'core' ).getEntityRecords( 'postType', 'portfolio', {
				per_page: 100,
				orderby: 'title',
				order: 'asc',
				_fields: 'id,title',
			} ) || [];
		}, [] );
		var editPost = useDispatch( 'core/editor' ).editPost;

		if ( postType !== 'post' ) {
			return null;
		}

		function updateMeta( key, value ) {
			var nextMeta = Object.assign( {}, meta );
			nextMeta[ key ] = value;
			editPost( { meta: nextMeta } );
		}

		var workOptions = works.map( function ( work ) {
			return {
				label: work.title && work.title.rendered ? work.title.rendered : '#' + work.id,
				value: String( work.id ),
			};
		} );
		var selectedWorks = Array.isArray( meta._plainmark_related_works )
			? meta._plainmark_related_works.map( String )
			: [];

		return createElement(
			Fragment,
			null,
			createElement(
				PluginDocumentSettingPanel,
				{ name: 'plainmark-verification', title: '動作確認', className: 'plainmark-verification-panel' },
				createElement( SelectControl, {
					label: '検証状態',
					value: meta._plainmark_verified_status || 'unverified',
					options: [
						{ label: '未検証', value: 'unverified' },
						{ label: '動作確認済み', value: 'verified' },
						{ label: '非推奨', value: 'deprecated' },
					],
					onChange: function ( value ) { updateMeta( '_plainmark_verified_status', value ); },
				} ),
				createElement( TextControl, {
					label: '最終動作確認日',
					type: 'date',
					value: meta._plainmark_verified_date || '',
					onChange: function ( value ) { updateMeta( '_plainmark_verified_date', value ); },
				} ),
				createElement( TextareaControl, {
					label: '確認環境',
					value: meta._plainmark_verified_env || '',
					rows: 4,
					placeholder: 'Node.js 24\nTypeScript 5.9\nmacOS',
					onChange: function ( value ) { updateMeta( '_plainmark_verified_env', value ); },
				} ),
				createElement( TextControl, {
					label: '次回レビュー日',
					type: 'date',
					value: meta._plainmark_review_date || '',
					help: '期限を過ぎると「再確認が必要」と表示されます。',
					onChange: function ( value ) { updateMeta( '_plainmark_review_date', value ); },
				} )
			),
			createElement(
				PluginDocumentSettingPanel,
				{ name: 'plainmark-related-works', title: '関連Works', className: 'plainmark-related-works-panel' },
				createElement( SelectControl, {
					label: 'この知識を使ったWorks',
					multiple: true,
					value: selectedWorks,
					options: workOptions,
					help: '複数選択できます。記事とWorksの双方に関連コンテンツが表示されます。',
					onChange: function ( values ) {
						var normalized = Array.isArray( values ) ? values.map( function ( value ) { return parseInt( value, 10 ); } ) : [];
						updateMeta( '_plainmark_related_works', normalized.filter( Boolean ) );
					},
				} )
			),
			createElement(
				PluginDocumentSettingPanel,
				{ name: 'plainmark-article-extras', title: '変更履歴', className: 'plainmark-article-extras-panel' },
				createElement( Notice, { status: 'info', isDismissible: false }, '1行につき「YYYY-MM-DD | 変更内容」の形式で入力してください。' ),
				createElement( TextareaControl, {
					label: 'Changelog',
					value: meta._plainmark_changelog || '',
					rows: 8,
					placeholder: '2026-06-10 | コード例を更新\n2026-06-08 | 初版公開',
					onChange: function ( value ) { updateMeta( '_plainmark_changelog', value ); },
				} )
			),
			meta._plainmark_github_path ? createElement(
				PluginDocumentSettingPanel,
				{ name: 'plainmark-github-source', title: 'GitHub管理', className: 'plainmark-github-source-panel' },
				createElement( Notice, { status: 'success', isDismissible: false }, 'この投稿はGitHubから同期されています。' ),
				createElement( 'p', null, createElement( 'strong', null, 'Path: ' ), meta._plainmark_github_path ),
				meta._plainmark_github_synced_at ? createElement( 'p', null, createElement( 'strong', null, 'Synced: ' ), meta._plainmark_github_synced_at ) : null
			) : null
		);
	}

	registerPlugin( 'plainmark-article-extras-sidebar', { render: ArticleExtrasPanel } );
} )( window.wp );
