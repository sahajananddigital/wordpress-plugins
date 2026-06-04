/**
 * External dependencies
 */
import { ToggleControl, Card, CardBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const PriceSuffixSettings = ( { settings, updateSetting } ) => {
    return (
        <div className="wsc-settings-panel">
            <Card>
                <CardBody>
                    <h2>{ __( 'Price Suffix Settings', 'woocommerce-simple-customizations' ) }</h2>
                    <ToggleControl
                        label={ __( 'Enable Price Suffix', 'woocommerce-simple-customizations' ) }
                        checked={ !! settings.price_suffix_enabled }
                        onChange={ ( value ) => updateSetting( 'price_suffix_enabled', value ) }
                    />
                    
                    <div className="wsc-settings-description" style={ { marginTop: '20px', padding: '15px', background: '#f0f0f1', borderRadius: '4px' } }>
                        <p>
                            <strong>{ __( 'How to use:', 'woocommerce-simple-customizations' ) }</strong>
                        </p>
                        <ol>
                            <li>{ __( 'Go to any Product > General Tab.', 'woocommerce-simple-customizations' ) }</li>
                            <li>{ __( 'Look for the "Price Suffix" field.', 'woocommerce-simple-customizations' ) }</li>
                            <li>{ __( 'Enter your desired text (e.g. "per box").', 'woocommerce-simple-customizations' ) }</li>
                            <li>{ __( 'Save the product.', 'woocommerce-simple-customizations' ) }</li>
                        </ol>
                    </div>
                </CardBody>
            </Card>
        </div>
    );
};

export default PriceSuffixSettings;
