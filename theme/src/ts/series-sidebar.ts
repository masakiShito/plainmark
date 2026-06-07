/**
 * Series Settings Sidebar Panel for Block Editor
 *
 * @package plainmark
 * @since 0.1.0
 */

declare const wp: {
	plugins: {
		registerPlugin: (name: string, options: Record<string, unknown>) => void;
	};
	editPost: {
		PluginDocumentSettingPanel: React.ComponentType<{
			name: string;
			title: string;
			children: React.ReactNode;
		}>;
	};
	components: {
		TextControl: React.ComponentType<{
			label: string;
			value: string;
			onChange: (value: string) => void;
			help?: string;
			placeholder?: string;
			type?: string;
		}>;
		ComboboxControl: React.ComponentType<{
			label: string;
			value: string;
			onChange: (value: string | null) => void;
			options: Array<{ value: string; label: string }>;
			onFilterValueChange?: (value: string) => void;
			help?: string;
			allowReset?: boolean;
		}>;
		Button: React.ComponentType<{
			variant?: string;
			isDestructive?: boolean;
			onClick: () => void;
			children: React.ReactNode;
			size?: string;
		}>;
		Spinner: React.ComponentType<Record<string, never>>;
		Flex: React.ComponentType<{
			children: React.ReactNode;
			direction?: string;
			gap?: number;
		}>;
		FlexItem: React.ComponentType<{
			children: React.ReactNode;
		}>;
	};
	data: {
		useSelect: <T>(selector: (select: (store: string) => Record<string, unknown>) => T, deps?: unknown[]) => T;
		useDispatch: (store: string) => Record<string, (...args: unknown[]) => void>;
	};
	element: {
		createElement: typeof React.createElement;
		Fragment: typeof React.Fragment;
		useState: <T>(initial: T) => [T, (value: T) => void];
		useEffect: (effect: () => void | (() => void), deps?: unknown[]) => void;
	};
	i18n: {
		__: (text: string, domain?: string) => string;
	};
	apiFetch: <T>(options: { path: string }) => Promise<T>;
};

const { registerPlugin } = wp.plugins;
const { PluginDocumentSettingPanel } = wp.editPost;
const { TextControl, ComboboxControl, Button, Spinner, Flex, FlexItem } = wp.components;
const { useSelect, useDispatch } = wp.data;
const { createElement, Fragment, useState, useEffect } = wp.element;
const { __ } = wp.i18n;
const apiFetch = wp.apiFetch;

interface SeriesOption {
	value: string;
	label: string;
}

/**
 * Series Settings Panel Component
 */
function SeriesSettingsPanel(): JSX.Element {
	const [seriesOptions, setSeriesOptions] = useState<SeriesOption[]>([]);
	const [isLoading, setIsLoading] = useState(true);
	const [inputValue, setInputValue] = useState('');

	const { seriesName, seriesOrder } = useSelect((select) => {
		const editor = select('core/editor') as { getEditedPostAttribute: (attr: string) => Record<string, unknown> };
		const meta = editor.getEditedPostAttribute('meta') || {};
		return {
			seriesName: (meta._plainmark_series_name as string) || '',
			seriesOrder: (meta._plainmark_series_order as number) || '',
		};
	}, []);

	const { editPost } = useDispatch('core/editor');

	// Fetch existing series names
	useEffect(() => {
		setIsLoading(true);
		apiFetch<string[]>({ path: '/plainmark/v1/series' })
			.then((names) => {
				const options = names.map((name) => ({
					value: name,
					label: name,
				}));
				setSeriesOptions(options);
				setIsLoading(false);
			})
			.catch(() => {
				setSeriesOptions([]);
				setIsLoading(false);
			});
	}, []);

	const updateSeriesName = (value: string | null): void => {
		editPost({
			meta: {
				_plainmark_series_name: value || '',
			},
		});
	};

	const updateSeriesOrder = (value: string): void => {
		const numValue = value === '' ? 0 : parseInt(value, 10);
		editPost({
			meta: {
				_plainmark_series_order: isNaN(numValue) ? 0 : numValue,
			},
		});
	};

	const handleFilterValueChange = (value: string): void => {
		setInputValue(value);
	};

	const createNewSeries = (): void => {
		if (inputValue.trim()) {
			updateSeriesName(inputValue.trim());
			// Add to options if not exists
			if (!seriesOptions.find((opt) => opt.value === inputValue.trim())) {
				setSeriesOptions([...seriesOptions, { value: inputValue.trim(), label: inputValue.trim() }]);
			}
			setInputValue('');
		}
	};

	const clearSeries = (): void => {
		updateSeriesName('');
		editPost({
			meta: {
				_plainmark_series_order: 0,
			},
		});
	};

	if (isLoading) {
		return createElement(
			'div',
			{ style: { textAlign: 'center', padding: '20px' } },
			createElement(Spinner, null)
		);
	}

	// Build options including "new series" option if user is typing something not in the list
	const displayOptions = [...seriesOptions];
	if (inputValue && !seriesOptions.find((opt) => opt.value.toLowerCase() === inputValue.toLowerCase())) {
		displayOptions.unshift({
			value: `__new__${inputValue}`,
			label: `「${inputValue}」を新規作成`,
		});
	}

	return createElement(
		Fragment,
		null,
		createElement(ComboboxControl, {
			label: __('シリーズ名', 'plainmark'),
			value: seriesName,
			onChange: (value: string | null) => {
				if (value && value.startsWith('__new__')) {
					// Create new series
					const newName = value.replace('__new__', '');
					updateSeriesName(newName);
					if (!seriesOptions.find((opt) => opt.value === newName)) {
						setSeriesOptions([...seriesOptions, { value: newName, label: newName }]);
					}
				} else {
					updateSeriesName(value);
				}
			},
			options: displayOptions,
			onFilterValueChange: handleFilterValueChange,
			help: seriesOptions.length > 0
				? __('既存のシリーズから選択、または新しいシリーズ名を入力', 'plainmark')
				: __('シリーズ名を入力して作成', 'plainmark'),
			allowReset: true,
		}),
		seriesName && createElement(TextControl, {
			label: __('Part番号', 'plainmark'),
			value: seriesOrder ? String(seriesOrder) : '',
			onChange: updateSeriesOrder,
			placeholder: '1',
			type: 'number',
			help: __('シリーズ内での順番（1, 2, 3...）', 'plainmark'),
		}),
		seriesName && createElement(
			'div',
			{ style: { marginTop: '12px' } },
			createElement(Button, {
				variant: 'secondary',
				isDestructive: true,
				onClick: clearSeries,
				size: 'small',
			}, __('シリーズから外す', 'plainmark'))
		)
	);
}

/**
 * Register the plugin
 */
registerPlugin('plainmark-series-settings', {
	render: () =>
		createElement(
			PluginDocumentSettingPanel,
			{
				name: 'plainmark-series-settings',
				title: __('シリーズ設定', 'plainmark'),
			},
			createElement(SeriesSettingsPanel, null)
		),
	icon: 'list-view',
});
