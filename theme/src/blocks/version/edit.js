/**
 * Version badge block edit component.
 *
 * @package plainmark
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

/**
 * Version badge block edit function.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Set attributes function.
 * @return {Element} Block edit element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { label, version } = attributes;
	const blockProps = useBlockProps( {
		className: 'version-badge',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Version Badge Settings', 'plainmark' ) }>
					<TextControl
						label={ __( 'Label', 'plainmark' ) }
						value={ label }
						onChange={ ( newLabel ) => setAttributes( { label: newLabel } ) }
						placeholder={ __( 'WordPress', 'plainmark' ) }
						help={ __( 'Optional label before version number.', 'plainmark' ) }
					/>
					<TextControl
						label={ __( 'Version', 'plainmark' ) }
						value={ version }
						onChange={ ( newVersion ) => setAttributes( { version: newVersion } ) }
						placeholder={ __( '6.4', 'plainmark' ) }
					/>
				</PanelBody>
			</InspectorControls>

			<span { ...blockProps }>
				{ label && (
					<span className="version-badge__label">{ label }</span>
				) }
				<span className="version-badge__value">
					{ version || __( '0.0.0', 'plainmark' ) }
				</span>
			</span>
		</>
	);
}
