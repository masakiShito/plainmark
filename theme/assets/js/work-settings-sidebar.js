( function ( wp ) {
	if ( ! wp || ! wp.plugins || ! wp.editPost || ! wp.components || ! wp.data || ! wp.element || ! wp.i18n ) {
		return;
	}

	var registerPlugin = wp.plugins.registerPlugin;
	var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var SelectControl = wp.components.SelectControl;
	var Notice = wp.components.Notice;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var createElement = wp.element.createElement;
	var Fragment = wp.element.Fragment;

	var fields = [
		{ key: 'work_summary', label: '概要', type: 'textarea', help: '一覧と詳細のリード文に表示されます。' },
		{ key: 'work_problem', label: '課題', type: 'textarea' },
		{ key: 'work_solution', label: '解決方法', type: 'textarea' },
		{ key: 'work_architecture', label: '設計・構成', type: 'textarea' },
		{ key: 'work_features', label: '主な機能', type: 'textarea' },
		{ key: 'work_learnings', label: '学び・工夫', type: 'textarea' },
		{ key: 'work_next_steps', label: '今後の改善', type: 'textarea' },
		{ key: 'work_role', label: '担当・役割', type: 'text' },
		{ key: 'work_period', label: '制作時期', type: 'text' },
		{ key: 'work_github_url', label: 'GitHub URL', type: 'url' },
		{ key: 'work_demo_url', label: 'Demo URL（任意）', type: 'url' },
	];

	function WorkSettingsSidebar() {
		var postType = useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		}, [] );

		var meta = useSelect( function ( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		}, [] );

		var posts = useSelect( function ( select ) {
			return select( 'core' ).getEntityRecords( 'postType', 'post', {
				per_page: 100,
				orderby: 'title',
				order: 'asc',
				_fields: 'id,title',
			} ) || [];
		}, [] );

		var editPost = useDispatch( 'core/editor' ).editPost;

		if ( postType !== 'portfolio' ) {
			return null;
		}

		function updateMeta( key, value ) {
			var nextMeta = {};
			Object.keys( meta || {} ).forEach( function ( metaKey ) {
				nextMeta[ metaKey ] = meta[ metaKey ];
			} );
			nextMeta[ key ] = value;
			editPost( { meta: nextMeta } );
		}

		var postOptions = posts.map( function ( post ) {
			return {
				label: post.title && post.title.rendered ? post.title.rendered : '#' + post.id,
				value: String( post.id ),
			};
		} );
		var selectedPosts = Array.isArray( meta._plainmark_related_posts )
			? meta._plainmark_related_posts.map( String )
			: [];

		return createElement(
			Fragment,
			null,
			createElement(
				PluginDocumentSettingPanel,
				{
					name: 'plainmark-work-settings',
					title: 'ケーススタディ設定',
					className: 'plainmark-work-settings-panel',
				},
				createElement(
					Fragment,
					null,
					createElement(
						'p',
						{ className: 'plainmark-work-settings-panel__description' },
						'Works詳細ページに表示する内容を入力します。未入力の項目は表示されません。'
					),
					fields.map( function ( field ) {
						var value = meta && meta[ field.key ] ? meta[ field.key ] : '';
						var Component = field.type === 'textarea' ? TextareaControl : TextControl;

						return createElement( Component, {
							key: field.key,
							label: field.label,
							type: field.type === 'url' ? 'url' : 'text',
							value: value,
							help: field.help || undefined,
							rows: field.type === 'textarea' ? 5 : undefined,
							onChange: function ( nextValue ) {
								updateMeta( field.key, nextValue );
							},
						} );
					} )
				)
			),
			createElement(
				PluginDocumentSettingPanel,
				{
					name: 'plainmark-related-posts',
					title: '関連記事',
					className: 'plainmark-related-posts-panel',
				},
				createElement( SelectControl, {
					label: 'この制作物に関連する記事',
					multiple: true,
					value: selectedPosts,
					options: postOptions,
					help: '複数選択できます。Worksと記事の双方に関連コンテンツが表示されます。',
					onChange: function ( values ) {
						var normalized = Array.isArray( values ) ? values.map( function ( value ) { return parseInt( value, 10 ); } ) : [];
						updateMeta( '_plainmark_related_posts', normalized.filter( Boolean ) );
					},
				} )
			),
			meta._plainmark_github_path ? createElement(
				PluginDocumentSettingPanel,
				{
					name: 'plainmark-work-github-source',
					title: 'GitHub管理',
					className: 'plainmark-work-github-source-panel',
				},
				createElement( Notice, { status: 'success', isDismissible: false }, 'このWorksはGitHubから同期されています。' ),
				createElement( 'p', null, createElement( 'strong', null, 'Path: ' ), meta._plainmark_github_path ),
				meta._plainmark_github_synced_at ? createElement( 'p', null, createElement( 'strong', null, 'Synced: ' ), meta._plainmark_github_synced_at ) : null
			) : null
		);
	}

	registerPlugin( 'plainmark-work-settings-sidebar', {
		render: WorkSettingsSidebar,
	} );
} )( window.wp );
