import { __ } from '@wordpress/i18n';
import { Modal, Button, Card, CardHeader, CardBody, CardFooter } from '@wordpress/components';
import { useState } from '@wordpress/element';

const AddModuleModal = ( { isOpen, onClose, modules, onAdd } ) => {
    if ( ! isOpen ) {
        return null;
    }

    return (
        <Modal
            title={ __( 'Add New Customization', 'wordpress-simple-customise-helper' ) }
            onRequestClose={ onClose }
            className="wsch-add-module-modal"
        >
            <div className="wsch-modules-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: '20px', padding: '20px 0' }}>
                { modules.length > 0 ? (
                    modules.map( module => (
                        <Card key={ module.name } style={{ border: '1px solid #e0e0e0' }}>
                            <CardHeader><h3>{ module.title }</h3></CardHeader>
                            <CardBody>
                                <p>{ module.description }</p>
                            </CardBody>
                            <CardFooter>
                                <Button isPrimary onClick={ () => onAdd( module.name ) }>
                                    { __( 'Add', 'wordpress-simple-customise-helper' ) }
                                </Button>
                            </CardFooter>
                        </Card>
                    ) )
                ) : (
                    <p>{ __( 'All available customizations are already active.', 'wordpress-simple-customise-helper' ) }</p>
                ) }
            </div>
        </Modal>
    );
};

export default AddModuleModal;
