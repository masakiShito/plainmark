/**
 * Environment block edit component.
 *
 * @package plainmark
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	Button,
} from '@wordpress/components';

/**
 * Environment block edit function.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Set attributes function.
 * @return {Element} Block edit element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { title, items } = attributes;
	const blockProps = useBlockProps( {
		className: 'environment-block',
	} );

	const displayTitle = title || __( 'Environment', 'plainmark' );

	const updateItem = ( index, field, value ) => {
		const newItems = [ ...items ];
		newItems[ index ] = { ...newItems[ index ], [ field ]: value };
		setAttributes( { items: newItems } );
	};

	const addItem = () => {
		setAttributes( {
			items: [ ...items, { label: '', value: '' } ],
		} );
	};

	const removeItem = ( index ) => {
		const newItems = items.filter( ( _, i ) => i !== index );
		setAttributes( { items: newItems } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Environment Settings', 'plainmark' ) }>
					<TextControl
						label={ __( 'Title', 'plainmark' ) }
						value={ title }
						onChange={ ( newTitle ) => setAttributes( { title: newTitle } ) }
						placeholder={ __( 'Environment', 'plainmark' ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Items', 'plainmark' ) } initialOpen={ true }>
					{ items.map( ( item, index ) => (
						<div key={ index } className="environment-block-item-control">
							<TextControl
								label={ __( 'Label', 'plainmark' ) }
								value={ item.label }
								onChange={ ( value ) => updateItem( index, 'label', value ) }
								placeholder={ __( 'OS', 'plainmark' ) }
							/>
							<TextControl
								label={ __( 'Value', 'plainmark' ) }
								value={ item.value }
								onChange={ ( value ) => updateItem( index, 'value', value ) }
								placeholder={ __( 'macOS 14.0', 'plainmark' ) }
							/>
							<Button
								isDestructive
								variant="secondary"
								size="small"
								onClick={ () => removeItem( index ) }
							>
								{ __( 'Remove', 'plainmark' ) }
							</Button>
							<hr />
						</div>
					) ) }
					<Button
						variant="secondary"
						onClick={ addItem }
					>
						{ __( 'Add Item', 'plainmark' ) }
					</Button>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="environment-block__header">
					<svg className="environment-block__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
						<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
						<line x1="8" y1="21" x2="16" y2="21"/>
						<line x1="12" y1="17" x2="12" y2="21"/>
					</svg>
					<span className="environment-block__title">{ displayTitle }</span>
				</div>
				<div className="environment-block__body">
					{ items.length === 0 ? (
						<p className="environment-block__empty">
							{ __( 'Add items in the sidebar panel.', 'plainmark' ) }
						</p>
					) : (
						items.map( ( item, index ) => (
							<div key={ index } className="environment-block__item">
								<span className="environment-block__label">
									{ item.label || __( 'Label', 'plainmark' ) }
								</span>
								<span className="environment-block__value">
									{ item.value || __( 'Value', 'plainmark' ) }
								</span>
							</div>
						) )
					) }
				</div>
			</div>
		</>
	);
}
