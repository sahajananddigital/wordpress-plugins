import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody, Spinner } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import ConditionsList from './ConditionsList';
import ConditionModal from './ConditionModal';

const App = () => {
    const [ conditions, setConditions ] = useState( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ isModalOpen, setIsModalOpen ] = useState( false );
    const [ currentCondition, setCurrentCondition ] = useState( null );

    useEffect( () => {
        fetchConditions();
    }, [] );

    const fetchConditions = () => {
        setIsLoading( true );
        apiFetch( { 
            path: '/wc-csp/v1/conditions',
            headers: { 'X-WP-Nonce': wcCspSettings.nonce }
        } ).then( ( data ) => {
            setConditions( data );
            setIsLoading( false );
        } ).catch( ( error ) => {
            console.error( 'Error fetching conditions:', error );
            setIsLoading( false );
        } );
    };

    const saveConditions = ( newConditions ) => {
        // Optimistic update
        setConditions( newConditions );

        apiFetch( {
            path: '/wc-csp/v1/conditions',
            method: 'POST',
            data: newConditions,
            headers: { 'X-WP-Nonce': wcCspSettings.nonce }
        } ).then( ( data ) => {
            setConditions( data );
        } ).catch( ( error ) => {
            console.error( 'Error saving conditions:', error );
            // Revert or show error? For now just log.
            alert( __( 'Failed to save conditions.', 'woocommerce-conditional-shipping-and-payments' ) );
            fetchConditions(); // Revert to server state
        } );
    };

    const handleAdd = () => {
        setCurrentCondition( null );
        setIsModalOpen( true );
    };

    const handleEdit = ( condition ) => {
        setCurrentCondition( condition );
        setIsModalOpen( true );
    };

    const handleDelete = ( id ) => {
        if ( confirm( __( 'Are you sure you want to delete this condition?', 'woocommerce-conditional-shipping-and-payments' ) ) ) {
            const newConditions = conditions.filter( ( c ) => c.id !== id );
            saveConditions( newConditions );
        }
    };

    const handleToggle = ( id ) => {
        const newConditions = conditions.map( ( c ) => 
            c.id === id ? { ...c, enabled: ! c.enabled } : c
        );
        saveConditions( newConditions );
    };

    const handleSave = ( condition ) => {
        let newConditions;
        if ( currentCondition ) {
            // Edit
            newConditions = conditions.map( ( c ) => 
                c.id === condition.id ? condition : c
            );
        } else {
            // Add
            // Ensure ID is set (backend will sanitize, but good to have temp one if needed, though backend response updates it)
            if ( ! condition.id ) {
                condition.id = Date.now().toString(); 
            }
            newConditions = [ ...conditions, condition ];
        }
        
        saveConditions( newConditions );
        setIsModalOpen( false );
    };

    return (
        <div className="wc-csp-app">
            <div className="wc-csp-header" style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' } }>
                <h1 style={ { margin: 0 } }>{ __( 'Payment Conditions', 'woocommerce-conditional-shipping-and-payments' ) }</h1>
            </div>
            
            <p>{ __( 'The added conditions apply in consecutive order.', 'woocommerce-conditional-shipping-and-payments' ) }</p>

            <Card>
                { isLoading ? (
                    <div style={ { padding: '20px', textAlign: 'center' } }>
                        <Spinner />
                    </div>
                ) : (
                    <ConditionsList 
                        conditions={ conditions }
                        onEdit={ handleEdit }
                        onDelete={ handleDelete }
                        onToggle={ handleToggle }
                    />
                ) }
            </Card>

            <div style={ { marginTop: '20px' } }>
                <Button variant="primary" onClick={ handleAdd }>
                    { __( 'Add payment condition', 'woocommerce-conditional-shipping-and-payments' ) }
                </Button>
            </div>

            <ConditionModal 
                isOpen={ isModalOpen }
                condition={ currentCondition }
                onClose={ () => setIsModalOpen( false ) }
                onSave={ handleSave }
            />
        </div>
    );
};

export default App;
