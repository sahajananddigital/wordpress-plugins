import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, Spinner, Notice } from '@wordpress/components';
import Dashboard from './Dashboard';
import modules from '../modules';

const SettingsApp = () => {
    const [ settings, setSettings ] = useState( {} );
    const [ isSaving, setIsSaving ] = useState( false );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ notice, setNotice ] = useState( null );
    
    // Controlled navigation state
    const [ currentView, setCurrentView ] = useState( 'dashboard' );

    useEffect( () => {
        apiFetch( { path: '/wsch/v1/settings' } ).then( ( data ) => {
            setSettings( data );
            setIsLoading( false );
        } );
    }, [] );

    const updateSetting = ( key, value ) => {
        setSettings( { ...settings, [ key ]: value } );
    };

    const saveSettings = () => {
        setIsSaving( true );
        setNotice( null );
        apiFetch( {
            path: '/wsch/v1/settings',
            method: 'POST',
            data: settings,
        } )
        .then( ( response ) => {
            setSettings( response );
            setIsSaving( false );
            setNotice( { status: 'success', text: __( 'Settings saved successfully.', 'wordpress-simple-customise-helper' ) } );
        } )
        .catch( () => {
             setIsSaving( false );
             setNotice( { status: 'error', text: __( 'Error saving settings.', 'wordpress-simple-customise-helper' ) } );
        } );
    };

    if ( isLoading ) {
        return <div className="wsch-loading"><Spinner /></div>;
    }

    /**
     * Determine which component to render based on currentView.
     */
    const renderContent = () => {
        if ( currentView === 'dashboard' ) {
            return (
                <Dashboard 
                    settings={ settings } 
                    setActiveTab={ setCurrentView }
                    updateSetting={ updateSetting }
                />
            );
        }

        const activeModule = Object.values( modules ).find( m => m.name === currentView );
        if ( activeModule ) {
            const Component = activeModule.component;
            return (
                <Component 
                    settings={ settings } 
                    updateSetting={ updateSetting } 
                /> 
            );
        }

        return null;
    };

    return (
        <div className="wsch-settings-app">
            <div className="wsch-header" style={{ marginBottom: '20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '15px' }}>
                    { currentView !== 'dashboard' && (
                        <Button isSecondary onClick={ () => setCurrentView( 'dashboard' ) }>
                            { __( '← Back', 'wordpress-simple-customise-helper' ) }
                        </Button>
                    ) }
                    <h1>{ __( 'WordPress Simple Customise Helper', 'wordpress-simple-customise-helper' ) }</h1>
                </div>
                <Button isPrimary isBusy={ isSaving } onClick={ saveSettings }>
                    { __( 'Save Changes', 'wordpress-simple-customise-helper' ) }
                </Button>
            </div>

            { notice && (
                <Notice status={ notice.status } onRemove={ () => setNotice( null ) } style={{ marginBottom: '20px' }}>
                    { notice.text }
                </Notice>
            ) }

            <div className="wsch-content">
                { renderContent() }
            </div>
        </div>
    );
};

export default SettingsApp;
