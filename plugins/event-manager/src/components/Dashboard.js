import { useState, useEffect, useRef } from '@wordpress/element';
import {
    Button,
    TextControl,
    Spinner,
    SnackbarList,
    Card,
    CardBody,
    CardHeader,
    Flex,
    FlexItem,
    Modal
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const Dashboard = () => {
    const [stats, setStats] = useState(null);
    const [attendees, setAttendees] = useState([]);
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(true);
    const [notices, setNotices] = useState([]);

    // Modal State
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create'); // 'create', 'edit', 'expense'
    const [currentAttendee, setCurrentAttendee] = useState({ name: '', mobile: '', email: '', amount: '', payment_mode: 'cash', status: 'active', uuid: '' });
    const [currentExpense, setCurrentExpense] = useState({ title: '', amount: '', category: 'general', date: '' });
    const [expenses, setExpenses] = useState([]);
    const [showExpenses, setShowExpenses] = useState(false);
    const [showSupportDetails, setShowSupportDetails] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);

    // ...





    // Import State
    const fileInputRef = useRef(null);
    const [importing, setImporting] = useState(false);

    const fetchStats = () => {
        apiFetch({ path: '/event-manager/v1/stats' }).then(setStats);
    };

    const fetchAttendees = (query = '') => {
        setLoading(true);
        const path = query ? `/event-manager/v1/attendees?q=${query}` : '/event-manager/v1/attendees';
        apiFetch({ path }).then((data) => {
            setAttendees(data);
            setLoading(false);
        });
    };

    const fetchExpenses = () => {
        apiFetch({ path: '/event-manager/v1/expenses' }).then(setExpenses);
    };

    useEffect(() => {
        fetchStats();
        fetchAttendees();
        fetchExpenses();
    }, []);

    // Debounce Search
    useEffect(() => {
        const delayDebounceFn = setTimeout(() => {
            if (search !== '') {
                fetchAttendees(search);
            } else {
                fetchAttendees();
            }
        }, 500);

        return () => clearTimeout(delayDebounceFn);
    }, [search]);

    const handleSearch = (value) => {
        setSearch(value);
    };

    const handleCheckIn = (uuid) => {
        apiFetch({
            path: '/event-manager/v1/checkin',
            method: 'POST',
            data: { uuid }
        }).then((res) => {
            setNotices([...notices, { id: Date.now(), content: res.message, type: 'snackbar' }]);
            fetchStats();
            fetchAttendees(search);
        }).catch((error) => {
            setNotices([...notices, { id: Date.now(), content: error.message, status: 'error' }]);
        });
    };

    const handleDelete = (uuid) => {
        if (!confirm('Are you sure you want to delete this attendee?')) return;

        apiFetch({
            path: `/event-manager/v1/attendees/${uuid}`,
            method: 'DELETE'
        }).then((res) => {
            setNotices([...notices, { id: Date.now(), content: res.message, type: 'snackbar' }]);
            fetchStats();
            fetchAttendees(search);
        }).catch((error) => {
            setNotices([...notices, { id: Date.now(), content: error.message, status: 'error' }]);
        });
    };

    const openModal = (mode = 'create', attendee = null) => {
        setModalMode(mode);
        if (attendee) {
            setCurrentAttendee({
                uuid: attendee.uuid,
                name: attendee.name,
                mobile: attendee.mobile,
                email: attendee.email || '',
                amount: attendee.amount,
                payment_mode: attendee.payment_mode,
                razorpay_payment_id: attendee.razorpay_payment_id,
                quantity: attendee.quantity || 1,
                status: attendee.status || 'active'
            });
        } else if (mode === 'support') {
            if (attendee) {
                // Pre-fill from existing attendee but reset amount/payment
                setCurrentAttendee({
                    name: attendee.name,
                    mobile: attendee.mobile,
                    email: attendee.email,
                    amount: '',
                    quantity: 0,
                    payment_mode: 'cash',
                    status: 'active', // Default to active
                    uuid: '' // New entry
                });
            } else {
                // Blank support entry
                setCurrentAttendee({ name: '', mobile: '', email: '', amount: '', quantity: 0, payment_mode: 'cash', status: 'active', uuid: '' });
            }
        } else {
            setCurrentAttendee({ name: '', mobile: '', email: '', amount: '', quantity: 1, payment_mode: 'cash', status: 'active', uuid: '' });
        }
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setCurrentAttendee({ name: '', mobile: '', email: '', amount: '', quantity: 1, payment_mode: 'cash', uuid: '' });
    };

    const handleRegister = () => {
        setIsSubmitting(true);
        apiFetch({
            path: '/event-manager/v1/register',
            method: 'POST',
            data: currentAttendee
        }).then((res) => {
            setNotices([...notices, { id: Date.now(), content: res.message, type: 'snackbar' }]);
            closeModal();
            fetchStats();
            fetchAttendees(search);
        }).catch((error) => {
            setNotices([...notices, { id: Date.now(), content: error.message, status: 'error' }]);
        }).finally(() => {
            setIsSubmitting(false);
        });
    };

    const handleSaveExpense = () => {
        setIsSubmitting(true);
        apiFetch({
            path: '/event-manager/v1/expenses',
            method: 'POST',
            data: currentExpense
        }).then((res) => {
            setNotices([...notices, { id: Date.now(), content: res.message, type: 'snackbar' }]);
            closeModal();
            fetchStats();
            fetchExpenses();
        }).catch((error) => {
            setNotices([...notices, { id: Date.now(), content: error.message, status: 'error' }]);
        }).finally(() => {
            setIsSubmitting(false);
        });
    };

    const handleDeleteExpense = (id) => {
        if (!confirm('Are you sure you want to delete this expense?')) return;
        apiFetch({
            path: `/event-manager/v1/expenses/${id}`,
            method: 'DELETE'
        }).then((res) => {
            setNotices([...notices, { id: Date.now(), content: res.message, type: 'snackbar' }]);
            fetchStats();
            fetchExpenses();
        }).catch((error) => {
            setNotices([...notices, { id: Date.now(), content: error.message, status: 'error' }]);
        });
    };

    const handleFileUpload = (event) => {
        const file = event.target.files[0];
        if (!file) return;

        setImporting(true);
        const reader = new FileReader();
        reader.onload = async (e) => {
            const text = e.target.result;
            const rows = text.split('\n').map(row => row.split(','));
            const headers = rows[0].map(h => h.trim().toLowerCase());

            // Precise column mapping for Razorpay CSV
            const nameIdx = headers.indexOf('name');
            const mobileIdx = headers.indexOf('phone');
            const emailIdx = headers.indexOf('email');
            const amountIdx = headers.indexOf('total payment amount');
            const paymentIdIdx = headers.indexOf('payment id');
            const quantityIdx = headers.indexOf('item quantity');
            const statusIdx = headers.indexOf('payment status'); // New status col

            let successCount = 0;
            let failCount = 0;
            let skipCount = 0; // Track skipped/duplicates

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                if (row.length < 2) continue;

                const getVal = (idx) => idx > -1 && row[idx] ? row[idx].trim().replace(/^"|"$/g, '') : '';

                let name = getVal(nameIdx);
                if (!name && headers.indexOf('item name') > -1) name = getVal(headers.indexOf('item name'));

                const mobile = getVal(mobileIdx);
                const email = getVal(emailIdx);
                const amount = getVal(amountIdx) || '0';
                const paymentId = getVal(paymentIdIdx);
                const quantity = getVal(quantityIdx);
                let paymentStatus = getVal(statusIdx).toLowerCase();

                // Map status
                let status = 'pending';
                if (paymentStatus === 'captured' || paymentStatus === 'paid' || paymentStatus === 'success') {
                    status = 'active'; // Or 'paid'? We use 'active' or 'pending' usually? status-active CSS class exists? 
                    // Wait, existing CSS usually has status-pending, status-active etc?
                    // Let's assume 'active' means valid/paid attendee.
                } else if (paymentStatus === 'failed') {
                    status = 'cancelled';
                }

                if (!name && !mobile) continue;

                if (quantity && parseInt(quantity) > 1) {
                    name = `${name} (x${quantity})`;
                }

                const attendeeData = {
                    name: name,
                    mobile: mobile,
                    email: email,
                    amount: amount,
                    razorpay_payment_id: paymentId,
                    payment_mode: 'razorpay',
                    status: status,
                    quantity: quantity ? parseInt(quantity) : 1
                };

                try {
                    const response = await apiFetch({
                        path: '/event-manager/v1/register',
                        method: 'POST',
                        data: attendeeData
                    });

                    if (response.success) {
                        successCount++;
                    } else if (response.code === 'duplicate_payment_id') {
                        skipCount++;
                    } else {
                        failCount++; // Should be caught by catch block usually, but if rest_ensure_response(false)...
                        // API returns error object in parsing? apiFetch throws on error status.
                        // My controller returns specific JSON for duplicate, but apiFetch treats it as success if 200 OK?
                        // rest_ensure_response returns 200 OK.
                        // So response.success will be false.
                        // If code is duplicate_payment_id, count as skip.
                    }
                } catch (err) {
                    // Check if error is from WP_Error (non-200) or our custom JSON?
                    // apiFetch throws if status >= 300.
                    // My controller returns rest_ensure_response which is 200 OK even for success=false payload? yes.
                    // But WP_Error returns 400/500 which throws here.
                    failCount++;
                    console.error('Import error for row', i, err);
                }
            }

            setNotices([...notices, { id: Date.now(), content: `Import Complete: ${successCount} imported, ${skipCount} skipped (duplicate), ${failCount} failed.`, type: 'snackbar' }]);
            setImporting(false);
            fetchStats();
            fetchAttendees();
            if (fileInputRef.current) fileInputRef.current.value = '';
        };
        reader.readAsText(file);
    };

    // Exports
    const handleExportCSV = () => {
        if (!attendees.length) return alert('No attendees to export.');

        const headers = ['UUID', 'Name', 'Mobile', 'Email', 'Status', 'Payment Mode', 'Amount', 'Payment ID', 'Check In Status', 'Date Created'];
        const csvContent = [
            headers.join(','),
            ...attendees.map(a => [
                a.uuid,
                `"${a.name}"`,
                a.mobile,
                a.email,
                a.status,
                a.payment_mode,
                a.amount,
                a.razorpay_payment_id || '',
                a.check_in_status,
                a.date_created
            ].join(','))
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `attendees_export_${new Date().toISOString().slice(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    const handleExportPDF = async () => {
        if (!attendees.length) return alert('No attendees to export.');

        // Dynamic import to avoid build issues if not tree-shaken well, or just standard import above?
        // Let's rely on standard import, but for now assuming it's available via window or we import it.
        // Since I installed it, I should import it. I will add imports at top later.
        // For now, I'll assume imports are added.

        const { jsPDF } = await import('jspdf');
        const autoTable = (await import('jspdf-autotable')).default;

        const doc = new jsPDF();
        doc.text("Attendee Report", 14, 15);
        doc.setFontSize(10);
        doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 22);

        const tableColumn = ["Name", "Mobile", "Status", "Amount", "Check-In"];
        const tableRows = attendees.map(a => [
            a.name,
            a.mobile,
            a.status,
            `${a.payment_mode} (${a.amount})`,
            a.check_in_status == 1 ? 'Yes' : 'No'
        ]);

        autoTable(doc, {
            startY: 25,
            head: [tableColumn],
            body: tableRows,
        });

        doc.save(`attendee_report_${new Date().toISOString().slice(0, 10)}.pdf`);
    };



    return (
        <div className="wrap">
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '10px' }}>
                <h1 className="wp-heading-inline">Event Manager Dashboard</h1>
                <div style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
                    <input type="file" ref={fileInputRef} style={{ display: 'none' }} onChange={handleFileUpload} accept=".csv" />

                    <Button variant="secondary" onClick={() => fileInputRef.current.click()} isBusy={importing}>
                        Import CSV
                    </Button>
                    <Button variant="secondary" onClick={handleExportCSV}>
                        Export CSV
                    </Button>
                    <Button variant="secondary" onClick={handleExportPDF}>
                        Export PDF
                    </Button>
                    <Button variant="secondary" onClick={() => setShowExpenses(!showExpenses)}>
                        {showExpenses ? 'Hide Expenses' : 'View Expenses'}
                    </Button>
                    <Button variant="secondary" onClick={() => openModal('expense')}>
                        Add Expense
                    </Button>
                    <Button variant="secondary" onClick={() => openModal('support')}>
                        Add Support
                    </Button>
                    <Button variant="primary" onClick={() => openModal('create')}>
                        Add Attendee
                    </Button>
                </div>
            </div>
            <hr className="wp-header-end" />

            {showExpenses ? (
                <Card style={{ marginBottom: '20px', borderColor: '#d63638' }}>
                    <CardHeader><strong>Expense List</strong></CardHeader>
                    <CardBody>
                        <table className="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {expenses.length > 0 ? expenses.map((exp) => (
                                    <tr key={exp.id}>
                                        <td>{exp.title}</td>
                                        <td>{exp.category}</td>
                                        <td>{exp.date}</td>
                                        <td>₹{exp.amount}</td>
                                        <td>
                                            <Button
                                                icon="trash"
                                                label="Delete"
                                                isDestructive
                                                onClick={() => handleDeleteExpense(exp.id)}
                                            />
                                        </td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="5">No expenses recorded.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </CardBody>
                </Card>
            ) : null}

            {isModalOpen && (
                <div className="components-modal__screen-overlay">
                    <div className="components-modal__frame" style={{ maxWidth: '500px', margin: 'auto', marginTop: '100px', background: '#fff', padding: '20px', borderRadius: '4px', boxShadow: '0 3px 30px rgba(0,0,0,0.2)' }}>
                        <div className="components-modal__header">
                            <h2 style={{ margin: 0 }}>
                                {modalMode === 'edit' ? 'Edit Attendee' :
                                    modalMode === 'expense' ? 'Add Expense' :
                                        modalMode === 'support' ? 'Add Support / Donation' :
                                            'Register Attendee'}
                            </h2>
                            <Button icon="no-alt" onClick={closeModal} label="Close" />
                        </div>
                        <div className="components-modal__content" style={{ marginTop: '20px' }}>
                            {modalMode === 'expense' ? (
                                <>
                                    <TextControl
                                        label="Title"
                                        value={currentExpense.title}
                                        onChange={(val) => setCurrentExpense({ ...currentExpense, title: val })}
                                    />
                                    <TextControl
                                        label="Amount"
                                        type="number"
                                        value={currentExpense.amount}
                                        onChange={(val) => setCurrentExpense({ ...currentExpense, amount: val })}
                                    />
                                    <div style={{ marginBottom: '20px' }}>
                                        <label className="components-base-control__label">Category</label>
                                        <select
                                            className="components-select-control__input"
                                            value={currentExpense.category}
                                            onChange={(e) => setCurrentExpense({ ...currentExpense, category: e.target.value })}
                                            style={{ width: '100%', height: '30px' }}
                                        >
                                            <option value="general">General</option>
                                            <option value="food">Food</option>
                                            <option value="marketing">Marketing</option>
                                            <option value="venue">Venue</option>
                                            <option value="logistics">Logistics</option>
                                        </select>
                                    </div>
                                    <Flex justify="flex-end" gap={2}>
                                        <Button variant="secondary" onClick={closeModal}>Cancel</Button>
                                        <Button variant="primary" onClick={handleSaveExpense} isBusy={isSubmitting}>
                                            Save Expense
                                        </Button>
                                    </Flex>
                                </>
                            ) : (
                                <>
                                    <TextControl
                                        label={modalMode === 'support' ? "Donor Name" : "Name"}
                                        value={currentAttendee.name}
                                        onChange={(val) => setCurrentAttendee({ ...currentAttendee, name: val })}
                                    />
                                    <TextControl
                                        label="Mobile"
                                        value={currentAttendee.mobile}
                                        onChange={(val) => setCurrentAttendee({ ...currentAttendee, mobile: val })}
                                    />
                                    <TextControl
                                        label="Email"
                                        type="email"
                                        value={currentAttendee.email}
                                        onChange={(val) => setCurrentAttendee({ ...currentAttendee, email: val })}
                                    />
                                    <TextControl
                                        label={modalMode === 'support' ? "Donation Amount" : "Amount"}
                                        type="number"
                                        value={currentAttendee.amount}
                                        onChange={(val) => setCurrentAttendee({ ...currentAttendee, amount: val })}
                                    />

                                    {modalMode !== 'support' && (
                                        <TextControl
                                            label="Passes / Quantity"
                                            type="number"
                                            help="Enter 0 for donations/support without a pass."
                                            value={currentAttendee.quantity}
                                            onChange={(val) => setCurrentAttendee({ ...currentAttendee, quantity: val })}
                                        />
                                    )}

                                    <div style={{ marginBottom: '20px' }}>
                                        <label className="components-base-control__label">Status</label>
                                        <select
                                            className="components-select-control__input"
                                            value={currentAttendee.status}
                                            onChange={(e) => setCurrentAttendee({ ...currentAttendee, status: e.target.value })}
                                            style={{ width: '100%', height: '30px' }}
                                        >
                                            <option value="active">Active</option>
                                            <option value="pending">Pending</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>

                                    <div style={{ marginBottom: '20px' }}>
                                        <label className="components-base-control__label">Payment Mode</label>
                                        <select
                                            className="components-select-control__input"
                                            value={currentAttendee.payment_mode}
                                            onChange={(e) => setCurrentAttendee({ ...currentAttendee, payment_mode: e.target.value })}
                                            style={{ width: '100%', height: '30px' }}
                                        >
                                            <option value="cash">Cash</option>
                                            <option value="online">Online</option>
                                            <option value="razorpay">Razorpay (Gateway)</option>
                                            <option value="qrcode">QR Code</option>
                                        </select>
                                    </div>

                                    {currentAttendee.payment_mode === 'razorpay' && (
                                        <TextControl
                                            label="Razorpay Payment ID"
                                            value={currentAttendee.razorpay_payment_id || ''}
                                            onChange={(val) => setCurrentAttendee({ ...currentAttendee, razorpay_payment_id: val })}
                                        />
                                    )}
                                    <Flex justify="flex-end" gap={2}>
                                        <Button variant="secondary" onClick={closeModal}>Cancel</Button>
                                        <Button variant="primary" onClick={handleRegister} isBusy={isSubmitting}>
                                            {modalMode === 'edit' ? 'Update' : modalMode === 'support' ? 'Add Support' : 'Register'}
                                        </Button>
                                    </Flex>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {stats && (
                <Flex gap={4} className="event-manager-stats" style={{ marginBottom: '20px' }} wrap>
                    <FlexItem>
                        <Card>
                            <CardHeader><strong>Total Attendees</strong></CardHeader>
                            <CardBody className="number" style={{ fontSize: '24px', fontWeight: 'bold', textAlign: 'center' }}>
                                {stats.total}
                            </CardBody>
                        </Card>
                    </FlexItem>
                    <FlexItem>
                        <Card>
                            <CardHeader><strong>Total Passes</strong></CardHeader>
                            <CardBody className="number" style={{ fontSize: '24px', fontWeight: 'bold', textAlign: 'center' }}>
                                {stats.total_passes}
                            </CardBody>
                        </Card>
                    </FlexItem>
                    <FlexItem>
                        <Card>
                            <CardHeader><strong>Checked In</strong></CardHeader>
                            <CardBody className="number" style={{ fontSize: '24px', fontWeight: 'bold', textAlign: 'center' }}>
                                {stats.checked_in}
                            </CardBody>
                        </Card>
                    </FlexItem>
                    <FlexItem>
                        <Card>
                            <CardHeader><strong>Cash Collected</strong></CardHeader>
                            <CardBody className="number" style={{ fontSize: '24px', fontWeight: 'bold', textAlign: 'center' }}>
                                ₹{stats.cash_collected}
                            </CardBody>
                        </Card>
                    </FlexItem>
                    <FlexItem>
                        <Card>
                            <CardHeader><strong>Online (Razorpay)</strong></CardHeader>
                            <CardBody className="number" style={{ fontSize: '24px', fontWeight: 'bold', textAlign: 'center' }}>
                                ₹{stats.online_collected}
                            </CardBody>
                        </Card>
                    </FlexItem>
                    <FlexItem>
                        <Card>
                            <CardHeader><strong>QR Code Collected</strong></CardHeader>
                            <CardBody className="number" style={{ fontSize: '24px', fontWeight: 'bold', textAlign: 'center' }}>
                                ₹{stats.qr_collected}
                            </CardBody>
                        </Card>
                    </FlexItem>
                    <FlexItem>
                        <Card>
                            <CardHeader><strong>Support / Donations</strong></CardHeader>
                            <CardBody className="number" style={{ fontSize: '24px', fontWeight: 'bold', textAlign: 'center', color: '#6e40aa' }}>
                                <div>₹{stats.support_collected}</div>
                                <div style={{ fontSize: '13px', color: '#555', fontWeight: 'normal', marginTop: '4px' }}>
                                    {stats.total_supporters} Supporters
                                </div>
                                <Button
                                    isLink
                                    isSmall
                                    style={{ fontSize: '12px', marginTop: '5px', textDecoration: 'underline' }}
                                    onClick={() => setShowSupportDetails(true)}
                                >
                                    View Details
                                </Button>
                            </CardBody>
                        </Card>
                    </FlexItem>
                    <FlexItem>
                        <Card>
                            <CardHeader><strong>Expenses</strong></CardHeader>
                            <CardBody className="number" style={{ fontSize: '24px', fontWeight: 'bold', textAlign: 'center', color: '#d63638' }}>
                                -₹{stats.total_expenses}
                            </CardBody>
                        </Card>
                    </FlexItem>
                    <FlexItem>
                        <Card>
                            <CardHeader><strong>Net Amount</strong></CardHeader>
                            <CardBody className="number" style={{ fontSize: '24px', fontWeight: 'bold', textAlign: 'center', color: '#46b450' }}>
                                ₹{Number(stats.cash_collected) + Number(stats.online_collected) + Number(stats.qr_collected) + Number(stats.support_collected) - Number(stats.total_expenses)}
                            </CardBody>
                        </Card>
                    </FlexItem>
                </Flex>
            )}

            {showSupportDetails && (
                <Modal title="Support / Donation Details" onRequestClose={() => setShowSupportDetails(false)}>
                    <table className="wp-list-table widefat fixed striped" style={{ marginTop: 0 }}>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Payment Mode</th>
                            </tr>
                        </thead>
                        <tbody>
                            {attendees.filter(a => a.quantity == 0).length > 0 ? (
                                attendees.filter(a => a.quantity == 0).map(supporter => (
                                    <tr key={supporter.uuid}>
                                        <td>{supporter.name}</td>
                                        <td>{supporter.mobile}</td>
                                        <td>{supporter.date_created}</td>
                                        <td style={{ fontWeight: 'bold', color: '#6e40aa' }}>₹{supporter.amount}</td>
                                        <td>{supporter.payment_mode}</td>
                                    </tr>
                                ))
                            ) : (
                                <tr><td colSpan="5">No support entries found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </Modal>
            )}

            <div className="attendee-list-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                <h2 style={{ fontSize: '1.3em', margin: 0 }}>Attendees</h2>
                <div style={{ width: '300px' }}>
                    <TextControl
                        value={search}
                        onChange={handleSearch}
                        placeholder="Search by name or mobile..."
                        hideLabelFromVision
                        label="Search Attendees"
                    />
                </div>
            </div>

            <Card>
                <CardBody style={{ padding: 0 }}>
                    {loading ? (
                        <div style={{ padding: '20px', textAlign: 'center' }}>
                            <Spinner />
                        </div>
                    ) : (
                        <table className="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>Email</th>
                                    <th>Passes</th>
                                    <th>Status</th>
                                    <th>Payment Info</th>
                                    <th>Date</th>
                                    <th>Checked In</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {attendees.filter(a => a.quantity > 0).length > 0 ? attendees.filter(a => a.quantity > 0).map((att) => (
                                    <tr key={att.uuid}>
                                        <td>{att.name}</td>
                                        <td>{att.mobile}</td>
                                        <td>{att.email}</td>
                                        <td>
                                            <span style={{ fontWeight: 'bold' }}>{att.quantity || 1}</span>
                                        </td>
                                        <td>
                                            <span style={{
                                                padding: '4px 8px',
                                                borderRadius: '4px',
                                                background: att.status === 'active' ? '#c6e1c6' : '#f8d7da',
                                                color: att.status === 'active' ? '#5b841b' : '#721c24'
                                            }}>
                                                {att.status}
                                            </span>
                                        </td>
                                        <td>
                                            <div>{att.payment_mode} (₹{att.amount})</div>
                                            {att.razorpay_payment_id && (
                                                <div style={{ fontSize: '0.85em', color: '#646970', marginTop: '4px' }}>
                                                    ID: <code>{att.razorpay_payment_id}</code>
                                                </div>
                                            )}
                                        </td>
                                        <td>{new Date(att.date_created).toLocaleString()}</td>
                                        <td>{att.check_in_status == '1' ? 'Yes' : 'No'}</td>
                                        <td>
                                            <Flex gap={2}>
                                                <Button
                                                    isSmall
                                                    variant="secondary"
                                                    onClick={() => {
                                                        const confirmCheckIn = confirm(`Check in ${att.name}?`);
                                                        if (confirmCheckIn) handleCheckIn(att.uuid);
                                                    }}
                                                    disabled={att.check_in_status == '1'}
                                                >
                                                    Check In
                                                </Button>
                                                <Button
                                                    isSmall
                                                    variant="tertiary"
                                                    icon="edit"
                                                    onClick={() => openModal('edit', att)}
                                                    label="Edit"
                                                />
                                                <Button
                                                    isSmall
                                                    isDestructive
                                                    onClick={() => handleDelete(att.uuid)}
                                                    label="Delete"
                                                />
                                                <Button
                                                    isSmall
                                                    variant="secondary"
                                                    icon="heart"
                                                    onClick={() => openModal('support', att)}
                                                    label="Add Support"
                                                    title="Add Support/Donation for this attendee"
                                                />
                                            </Flex>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="9">No attendees found.</td></tr>
                                )}
                            </tbody>
                        </table>
                    )}
                </CardBody>
            </Card>

            <SnackbarList notices={notices} onRemove={(id) => setNotices(notices.filter(n => n.id !== id))} />
        </div>
    );
};

export default Dashboard;
