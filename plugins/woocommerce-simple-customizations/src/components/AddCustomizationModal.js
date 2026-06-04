/**
 * External dependencies
 */
import { Modal, Button, CheckboxControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const AddCustomizationModal = ( { settings, onEnableModule, onClose } ) => {
    // List of all mock available modules
    const allModules = [
        {
            id: 'cart_limit',
            title: __( 'Cart Limit', 'woocommerce-simple-customizations' ),
            description: __( 'Restrict cart checkout based on rules.', 'woocommerce-simple-customizations' ),
        },
        {
            id: 'price_suffix',
            title: __( 'Price Suffix', 'woocommerce-simple-customizations' ),
            description: __( 'Add custom text after product prices.', 'woocommerce-simple-customizations' ),
        },
        {
            id: 'product_inquiry',
            title: __( 'Product Inquiry', 'woocommerce-simple-customizations' ),
            description: __( 'Turn checkout into an inquiry form.', 'woocommerce-simple-customizations' ),
        },
        {
            id: 'label_printing',
            title: __( 'Label Printing', 'woocommerce-simple-customizations' ),
            description: __( 'Print labels for orders.', 'woocommerce-simple-customizations' ),
        }
    ];

    // Filter out already enabled modules
    const availableModules = allModules.filter( m => ! settings[ `${m.id}_enabled` ] );

    const [ selected, setSelected ] = useState( [] );

    const handleToggle = ( id ) => {
        if ( selected.includes( id ) ) {
            setSelected( selected.filter( s => s !== id ) );
        } else {
            setSelected( [ ...selected, id ] );
        }
    };

    const handleAdd = () => {
        selected.forEach( id => onEnableModule( id ) );
        onClose();
    };

    return (
        <Modal
            title={ __( 'Add Customization', 'woocommerce-simple-customizations' ) }
            onRequestClose={ onClose }
            className="wsc-add-customization-modal"
        >
            <div style={{ paddingBottom: '20px' }}>
                { availableModules.length === 0 ? (
                    <p>{ __( 'All available customizations are already active.', 'woocommerce-simple-customizations' ) }</p>
                ) : (
                    <ul style={{ listStyle: 'none', padding: 0 }}>
                        { availableModules.map( module => (
                            <li key={ module.id } style={{ marginBottom: '10px', padding: '10px', border: '1px solid #eee', borderRadius: '4px' }}>
                                <CheckboxControl
                                    label={ 
                                        <span>
                                            <strong>{ module.title }</strong>
                                            <br/>
                                            <small>{ module.description }</small>
                                        </span>
                                     }
                                    checked={ selected.includes( module.id ) }
                                    onChange={ () => handleToggle( module.id ) }
                                />
                            </li>
                        ) ) }
                    </ul>
                ) }
            </div>
            
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px', marginTop: '20px' }}>
                <Button variant="secondary" onClick={ onClose }>
                    { __( 'Cancel', 'woocommerce-simple-customizations' ) }
                </Button>
                <Button variant="primary" onClick={ handleAdd } disabled={ selected.length === 0 }>
                    { __( 'Add Selected', 'woocommerce-simple-customizations' ) }
                </Button>
            </div>
        </Modal>
    );
};

export default AddCustomizationModal;
