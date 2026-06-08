( function ( wp ) {
	if ( ! wp || ! wp.plugins || ! wp.editPost || ! wp.components || ! wp.data || ! wp.element || ! wp.i18n ) {
		return;
	}

	var registerPlugin = wp.plugins.registerPlugin;
	var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var PanelBody = wp.components.PanelBody;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var createElement = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;

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

		return createElement(
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
		);
	}

	registerPlugin( 'plainmark-work-settings-sidebar', {
		render: WorkSettingsSidebar,
	} );
} )( window.wp );
