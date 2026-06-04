/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { ToggleControl, Button, Card, CardBody } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import RuleModal from '../../components/RuleModal';

const CartLimitSettings = ( { settings, updateSetting } ) => {
    const [ isModalOpen, setIsModalOpen ] = useState( false );
    const [ currentRuleIndex, setCurrentRuleIndex ] = useState( null );
    const [ ruleToEdit, setRuleToEdit ] = useState( null );

    const rules = settings.cart_limit_rules || [];

    // Open Modal for New Rule
    const openNewRuleModal = () => {
        setRuleToEdit( {
            conditions: [],
            target_type: 'global',
            target_id: 0,
            min_qty: 1
        } );
        setCurrentRuleIndex( null );
        setIsModalOpen( true );
    };

    // Open Modal to Edit Rule
    const openEditRuleModal = ( index ) => {
        setRuleToEdit( rules[ index ] );
        setCurrentRuleIndex( index );
        setIsModalOpen( true );
    };

    // Save Rule from Modal
    const saveRule = ( updatedRule ) => {
        const newRules = [ ...rules ];
        if ( currentRuleIndex !== null ) {
            newRules[ currentRuleIndex ] = updatedRule;
        } else {
            newRules.push( updatedRule );
        }
        updateSetting( 'cart_limit_rules', newRules );
        setIsModalOpen( false );
    };

    const removeRule = ( index ) => {
        const newRules = rules.filter( ( _, i ) => i !== index );
        updateSetting( 'cart_limit_rules', newRules );
    };

    // Migration
    useEffect( () => {
        if ( rules.length === 0 && settings.cart_limit_min_qty > 0 ) {
             const legacyRule = {
                 conditions: [],
                 target_type: settings.cart_limit_rule_type || 'global',
                 target_id: settings.cart_limit_term_id || 0,
                 min_qty: settings.cart_limit_min_qty
             };
             updateSetting( 'cart_limit_rules', [ legacyRule ] );
        }
    }, [] );

    const getTargetLabel = ( rule ) => {
        if ( rule.target_type === 'global' ) return 'Global';
        if ( rule.target_type === 'category' ) return `Category (ID: ${rule.target_id})`;
        if ( rule.target_type === 'tag' ) return `Tag (ID: ${rule.target_id})`;
        return rule.target_type;
    };

    return (
        <div className="wsc-cart-limit-settings">
            <h2>{ __( 'Cart Limits', 'woocommerce-simple-customizations' ) }</h2>
            
            <ToggleControl
                label={ __( 'Enable Cart Limit', 'woocommerce-simple-customizations' ) }
                checked={ !! settings.cart_limit_enabled }
                onChange={ ( value ) => updateSetting( 'cart_limit_enabled', value ) }
            />

            { settings.cart_limit_enabled && (
                <div className="wsc-rules-list">
                    <p>{ __( 'Manage your cart limits below. Rules are evaluated in order.', 'woocommerce-simple-customizations' ) }</p>
                    
                    { rules.map( ( rule, index ) => (
                        <Card key={ index } className="wsc-rule-card" style={ { marginBottom: '10px', padding: '15px' } }>
                            <div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
                                <div>
                                    <strong>{ sprintf( __( 'Rule #%d:', 'woocommerce-simple-customizations' ), index + 1 ) }</strong>
                                    <span style={ { marginLeft: '10px' } }>
                                        { getTargetLabel( rule ) } 
                                        { ` - Min Qty: ${rule.min_qty}` }
                                    </span>
                                    <div style={{fontSize: '0.9em', color: '#666'}}>
                                        { rule.conditions && rule.conditions.length > 0 
                                            ? sprintf( __( '%d Condition(s)', 'woocommerce-simple-customizations' ), rule.conditions.length )
                                            : __( 'Always Applies', 'woocommerce-simple-customizations' ) 
                                        }
                                    </div>
                                </div>
                                <div style={ { display: 'flex', gap: '5px' } }>
                                    <Button isSecondary onClick={ () => openEditRuleModal( index ) }>
                                        { __( 'Edit', 'woocommerce-simple-customizations' ) }
                                    </Button>
                                    <Button isDestructive isSmall variant="tertiary" onClick={ () => removeRule( index ) }>
                                        { __( 'Delete', 'woocommerce-simple-customizations' ) }
                                    </Button>
                                </div>
                            </div>
                        </Card>
                    ) ) }

                    <Button variant="primary" onClick={ openNewRuleModal } style={ { marginTop: '10px' } }>
                        { __( 'Add New Rule', 'woocommerce-simple-customizations' ) }
                    </Button>

                    { isModalOpen && (
                        <RuleModal 
                            rule={ ruleToEdit }
                            onSave={ saveRule }
                            onClose={ () => setIsModalOpen( false ) }
                        />
                    ) }
                </div>
            ) }
        </div>
    );
};

export default CartLimitSettings;
