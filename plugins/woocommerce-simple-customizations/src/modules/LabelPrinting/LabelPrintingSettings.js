/**
 * External dependencies
 */
import { ToggleControl, SelectControl, TextControl, TextareaControl, Card, CardBody, Button, Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const LabelPrintingSettings = ( { settings, updateSetting } ) => {
    const [ isPreviewOpen, setIsPreviewOpen ] = useState( false );

    const generatePreview = ( template ) => {
        let content = template;
        if ( ! content ) return '<p>' + __( 'No template content.', 'woocommerce-simple-customizations' ) + '</p>';
        
        const dummies = {
            '{header}': settings.label_printing_header || 'My Store',
            '{shipping_address}': 'John Doe<br>123 Main St<br>New York, NY 10001',
            '{billing_address}': 'John Doe<br>123 Main St<br>New York, NY 10001',
            '{order_number}': '12345',
            '{order_id}': '123',
            '{phone}': '555-0123',
            '{payment_method}': 'COD',
            '{order_total}': '$125.00',
            '{weight}': '0.5 kg',
            '{items_summary}': 'Product A x 1, Product B x 2',
            '{return_address}': 'My Store<br>456 Warehouse Blvd<br>City, State 90210',
            '{current_date}': new Date().toLocaleDateString(),
            '{barcode_order}': '<div style="background: #000; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; color: white; font-size: 10px;">[QR]</div>',
            '{barcode_awb}': '<div style="background: #000; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; color: white; font-size: 10px;">[QR]</div>',
        };

        Object.keys( dummies ).forEach( key => {
            content = content.replace( new RegExp( key, 'g' ), dummies[ key ] );
        } );

        return content;
    };

    return (
        <div className="wsc-settings-panel">
            <Card>
                <CardBody>
                    <h2>{ __( 'Label Printing Settings', 'woocommerce-simple-customizations' ) }</h2>
                    <ToggleControl
                        label={ __( 'Enable Label Printing', 'woocommerce-simple-customizations' ) }
                        checked={ !! settings.label_printing_enabled }
                        onChange={ ( value ) => updateSetting( 'label_printing_enabled', value ) }
                    />

                    { settings.label_printing_enabled && (
                        <div style={{ marginTop: '20px' }}>
                             <SelectControl
                                label={ __( 'Label Size', 'woocommerce-simple-customizations' ) }
                                value={ settings.label_printing_size || '4x6' }
                                options={ [
                                    { label: '4x6 inches', value: '4x6' },
                                    { label: 'A4', value: 'a4' },
                                    { label: 'A5', value: 'a5' },
                                ] }
                                onChange={ ( value ) => updateSetting( 'label_printing_size', value ) }
                            />
                            
                            <TextControl
                                label={ __( 'Default Header Text', 'woocommerce-simple-customizations' ) }
                                value={ settings.label_printing_header || '' }
                                onChange={ ( value ) => updateSetting( 'label_printing_header', value ) }
                                help={ __( 'Text to appear at the top of the label (e.g. Store Name).', 'woocommerce-simple-customizations' ) }
                            />

                            <hr style={{ margin: '20px 0' }} />

                            <h3>{ __( 'Label Template', 'woocommerce-simple-customizations' ) }</h3>
                            <p>{ __( 'Customize the HTML for your label. Placeholders: {header}, {shipping_address}, {billing_address}, {phone}, {order_number}, {order_id}, {payment_method}, {order_total}, {weight}, {items_summary}, {return_address}, {current_date}.', 'woocommerce-simple-customizations' ) }</p>

                            <div style={{ marginBottom: '15px', display: 'flex', gap: '10px', flexWrap: 'wrap' }}>
                                <Button variant="secondary" onClick={ () => updateSetting( 'label_printing_template', 
                                    '<div style="text-align: center; font-family: sans-serif;">\n' +
                                    '  <h1>{header}</h1>\n' +
                                    '  <div style="margin: 20px 0; font-size: 16px;">{shipping_address}</div>\n' +
                                    '  <div style="font-size: 12px; color: #555;">Order #{order_number}</div>\n' +
                                    '</div>'
                                ) }>
                                    { __( 'Load Simple', 'woocommerce-simple-customizations' ) }
                                </Button>
                                <Button variant="secondary" onClick={ () => updateSetting( 'label_printing_template', 
                                    '<div style="border: 2px solid #000; padding: 20px; font-family: serif;">\n' +
                                    '  <h2 style="border-bottom: 2px solid #000; padding-bottom: 10px;">{header}</h2>\n' +
                                    '  <div style="margin: 20px 0;"><strong>Ship To:</strong><br>{shipping_address}</div>\n' +
                                    '  <hr>\n' +
                                    '  <div style="font-weight: bold;">Order: #{order_number}</div>\n' +
                                    '</div>'
                                ) }>
                                    { __( 'Load Classic', 'woocommerce-simple-customizations' ) }
                                </Button>
                                <Button variant="secondary" onClick={ () => updateSetting( 'label_printing_template', 
                                    '<div style="font-family: Helvetica, Arial, sans-serif; padding: 20px;">\n' +
                                    '  <div style="font-size: 24px; font-weight: 900; letter-spacing: -1px; margin-bottom: 20px;">{header}</div>\n' +
                                    '  <div style="background: #f0f0f0; padding: 15px; border-radius: 8px; margin-bottom: 20px;">\n' +
                                    '    <span style="font-size: 10px; text-transform: uppercase; color: #888; font-weight: bold;">Ship To</span><br>\n' +
                                    '    <div style="font-size: 18px; line-height: 1.4;">{shipping_address}</div>\n' +
                                    '  </div>\n' +
                                    '  <div style="font-size: 14px; color: #888;">Order ID: #{order_number}</div>\n' +
                                    '</div>'
                                ) }>
                                    { __( 'Load Modern', 'woocommerce-simple-customizations' ) }
                                </Button>
                                <Button variant="secondary" onClick={ () => updateSetting( 'label_printing_template', 
                                    '<div style="font-family: Arial, sans-serif; border: 2px solid #000; padding: 0; max-width: 100%; box-sizing: border-box;">\n' +
                                    '  <!-- Top Section -->\n' +
                                    '  <div style="display: flex; border-bottom: 2px solid #000;">\n' +
                                    '    <div style="flex: 1; padding: 10px; border-right: 2px solid #000;">\n' +
                                    '      <strong style="font-size: 14px;">Ship To:</strong><br>\n' +
                                    '      <div style="font-size: 14px; margin-top: 5px;">{shipping_address}</div>\n' +
                                    '      <div style="margin-top: 5px; font-size: 12px;"><strong>Phone:</strong> {phone}</div>\n' +
                                    '    </div>\n' +
                                    '    <div style="flex: 1; padding: 10px; text-align: center;">\n' +
                                    '      <div style="font-size: 12px; font-weight: bold;">Courier: Standard</div>\n' +
                                    '      <div style="margin: 10px 0; height: 50px; display: flex; align-items: center; justify-content: center;">{barcode_awb}</div>\n' +
                                    '      <div style="font-size: 10px;">AWB: 123456789</div>\n' +
                                    '    </div>\n' +
                                    '  </div>\n' +
                                    '  \n' +
                                    '  <!-- Middle Details -->\n' +
                                    '  <div style="display: flex; border-bottom: 2px solid #000; font-size: 12px;">\n' +
                                    '    <div style="flex: 1; padding: 8px; border-right: 1px solid #000;">\n' +
                                    '      <strong>Payment:</strong> {payment_method}\n' +
                                    '    </div>\n' +
                                    '    <div style="flex: 1; padding: 8px; border-right: 1px solid #000;">\n' +
                                    '      <strong>Weight:</strong> {weight}\n' +
                                    '    </div>\n' +
                                    '    <div style="flex: 1; padding: 8px;">\n' +
                                    '      <strong>COD Amount:</strong> {order_total}\n' +
                                    '    </div>\n' +
                                    '  </div>\n' +
                                    '  \n' +
                                    '  <!-- Items Section -->\n' +
                                    '  <div style="padding: 10px; border-bottom: 2px solid #000; font-size: 12px;">\n' +
                                    '    <strong>Item(s):</strong> {items_summary}\n' +
                                    '  </div>\n' +
                                    '  \n' +
                                    '  <!-- Return Address / Bottom -->\n' +
                                    '  <div style="display: flex; padding: 10px;">\n' +
                                    '    <div style="flex: 1; font-size: 12px;">\n' +
                                    '      <strong>Shipped By (If undelivered, return to):</strong><br>\n' +
                                    '      <div style="margin-top: 5px;">{return_address}</div>\n' +
                                    '    </div>\n' +
                                    '    <div style="flex: 0 0 150px; text-align: center;">\n' +
                                    '       <div style="font-size: 10px; margin-bottom: 5px;"><strong>Order #: {order_number}</strong></div>\n' +
                                    '       <div style="height: 40px; display: flex; align-items: center; justify-content: center;">{barcode_order}</div>\n' +
                                    '    </div>\n' +
                                    '  </div>\n' +
                                    '  \n' +
                                    '  <!-- Footer -->\n' +
                                    '  <div style="border-top: 2px solid #000; padding: 5px; font-size: 9px; text-align: center;">\n' +
                                    '    THIS IS AN AUTO-GENERATED LABEL AND DOES NOT NEED SIGNATURE.\n' +
                                    '  </div>\n' +
                                    '</div>'
                                ) }>
                                    { __( 'Load Logistic', 'woocommerce-simple-customizations' ) }
                                </Button>
                                <Button variant="primary" onClick={ () => setIsPreviewOpen( true ) }>
                                    { __( 'Preview Template', 'woocommerce-simple-customizations' ) }
                                </Button>
                            </div>

                            <TextareaControl
                                label={ __( 'HTML Template', 'woocommerce-simple-customizations' ) }
                                value={ settings.label_printing_template || '' }
                                onChange={ ( value ) => updateSetting( 'label_printing_template', value ) }
                                rows={ 15 }
                                style={{ fontFamily: 'monospace', fontSize: '12px', lineHeight: '1.5' }}
                            />
                        </div>
                    ) }
                </CardBody>
            </Card>

            { isPreviewOpen && (
                <Modal title={ __( 'Label Preview', 'woocommerce-simple-customizations' ) } onRequestClose={ () => setIsPreviewOpen( false ) }>
                    <div style={{ padding: '20px', border: '1px solid #ddd', background: '#fff' }}>
                        <div dangerouslySetInnerHTML={{ __html: generatePreview( settings.label_printing_template || '' ) }} />
                    </div>
                    <div style={{ marginTop: '20px', textAlign: 'right' }}>
                        <Button variant="secondary" onClick={ () => setIsPreviewOpen( false ) }>
                            { __( 'Close', 'woocommerce-simple-customizations' ) }
                        </Button>
                    </div>
                </Modal>
            ) }
        </div>
    );
};

export default LabelPrintingSettings;
