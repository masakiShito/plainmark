/**
 * Alert block registration.
 *
 * @package plainmark
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';
import './editor.scss';

registerBlockType( metadata.name, {
	edit: Edit,
} );
