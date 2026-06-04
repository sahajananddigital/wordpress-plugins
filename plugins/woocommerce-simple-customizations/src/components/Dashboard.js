/**
 * External dependencies
 */
import { Button, Card, CardBody, CardHeader } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Dashboard = ( { settings, onEditModule, onAddModule, onRemoveModule } ) => {
    
    // Define available modules map for display
    const modules = [
        {
            id: 'cart_limit',
            title: __( 'Cart Limit', 'woocommerce-simple-customizations' ),
            description: __( 'Restrict cart checkout based on rules.', 'woocommerce-simple-customizations' ),
            isEnabled: !! settings.cart_limit_enabled,
        },
        {
            id: 'price_suffix',
            title: __( 'Price Suffix', 'woocommerce-simple-customizations' ),
            description: __( 'Add custom text after product prices.', 'woocommerce-simple-customizations' ),
            isEnabled: !! settings.price_suffix_enabled,
        },
        {
            id: 'product_inquiry',
            title: __( 'Product Inquiry', 'woocommerce-simple-customizations' ),
            description: __( 'Turn checkout into an inquiry form.', 'woocommerce-simple-customizations' ),
            isEnabled: !! settings.product_inquiry_enabled,
        },
        {
            id: 'label_printing',
            title: __( 'Label Printing', 'woocommerce-simple-customizations' ),
            description: __( 'Print labels for orders.', 'woocommerce-simple-customizations' ),
            isEnabled: !! settings.label_printing_enabled,
        }
    ];

    const activeModules = modules.filter( m => m.isEnabled );

    return (
        <div className="wsc-dashboard">
            <div className="wsc-dashboard-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <p>{ __( 'Active Customizations', 'woocommerce-simple-customizations' ) }</p>
                <Button variant="primary" onClick={ onAddModule }>
                    { __( 'Add Customization', 'woocommerce-simple-customizations' ) }
                </Button>
            </div>

            { activeModules.length === 0 ? (
                <div className="wsc-empty-state" style={{ textAlign: 'center', padding: '50px', background: '#fff', borderRadius: '4px', border: '1px dashed #ccc' }}>
                    <p>{ __( 'No customizations active. Click "Add Customization" to start.', 'woocommerce-simple-customizations' ) }</p>
                </div>
            ) : (
                <div className="wsc-modules-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: '20px' }}>
                    { activeModules.map( module => (
                        <Card key={ module.id }>
                            <CardHeader>
                                <strong>{ module.title }</strong>
                            </CardHeader>
                            <CardBody>
                                <p>{ module.description }</p>
                                <div className="wsc-card-actions" style={{ display: 'flex', gap: '10px', marginTop: '15px' }}>
                                    <Button variant="secondary" onClick={ () => onEditModule( module.id ) }>
                                        { __( 'Edit', 'woocommerce-simple-customizations' ) }
                                    </Button>
                                    <Button isDestructive isSmall variant="tertiary" onClick={ () => onRemoveModule( module.id ) }>
                                        { __( 'Remove', 'woocommerce-simple-customizations' ) }
                                    </Button>
                                </div>
                            </CardBody>
                        </Card>
                    ) ) }
                </div>
            ) }
        </div>
    );
};

export default Dashboard;
