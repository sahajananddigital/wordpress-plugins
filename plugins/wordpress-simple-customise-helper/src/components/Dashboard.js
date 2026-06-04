import { __ } from '@wordpress/i18n';
import { Card, CardBody, CardHeader, Button, CardFooter } from '@wordpress/components';
import { useState } from '@wordpress/element';
import modules from '../modules';
import AddModuleModal from './AddModuleModal';

const Dashboard = ( { settings, setActiveTab, updateSetting } ) => {
    const [ isModalOpen, setIsModalOpen ] = useState( false );

    const activeModules = Object.values( modules ).filter( module => settings[ `${ module.name }_active` ] );
    const availableModules = Object.values( modules ).filter( module => ! settings[ `${ module.name }_active` ] );

    const handleAddModule = ( moduleName ) => {
        updateSetting( `${ moduleName }_active`, true );
        setIsModalOpen( false );
        setActiveTab( moduleName );
    };

    const handleRemoveModule = ( moduleName ) => {
        updateSetting( `${ moduleName }_active`, false );
    };

    return (
        <div className="wsch-dashboard">
            <div className="wsch-header-actions" style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: '20px' }}>
                <Button isPrimary onClick={ () => setIsModalOpen( true ) }>
                    { __( 'Add New Customization', 'wordpress-simple-customise-helper' ) }
                </Button>
            </div>

            <div className="wsch-section">
                <h2 style={{ marginBottom: '20px' }}>{ __( 'Active Customizations', 'wordpress-simple-customise-helper' ) }</h2>
                { activeModules.length > 0 ? (
                    <div className="wsch-modules-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: '20px' }}>
                        { activeModules.map( module => (
                            <Card key={ module.name }>
                                <CardHeader>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', width: '100%' }}>
                                        <h3>{ module.title }</h3>
                                        <Button 
                                            icon="trash" 
                                            label={ __( 'Remove', 'wordpress-simple-customise-helper' ) }
                                            isDestructive 
                                            variant="tertiary"
                                            onClick={ () => handleRemoveModule( module.name ) }
                                        />
                                    </div>
                                </CardHeader>
                                <CardBody>
                                    <p>{ module.description }</p>
                                </CardBody>
                                <CardFooter>
                                    <Button isSecondary onClick={ () => setActiveTab( module.name ) }>
                                        { __( 'Edit Settings', 'wordpress-simple-customise-helper' ) }
                                    </Button>
                                </CardFooter>
                            </Card>
                        ) ) }
                    </div>
                ) : (
                    <Card>
                        <CardBody>
                            <p>{ __( 'No active customizations. Click "Add New Customization" to get started.', 'wordpress-simple-customise-helper' ) }</p>
                        </CardBody>
                    </Card>
                ) }
            </div>

            <AddModuleModal 
                isOpen={ isModalOpen } 
                onClose={ () => setIsModalOpen( false ) }
                modules={ availableModules }
                onAdd={ handleAddModule }
            />
        </div>
    );
};

export default Dashboard;
