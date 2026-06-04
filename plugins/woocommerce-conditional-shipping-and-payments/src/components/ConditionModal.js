import { __ } from '@wordpress/i18n';
import { Modal, TextControl, SelectControl, FormTokenField, Button, ToggleControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

const ConditionModal = ( { condition, isOpen, onClose, onSave } ) => {
    const [ title, setTitle ] = useState( '' );
    const [ action, setAction ] = useState( 'enable' );
    const [ paymentMethods, setPaymentMethods ] = useState( [] );
    const [ conditionType, setConditionType ] = useState( 'billing_country' );
    const [ countries, setCountries ] = useState( [] );
    const [ enabled, setEnabled ] = useState( true );

    // Data
    const availablePaymentMethods = wcCspSettings.paymentMethods || [];
    const availableCountries = [ 'US', 'NL', 'GB', 'DE', 'FR', 'SE', 'IN' ]; // Example codes

    useEffect( () => {
        if ( condition ) {
            setTitle( condition.title || '' );
            setAction( condition.action || 'enable' );
            setPaymentMethods( condition.payment_methods || [] );
            setCountries( condition.countries || [] );
            setEnabled( condition.enabled !== false );
        } else {
            setTitle( '' );
            setAction( 'enable' );
            setPaymentMethods( [] );
            setCountries( [] );
            setEnabled( true );
        }
    }, [ condition ] );

    const handleSave = () => {
        onSave( {
            id: condition ? condition.id : Date.now(), // Temporary ID generation
            title,
            action,
            payment_methods: paymentMethods,
            countries,
            enabled,
        } );
    };

    if ( ! isOpen ) {
        return null;
    }

    return (
        <Modal
            title={ condition ? __( 'Edit condition', 'woocommerce-conditional-shipping-and-payments' ) : __( 'Add condition', 'woocommerce-conditional-shipping-and-payments' ) }
            onRequestClose={ onClose }
            shouldCloseOnClickOutside={ false }
        >
            <div className="wc-csp-modal-content">
                <TextControl
                    label={ __( 'Title', 'woocommerce-conditional-shipping-and-payments' ) }
                    value={ title }
                    onChange={ setTitle }
                />

                <SelectControl
                    label={ __( 'Action', 'woocommerce-conditional-shipping-and-payments' ) }
                    value={ action }
                    options={ [
                        { label: __( 'Enable payment methods', 'woocommerce-conditional-shipping-and-payments' ), value: 'enable' },
                        { label: __( 'Disable payment methods', 'woocommerce-conditional-shipping-and-payments' ), value: 'disable' },
                    ] }
                    onChange={ setAction }
                />

                <FormTokenField
                    label={ __( 'Payment methods', 'woocommerce-conditional-shipping-and-payments' ) }
                    value={ paymentMethods }
                    suggestions={ availablePaymentMethods }
                    onChange={ ( tokens ) => setPaymentMethods( tokens ) }
                />

                <SelectControl
                    label={ __( 'Condition', 'woocommerce-conditional-shipping-and-payments' ) }
                    value={ conditionType }
                    options={ [
                        { label: __( 'Billing country', 'woocommerce-conditional-shipping-and-payments' ), value: 'billing_country' },
                    ] }
                    onChange={ setConditionType }
                />

                <FormTokenField
                    label={ __( 'Billing country', 'woocommerce-conditional-shipping-and-payments' ) }
                    value={ countries }
                    suggestions={ availableCountries }
                    onChange={ ( tokens ) => setCountries( tokens ) }
                />

                <ToggleControl
                    label={ __( 'Enabled', 'woocommerce-conditional-shipping-and-payments' ) }
                    checked={ enabled }
                    onChange={ setEnabled }
                />

                <div className="wc-csp-modal-actions" style={ { marginTop: '20px', textAlign: 'right' } }>
                    <Button variant="secondary" onClick={ onClose } style={ { marginRight: '10px' } }>
                        { __( 'Cancel', 'woocommerce-conditional-shipping-and-payments' ) }
                    </Button>
                    <Button variant="primary" onClick={ handleSave }>
                        { __( 'Save changes', 'woocommerce-conditional-shipping-and-payments' ) }
                    </Button>
                </div>
            </div>
        </Modal>
    );
};

export default ConditionModal;
