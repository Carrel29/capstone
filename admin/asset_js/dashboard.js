// =========================
// Dashboard JS
// =========================

// ========== STATUS HANDLING ==========
function updateStatus(bookingId, newStatus) {
    if (!newStatus) return;

    const refreshIndicator = document.getElementById('refreshIndicator');
    refreshIndicator.style.display = 'inline';
    refreshIndicator.textContent = 'Updating status...';

    fetch('update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(bookingId)}&new_status=${encodeURIComponent(newStatus)}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`tr:has(.status-cell-${bookingId})`);

                if (newStatus === 'Completed' || newStatus === 'Canceled') {
                    if (row) {
                        row.classList.add('blink');
                        setTimeout(() => row.remove(), 1500);
                    }
                    refreshTableData();
                } else {
                    const statusCell = document.querySelector(`.status-cell-${bookingId}`);
                    if (statusCell) statusCell.textContent = newStatus;
                }

                refreshIndicator.textContent = 'Status updated successfully';
                if (newStatus === 'Completed' || newStatus === 'Canceled') {
                    fetchMonthData(currentDisplayMonth.getMonth(), currentDisplayMonth.getFullYear());
                }
            } else {
                throw new Error(data.message || 'Failed to update status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            refreshIndicator.textContent = 'Error updating status';
            alert('Error updating status: ' + error.message);
        })
        .finally(() => {
            setTimeout(() => { refreshIndicator.style.display = 'none'; }, 2000);
        });
}

// Refresh active inquiries
function refreshTableData() {
    fetch('get_inquiries.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('.customer-table tbody');
            tbody.innerHTML = '';

            data.forEach(booking => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${escapeHtml(booking.bt_first_name + ' ' + booking.bt_last_name)}</td>
                    <td>${escapeHtml(booking.btschedule)}</td>
                    <td class="status-cell-${escapeHtml(booking.id)}">${escapeHtml(booking.status)}</td>
                    <td>${escapeHtml(booking.btevent)}</td>
                    <td>
                        <select class="status-select" onchange="updateStatus('${escapeHtml(booking.id)}', this.value)">
                            <option value="">Change Status</option>
                            ${['Pending', 'Approved', 'Canceled', 'Completed'].map(statusOption => `
                                <option value="${statusOption}" ${booking.status === statusOption ? 'selected' : ''}>
                                    ${statusOption}
                                </option>
                            `).join('')}
                        </select>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => console.error('Error:', error));
}

function escapeHtml(unsafe) {
    return unsafe.replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
}

setInterval(refreshTableData, 30000);

// ========== MODAL HANDLING ==========
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('historyModal');
    const historyBtn = document.getElementById('historyButton');
    const closeBtn = document.querySelector('.close-modal');
    const clearBtn = document.getElementById('clearArchiveBtn');

    if (historyBtn) {
        historyBtn.onclick = () => modal.style.display = 'block';
    }
    if (closeBtn) {
        closeBtn.onclick = () => modal.style.display = 'none';
    }
    window.onclick = event => { if (event.target == modal) modal.style.display = 'none'; };

    if (clearBtn) {
        clearBtn.onclick = function () {
            if (confirm('Are you sure you want to clear all archived inquiries? This action cannot be undone.')) {
                fetch('clear_archive.php', { method: 'POST' })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            document.querySelector('.modal-table tbody').innerHTML = '';
                            alert('All archived inquiries have been cleared.');
                        } else {
                            alert(data.message || 'Failed to clear archived inquiries.');
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        alert('An error occurred while clearing the archive.');
                    });
            }
        };
    }
});

// ========== CHARTS ==========
const ctx = document.getElementById('bookingChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: bookingAnalytics.map(item => item.btevent),
        datasets: [{
            label: 'Number of Bookings',
            data: bookingAnalytics.map(item => item.booking_count),
            backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40']
        }]
    },
    options: { responsive: true, plugins: { legend: { display: true } } }
});

const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('default', { month: 'long', year: 'numeric' });
        }),
        datasets: [{
            label: 'Number of Bookings',
            data: monthlyData.map(item => item.booking_count),
            borderColor: '#36A2EB',
            backgroundColor: 'rgba(54,162,235,0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        aspectRatio:2,
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Monthly Booking Trends', font: { size: 24, weight: 'bold' }, color: '#333' }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});

let currentDisplayMonth = new Date();
function updateMonthDisplay() {
    document.getElementById('currentMonthDisplay').textContent =
        currentDisplayMonth.toLocaleDateString('default', { month: 'long', year: 'numeric' });
}

function fetchMonthData(month, year) {
    fetch(`get_month_data.php?month=${month+1}&year=${year}`)
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                const data = response.data;
                document.getElementById('totalBookings').textContent = data.total_bookings;
                document.getElementById('confirmedBookings').textContent = data.confirmed_bookings;
                document.getElementById('pendingBookings').textContent = data.pending_bookings;
                document.getElementById('popularPackages').textContent = data.popular_packages.length > 0
                    ? data.popular_packages.join(', ')
                    : 'No bookings';
            }
        })
        .catch(err => console.error('Error fetching month data:', err));
}

document.getElementById('prevMonth').addEventListener('click', () => {
    currentDisplayMonth.setMonth(currentDisplayMonth.getMonth() - 1);
    updateMonthDisplay();
    fetchMonthData(currentDisplayMonth.getMonth(), currentDisplayMonth.getFullYear());
});
document.getElementById('nextMonth').addEventListener('click', () => {
    currentDisplayMonth.setMonth(currentDisplayMonth.getMonth() + 1);
    updateMonthDisplay();
    fetchMonthData(currentDisplayMonth.getMonth(), currentDisplayMonth.getFullYear());
});

document.addEventListener('DOMContentLoaded', () => {
    updateMonthDisplay();
    fetchMonthData(currentDisplayMonth.getMonth(), currentDisplayMonth.getFullYear());
});
