import { useState } from '@wordpress/element';
import { Button, Card, CardBody, CardHeader, SnackbarList, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const Settings = () => {
    const [loading, setLoading] = useState(false);
    const [notices, setNotices] = useState([]);

    const handleClearAll = () => {
        if (!window.confirm('DANGER: Are you sure you want to DELETE ALL ATTENDEES? This cannot be undone.')) return;
        if (!window.confirm('Double Check: Really delete everyone?')) return;

        setLoading(true);
        apiFetch({
            path: '/event-manager/v1/attendees?all=true',
            method: 'DELETE'
        }).then((res) => {
            setNotices([...notices, { id: Date.now(), content: res.message, type: 'snackbar' }]);
            setLoading(false);
        }).catch((error) => {
            setNotices([...notices, { id: Date.now(), content: error.message, status: 'error' }]);
            setLoading(false);
        });
    };

    return (
        <div className="wrap">
            <h1>Event Manager Settings</h1>
            <hr className="wp-header-end" />

            <Card style={{ maxWidth: '600px', marginTop: '20px', borderColor: '#d63638' }}>
                <CardHeader style={{ backgroundColor: '#fcf0f1', borderBottomColor: '#d63638' }}>
                    <strong>Danger Zone</strong>
                </CardHeader>
                <CardBody>
                    <p>Parameters related to critical data management.</p>
                    <p><strong>Clear All Data:</strong> This will permanently delete all attendee records from the database. This action cannot be undone.</p>

                    {loading ? (
                        <Spinner />
                    ) : (
                        <Button isDestructive variant="primary" onClick={handleClearAll}>
                            Delete All Attendees
                        </Button>
                    )}
                </CardBody>
            </Card>

            <SnackbarList notices={notices} onRemove={(id) => setNotices(notices.filter(n => n.id !== id))} />
        </div>
    );
};

export default Settings;
