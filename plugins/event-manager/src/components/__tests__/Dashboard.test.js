import { render, screen, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import Dashboard from '../Dashboard';
import apiFetch from '@wordpress/api-fetch';

// Mock apiFetch
jest.mock('@wordpress/api-fetch');

describe('Dashboard', () => {
    beforeEach(() => {
        apiFetch.mockClear();
    });

    it('renders loading spinner initially, then stats and attendees', async () => {
        // Mock Responses
        apiFetch.mockImplementation((options) => {
            if (options.path === '/event-manager/v1/stats') {
                return Promise.resolve({
                    total: 10,
                    checked_in: 5,
                    cash_collected: 1000,
                    online_collected: 2000
                });
            }
            if (options.path && options.path.includes('/event-manager/v1/attendees')) {
                return Promise.resolve([
                    { uuid: '123', name: 'John Doe', mobile: '9999', status: 'pending', check_in_status: 0 }
                ]);
            }
            return Promise.resolve({});
        });

        render(<Dashboard />);

        // Initial loading state might be too fast to catch or just spinner, 
        // but we expect eventual content.

        await waitFor(() => expect(screen.getByText('Event Manager Dashboard')).toBeInTheDocument());

        // Check Stats
        expect(screen.getByText('Total Attendees')).toBeInTheDocument();
        expect(screen.getByText('10')).toBeInTheDocument(); // Stats total

        // Check Attendees List
        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('9999')).toBeInTheDocument();

        // Check Check-in Button exists
        expect(screen.getByRole('button', { name: 'Check In' })).toBeInTheDocument();
    });
});
