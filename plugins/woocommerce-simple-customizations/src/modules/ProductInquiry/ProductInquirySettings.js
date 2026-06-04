/**
 * External dependencies
 */
import { ToggleControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ProductInquirySettings = ( { settings, updateSetting } ) => {
    return (
        <div className="wsc-product-inquiry-settings">
            <h2>{ __( 'Product Inquiry', 'woocommerce-simple-customizations' ) }</h2>
            
            <ToggleControl
                label={ __( 'Enable Product Inquiry Mode', 'woocommerce-simple-customizations' ) }
                help={ __( 'This will turn the checkout into an inquiry form and disable payment requirements.', 'woocommerce-simple-customizations' ) }
                checked={ !! settings.product_inquiry_enabled }
                onChange={ ( value ) => updateSetting( 'product_inquiry_enabled', value ) }
            />

            { settings.product_inquiry_enabled && (
                <>
                    <ToggleControl
                        label={ __( 'Replace "Add to Cart" Button', 'woocommerce-simple-customizations' ) }
                        checked={ !! settings.product_inquiry_replace_atc }
                        onChange={ ( value ) => updateSetting( 'product_inquiry_replace_atc', value ) }
                    />

                    <ToggleControl
                        label={ __( 'Allow Inquiry for Priceless Products', 'woocommerce-simple-customizations' ) }
                        help={ __( 'Allow customers to inquire about products without a price set.', 'woocommerce-simple-customizations' ) }
                        checked={ !! settings.product_inquiry_allow_priceless }
                        onChange={ ( value ) => updateSetting( 'product_inquiry_allow_priceless', value ) }
                    />

                    { settings.product_inquiry_replace_atc && (
                        <TextControl
                            label={ __( 'Inquiry Button Text', 'woocommerce-simple-customizations' ) }
                            value={ settings.product_inquiry_btn_text || '' }
                            placeholder={ __( 'Add to Inquiry', 'woocommerce-simple-customizations' ) }
                            onChange={ ( value ) => updateSetting( 'product_inquiry_btn_text', value ) }
                        />
                    ) }

                    <TextControl
                        label={ __( 'Inquiry Submit Button Text', 'woocommerce-simple-customizations' ) }
                        value={ settings.product_inquiry_order_btn_text || '' }
                        placeholder={ __( 'Send Inquiry', 'woocommerce-simple-customizations' ) }
                        onChange={ ( value ) => updateSetting( 'product_inquiry_order_btn_text', value ) }
                    />
                    <TextControl
                        label={ __( 'Cart Page Button Text', 'woocommerce-simple-customizations' ) }
                        value={ settings.product_inquiry_checkout_btn_text || '' }
                        placeholder={ __( 'Proceed to Inquiry', 'woocommerce-simple-customizations' ) }
                        onChange={ ( value ) => updateSetting( 'product_inquiry_checkout_btn_text', value ) }
                    />

                    <TextControl
                        label={ __( 'Cart Label (e.g. "Inquiry List")', 'woocommerce-simple-customizations' ) }
                        value={ settings.product_inquiry_cart_label || '' }
                        placeholder={ __( 'Inquiry List', 'woocommerce-simple-customizations' ) }
                        onChange={ ( value ) => updateSetting( 'product_inquiry_cart_label', value ) }
                    />
                </>
            ) }
        </div>
    );
};

export default ProductInquirySettings;
