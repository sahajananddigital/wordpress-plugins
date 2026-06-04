/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { Modal, Button, SelectControl, TextControl, Spinner, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ConditionBuilder from './ConditionBuilder';

const RuleModal = ( { rule, onSave, onClose } ) => {
    const [ localRule, setLocalRule ] = useState( { ...rule } );
    const [ terms, setTerms ] = useState( [] );
    const [ isLoadingTerms, setIsLoadingTerms ] = useState( false );

    useEffect( () => {
        setLocalRule( { ...rule } );
    }, [ rule ] );

    // Fetch terms based on target type
    useEffect( () => {
        if ( localRule.target_type === 'global' ) {
            setTerms( [] );
            return;
        }

        const fetchTerms = async () => {
            setIsLoadingTerms( true );
            try {
                // Fetch only the relevant taxonomy terms
                const taxonomy = localRule.target_type === 'category' ? 'product_cat' : 'product_tag';
                const data = await apiFetch( { path: `/wp/v2/${taxonomy}?per_page=100` } );
                const formattedTerms = data.map( t => ( { 
                    label: t.name, 
                    value: t.id 
                } ) );
                setTerms( [ { label: __( 'Select Term', 'woocommerce-simple-customizations' ), value: 0 }, ...formattedTerms ] );
            } catch ( error ) {
                console.error( error );
            } finally {
                setIsLoadingTerms( false );
            }
        };

        fetchTerms();
    }, [ localRule.target_type ] );

    const updateLocalRule = ( field, value ) => {
        setLocalRule( prev => ( { ...prev, [ field ]: value } ) );
    };

    return (
        <Modal
            title={ rule.id ? __( 'Edit Rule', 'woocommerce-simple-customizations' ) : __( 'Add New Rule', 'woocommerce-simple-customizations' ) }
            onRequestClose={ onClose }
            className="wsc-rule-modal"
        >
            <div className="wsc-rule-modal-content" style={ { paddingBottom: '20px' } }>
                <div style={ { display: 'flex', gap: '15px', marginBottom: '20px' } }>
                    <SelectControl
                        label={ __( 'Apply To', 'woocommerce-simple-customizations' ) }
                        value={ localRule.target_type }
                        options={ [
                            { label: 'Global (All Products)', value: 'global' },
                            { label: 'Category', value: 'category' },
                            { label: 'Tag', value: 'tag' },
                        ] }
                        onChange={ ( val ) => updateLocalRule( 'target_type', val ) }
                    />
                    
                    { localRule.target_type !== 'global' && (
                        isLoadingTerms ? (
                            <div style={ { marginTop: '25px' } }><Spinner /></div>
                        ) : (
                            <SelectControl
                                label={ __( 'Select Term', 'woocommerce-simple-customizations' ) }
                                value={ localRule.target_id }
                                options={ terms }
                                onChange={ ( val ) => updateLocalRule( 'target_id', val ) }
                            />
                        )
                    ) }

                    <TextControl
                        label={ __( 'Min Quantity', 'woocommerce-simple-customizations' ) }
                        type="number"
                        value={ localRule.min_qty }
                        onChange={ ( val ) => updateLocalRule( 'min_qty', val ) }
                    />

                    <div style={{ alignSelf: 'center', marginTop: '20px' }}>
                         <ToggleControl
                            label={ __( 'Auto-adjust Qty', 'woocommerce-simple-customizations' ) }
                            help={ __( 'If enabled, quantity will be automatically increased to minimum when adding to cart.', 'woocommerce-simple-customizations' ) }
                            checked={ !! localRule.auto_adjust_qty }
                            onChange={ ( val ) => updateLocalRule( 'auto_adjust_qty', val ) }
                        />
                    </div>
                </div>

                <hr />
                
                <h4>{ __( 'Conditions', 'woocommerce-simple-customizations' ) }</h4>
                <p className="description" style={{marginBottom: '15px'}}>
                    { __( 'Configure conditions that must be met for this limit to apply. Leave empty to always apply.', 'woocommerce-simple-customizations' ) }
                </p>
                
                <ConditionBuilder 
                    conditions={ localRule.conditions || [] }
                    onChange={ ( newConditions ) => updateLocalRule( 'conditions', newConditions ) }
                />

                <div className="wsc-modal-actions" style={ { marginTop: '20px', display: 'flex', justifyContent: 'flex-end', gap: '10px' } }>
                    <Button variant="secondary" onClick={ onClose }>
                        { __( 'Cancel', 'woocommerce-simple-customizations' ) }
                    </Button>
                    <Button variant="primary" onClick={ () => onSave( localRule ) }>
                        { __( 'Save Rule', 'woocommerce-simple-customizations' ) }
                    </Button>
                </div>
            </div>
        </Modal>
    );
};

export default RuleModal;
