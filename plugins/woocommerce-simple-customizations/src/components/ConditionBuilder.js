/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { SelectControl, TextControl, Button, Tooltip } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ConditionBuilder = ( { conditions, onChange } ) => {

	const addCondition = () => {
		const newConditions = [
			...conditions,
			{ type: 'cart_total', operator: '==', value: '' }
		];
		onChange( newConditions );
	};

	const updateCondition = ( index, field, value ) => {
		const newConditions = [ ...conditions ];
		newConditions[ index ][ field ] = value;
		onChange( newConditions );
	};

	const removeCondition = ( index ) => {
		const newConditions = conditions.filter( ( _, i ) => i !== index );
		onChange( newConditions );
	};

	const conditionTypes = [
		{ label: __( 'Cart Total', 'woocommerce-simple-customizations' ), value: 'cart_total' },
		{ label: __( 'Cart Subtotal', 'woocommerce-simple-customizations' ), value: 'cart_subtotal' },
		{ label: __( 'Item Count', 'woocommerce-simple-customizations' ), value: 'cart_item_count' },
		{ label: __( 'Cart Has Product (ID)', 'woocommerce-simple-customizations' ), value: 'cart_has_product' },
		{ label: __( 'Cart Has Category (ID)', 'woocommerce-simple-customizations' ), value: 'cart_has_category' },
		{ label: __( 'User Role', 'woocommerce-simple-customizations' ), value: 'user_role' },
	];

	const operators = [
		{ label: '==', value: '==' },
		{ label: '!=', value: '!=' },
		{ label: '>', value: '>' },
		{ label: '<', value: '<' },
		{ label: '>=', value: '>=' },
		{ label: '<=', value: '<=' },
	];

	return (
		<div className="wsc-condition-builder">
			{ conditions.map( ( condition, index ) => (
				<div key={ index } className="wsc-condition-row" style={ { display: 'flex', gap: '10px', marginBottom: '10px', alignItems: 'flex-end' } }>
					<SelectControl
						label={ __( 'Condition', 'woocommerce-simple-customizations' ) }
						value={ condition.type }
						options={ conditionTypes }
						onChange={ ( val ) => updateCondition( index, 'type', val ) }
					/>
					<SelectControl
						label={ __( 'Operator', 'woocommerce-simple-customizations' ) }
						value={ condition.operator }
						options={ operators }
						onChange={ ( val ) => updateCondition( index, 'operator', val ) }
					/>
					<TextControl
						label={ __( 'Value', 'woocommerce-simple-customizations' ) }
						value={ condition.value }
						onChange={ ( val ) => updateCondition( index, 'value', val ) }
					/>
					<Button 
						isDestructive 
						isSmall
						variant="secondary"
						onClick={ () => removeCondition( index ) }
						aria-label={ __( 'Remove Condition', 'woocommerce-simple-customizations' ) }
					>
						{ __( 'Remove', 'woocommerce-simple-customizations' ) }
					</Button>
				</div>
			) ) }
			<Button variant="secondary" onClick={ addCondition }>
				{ __( 'Add Condition', 'woocommerce-simple-customizations' ) }
			</Button>
		</div>
	);
};

export default ConditionBuilder;
