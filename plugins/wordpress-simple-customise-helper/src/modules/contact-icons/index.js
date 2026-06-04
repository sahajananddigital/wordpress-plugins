import { __ } from '@wordpress/i18n';
import { Card, CardBody, CardHeader, ToggleControl, TextControl } from '@wordpress/components';

const ContactIcons = ( { settings, updateSetting } ) => {
    return (
        <div className="wsch-contact-icons-settings">
            <Card>
                <CardHeader>
                    <h3>{ __( 'Contact Icons', 'wordpress-simple-customise-helper' ) }</h3>
                </CardHeader>
                <CardBody>
                    <ToggleControl
                        label={ __( 'Enable Contact Icons', 'wordpress-simple-customise-helper' ) }
                        checked={ !! settings.contact_icons_active }
                        onChange={ ( value ) => updateSetting( 'contact_icons_active', value ) }
                    />

                    <TextControl
                        label={ __( 'WhatsApp Number', 'wordpress-simple-customise-helper' ) }
                        help={ __( 'Enter your WhatsApp number with country code (e.g., 15551234567).', 'wordpress-simple-customise-helper' ) }
                        value={ settings.contact_icons_whatsapp || '' }
                        onChange={ ( value ) => updateSetting( 'contact_icons_whatsapp', value ) }
                        placeholder="15551234567"
                    />

                    <TextControl
                        label={ __( 'Phone Number', 'wordpress-simple-customise-helper' ) }
                        help={ __( 'Enter your phone number for click-to-call.', 'wordpress-simple-customise-helper' ) }
                        value={ settings.contact_icons_phone || '' }
                        onChange={ ( value ) => updateSetting( 'contact_icons_phone', value ) }
                        placeholder="+1 (555) 123-4567"
                    />
                </CardBody>
            </Card>
        </div>
    );
};

export default {
    name: 'contact_icons',
    title: __( 'Contact Icons', 'wordpress-simple-customise-helper' ),
    description: __( 'Add floating WhatsApp and Phone contact icons to your site.', 'wordpress-simple-customise-helper' ),
    component: ContactIcons,
};
