/**
 * Alert block edit component.
 *
 * @package plainmark
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';

/**
 * Alert type configurations with labels and icons.
 */
const ALERT_TYPES = {
	note: {
		label: __( 'Note', 'plainmark' ),
		icon: (
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
				<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
				<polyline points="14 2 14 8 20 8"/>
				<line x1="16" y1="13" x2="8" y2="13"/>
				<line x1="16" y1="17" x2="8" y2="17"/>
				<polyline points="10 9 9 9 8 9"/>
			</svg>
		),
	},
	info: {
		label: __( 'Info', 'plainmark' ),
		icon: (
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
				<circle cx="12" cy="12" r="10"/>
				<line x1="12" y1="16" x2="12" y2="12"/>
				<line x1="12" y1="8" x2="12.01" y2="8"/>
			</svg>
		),
	},
	tip: {
		label: __( 'Tip', 'plainmark' ),
		icon: (
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
				<path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
				<circle cx="12" cy="12" r="10"/>
				<line x1="12" y1="17" x2="12.01" y2="17"/>
			</svg>
		),
	},
	warning: {
		label: __( 'Warning', 'plainmark' ),
		icon: (
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
				<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
				<line x1="12" y1="9" x2="12" y2="13"/>
				<line x1="12" y1="17" x2="12.01" y2="17"/>
			</svg>
		),
	},
	danger: {
		label: __( 'Danger', 'plainmark' ),
		icon: (
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
				<circle cx="12" cy="12" r="10"/>
				<line x1="15" y1="9" x2="9" y2="15"/>
				<line x1="9" y1="9" x2="15" y2="15"/>
			</svg>
		),
	},
};

/**
 * Alert block edit function.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Set attributes function.
 * @return {Element} Block edit element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { type, title, content } = attributes;
	const blockProps = useBlockProps( {
		className: `alert-block alert-block--${ type }`,
	} );

	const alertType = ALERT_TYPES[ type ] || ALERT_TYPES.note;
	const displayTitle = title || alertType.label;

	const typeOptions = Object.entries( ALERT_TYPES ).map( ( [ value, config ] ) => ( {
		value,
		label: config.label,
	} ) );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Alert Settings', 'plainmark' ) }>
					<SelectControl
						label={ __( 'Type', 'plainmark' ) }
						value={ type }
						options={ typeOptions }
						onChange={ ( newType ) => setAttributes( { type: newType } ) }
					/>
					<TextControl
						label={ __( 'Custom Title', 'plainmark' ) }
						value={ title }
						onChange={ ( newTitle ) => setAttributes( { title: newTitle } ) }
						placeholder={ alertType.label }
						help={ __( 'Leave empty to use default type label.', 'plainmark' ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps } role="alert">
				<div className="alert-block__header">
					<span className="alert-block__icon">{ alertType.icon }</span>
					<span className="alert-block__title">{ displayTitle }</span>
				</div>
				<RichText
					tagName="div"
					className="alert-block__content"
					value={ content }
					onChange={ ( newContent ) => setAttributes( { content: newContent } ) }
					placeholder={ __( 'Enter alert content…', 'plainmark' ) }
					allowedFormats={ [ 'core/bold', 'core/italic', 'core/link', 'core/code' ] }
				/>
			</div>
		</>
	);
}
