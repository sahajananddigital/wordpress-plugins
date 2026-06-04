/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { Button, Spinner, Notice, TabPanel } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CartLimitSettings from '../modules/CartLimit/CartLimitSettings';
import PriceSuffixSettings from '../modules/PriceSuffix/PriceSuffixSettings';
import ProductInquirySettings from '../modules/ProductInquiry/ProductInquirySettings';
import LabelPrintingSettings from '../modules/LabelPrinting/LabelPrintingSettings';
import Dashboard from './Dashboard';
import AddCustomizationModal from './AddCustomizationModal';

const SettingsApp = () => {
    const [ settings, setSettings ] = useState( {} );
    const [ isSaving, setIsSaving ] = useState( false );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ notice, setNotice ] = useState( null );
    
    // Navigation State
    const [ currentView, setCurrentView ] = useState( 'dashboard' ); // dashboard | module_edit
    const [ activeModuleId, setActiveModuleId ] = useState( null );
    const [ isAddModalOpen, setIsAddModalOpen ] = useState( false );

    useEffect( () => {
        apiFetch( { path: '/wsc/v1/settings' } ).then( ( data ) => {
            setSettings( data );
            setIsLoading( false );
        } ).catch( ( error ) => {
            console.error( error );
            setIsLoading( false );
        });
    }, [] );

    const handleSave = () => {
        setIsSaving( true );
        setNotice( null );
        apiFetch( {
            path: '/wsc/v1/settings',
            method: 'POST',
            data: settings,
        } ).then( ( data ) => {
            setSettings( data );
            setIsSaving( false );
            setNotice( { status: 'success', content: __( 'Settings saved.', 'woocommerce-simple-customizations' ) } );
        } ).catch( ( error ) => {
            setIsSaving( false );
            setNotice( { status: 'error', content: error.message } );
        } );
    };

    const updateSetting = ( key, value ) => {
        setSettings( ( prev ) => ( { ...prev, [ key ]: value } ) );
    };

    const handleAddModule = ( moduleId ) => {
        updateSetting( `${moduleId}_enabled`, true );
        // Optionally switch to edit mode immediately
        // setActiveModuleId( moduleId );
        // setCurrentView( 'module_edit' );
    };

    const handleEditModule = ( moduleId ) => {
        setActiveModuleId( moduleId );
        setCurrentView( 'module_edit' );
    };

    const handleRemoveModule = ( moduleId ) => {
        updateSetting( `${moduleId}_enabled`, false );
    };

    if ( isLoading ) {
        return <Spinner />;
    }

    // Render Module Content
    const renderModuleSettings = () => {
        if ( activeModuleId === 'cart_limit' ) {
            return <CartLimitSettings settings={ settings } updateSetting={ updateSetting } />;
        } else if ( activeModuleId === 'price_suffix' ) {
            return <PriceSuffixSettings settings={ settings } updateSetting={ updateSetting } />;
        } else if ( activeModuleId === 'product_inquiry' ) {
            return <ProductInquirySettings settings={ settings } updateSetting={ updateSetting } />;
        } else if ( activeModuleId === 'label_printing' ) {
            return <LabelPrintingSettings settings={ settings } updateSetting={ updateSetting } />;
        }
        return null;
    };

    return (
        <div className="wsc-settings-app">
            <div style={{ display: 'flex', alignItems: 'center', marginBottom: '20px', gap: '10px' }}>
                 { currentView === 'module_edit' && (
                     <Button variant="secondary" onClick={ () => setCurrentView( 'dashboard' ) }>
                         &larr; { __( 'Back', 'woocommerce-simple-customizations' ) }
                     </Button>
                 ) }
                 <h1 style={{margin: 0}}>{ __( 'Simple Customizations', 'woocommerce-simple-customizations' ) }</h1> 
            </div>

            { notice && (
                <Notice status={ notice.status } onRemove={ () => setNotice( null ) }>
                    { notice.content }
                </Notice>
            ) }
            
            { currentView === 'dashboard' ? (
                <>
                    <Dashboard 
                        settings={ settings }
                        onAddModule={ () => setIsAddModalOpen( true ) }
                        onEditModule={ handleEditModule }
                        onRemoveModule={ handleRemoveModule }
                    />
                    { isAddModalOpen && (
                        <AddCustomizationModal 
                            settings={ settings }
                            onEnableModule={ handleAddModule }
                            onClose={ () => setIsAddModalOpen( false ) }
                        />
                    ) }
                </>
            ) : (
                <div className="wsc-module-edit-view">
                    { renderModuleSettings() }
                </div>
            ) }

            <div className="wsc-settings-footer" style={{ marginTop: '30px', borderTop: '1px solid #ddd', paddingTop: '20px' }}>
                <Button isPrimary isBusy={ isSaving } onClick={ handleSave }>
                    { __( 'Save Changes', 'woocommerce-simple-customizations' ) }
                </Button>
            </div>
        </div>
    );
};

export default SettingsApp;
