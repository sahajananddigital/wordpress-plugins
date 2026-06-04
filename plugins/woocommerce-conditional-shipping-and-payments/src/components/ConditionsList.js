import { __ } from '@wordpress/i18n';
import { Button, ToggleControl } from '@wordpress/components';

const ConditionsList = ( { conditions, onEdit, onDelete, onToggle } ) => {
    return (
        <table className="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th scope="col" className="manage-column column-title column-primary">{ __( 'Title', 'woocommerce-conditional-shipping-and-payments' ) }</th>
                    <th scope="col" className="manage-column column-enabled">{ __( 'Enabled', 'woocommerce-conditional-shipping-and-payments' ) }</th>
                    <th scope="col" className="manage-column column-actions">{ __( 'Actions', 'woocommerce-conditional-shipping-and-payments' ) }</th>
                </tr>
            </thead>
            <tbody>
                { conditions.length === 0 ? (
                    <tr>
                        <td colSpan="3">{ __( 'No conditions found.', 'woocommerce-conditional-shipping-and-payments' ) }</td>
                    </tr>
                ) : (
                    conditions.map( ( condition ) => (
                        <tr key={ condition.id }>
                            <td className="title column-title has-row-actions column-primary" data-colname="Title">
                                <strong>{ condition.title }</strong>
                            </td>
                            <td className="enabled column-enabled" data-colname="Enabled">
                                <ToggleControl
                                    checked={ condition.enabled }
                                    onChange={ () => onToggle( condition.id ) }
                                />
                            </td>
                            <td className="actions column-actions" data-colname="Actions">
                                <Button variant="link" onClick={ () => onEdit( condition ) }>{ __( 'Edit', 'woocommerce-conditional-shipping-and-payments' ) }</Button>
                                |
                                <Button variant="link" isDestructive onClick={ () => onDelete( condition.id ) }>{ __( 'Delete', 'woocommerce-conditional-shipping-and-payments' ) }</Button>
                            </td>
                        </tr>
                    ) )
                ) }
            </tbody>
        </table>
    );
};

export default ConditionsList;
