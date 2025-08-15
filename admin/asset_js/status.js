  // Replace the existing updateStatus function with this updated version
  function updateStatus(customerName, newStatus) {
    if (!newStatus) return;

    const refreshIndicator = document.getElementById('refreshIndicator');
    refreshIndicator.style.display = 'inline';
    refreshIndicator.textContent = 'Updating status...';

    fetch('update_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `customer_name=${encodeURIComponent(customerName)}&new_status=${encodeURIComponent(newStatus)}`
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`tr:has(.status-cell-${customerName})`);

                // Handle both Completed and Cancelled statuses
                if (newStatus === 'Completed' || newStatus === 'Cancelled') {
                    if (row) {
                        row.classList.add('blink');
                        setTimeout(() => {
                            row.remove();
                        }, 1500);
                    }
                    refreshTableData(); // Refresh the table data
                } else {
                    // Update the status cell for other statuses
                    const statusCell = document.querySelector(`.status-cell-${customerName}`);
                    if (statusCell) {
                        statusCell.textContent = newStatus;
                    }
                }

                refreshIndicator.textContent = 'Status updated successfully';

                // Refresh monthly stats if status is Completed or Cancelled
                if (newStatus === 'Completed' || newStatus === 'Cancelled') {
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
            // Reset select and hide indicator after delay
            const select = event.target;
            if (select) select.value = "";

            setTimeout(() => {
                refreshIndicator.style.display = 'none';
            }, 2000);
        });
}

// Add this function for auto-loading
function refreshTableData() {
    fetch('get_inquiries.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('.customer-table tbody');
            tbody.innerHTML = '';

            data.forEach(customer => {
                const row = document.createElement('tr');
                row.innerHTML = `
    <td>${escapeHtml(customer.customer_name)}</td>
    <td>${escapeHtml(customer.inquiry_date)}</td>
    <td class="status-cell-${escapeHtml(customer.customer_name)}">
        ${escapeHtml(customer.status)}
    </td>
    <td>${escapeHtml(customer.event_package)}</td>
    <td>
        <select class="status-select" 
                onchange="updateStatus('${escapeHtml(customer.customer_name)}', this.value)">
            <option value="">Change Status</option>
            <option value="Pending">Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Ongoing">Ongoing</option>
            <option value="Validating">Validating</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
        </select>
    </td>
`;
                tbody.appendChild(row);
            });
        })
        .catch(error => console.error('Error:', error));
}

// Helper function to escape HTML
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Add auto-refresh every 30 seconds
setInterval(refreshTableData, 30000);