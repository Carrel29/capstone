document.addEventListener('DOMContentLoaded', function () {
    const statusFilter = document.getElementById('statusFilter');
    const paymentFilter = document.getElementById('paymentFilter');
    const dateFilter = document.getElementById('dateFilter');
    const searchInput = document.getElementById('search');
    const tableRows = document.querySelectorAll('#bookingsTable tbody tr');

    function applyFilters() {
        const statusValue = statusFilter.value.toLowerCase();
        const paymentValue = paymentFilter.value.toLowerCase();
        const dateValue = dateFilter.value;
        const searchValue = searchInput.value.toLowerCase();

        tableRows.forEach(row => {
            if (row.cells.length < 2) return;

            const rowStatus = row.classList[0].replace('status-', '');
            const paymentStatus = row.querySelector('.payment-details .status-pending, .payment-details .status-partial, .payment-details .status-paid')?.className.replace('status-', '') || '';
            const eventDate = row.cells[3].textContent.match(/([A-Za-z]{3} \d{1,2}, \d{4})/)?.[0] || '';
            const searchText = row.textContent.toLowerCase();

            const statusMatch = statusValue === 'all' || rowStatus === statusValue;
            const paymentMatch = paymentValue === 'all' || paymentStatus === paymentValue;
            const dateMatch = !dateValue || (eventDate && new Date(eventDate).toISOString().split('T')[0] === dateValue);
            const searchMatch = !searchValue || searchText.includes(searchValue);

            row.style.display = (statusMatch && paymentMatch && dateMatch && searchMatch) ? '' : 'none';
        });
    }

    statusFilter.addEventListener('change', applyFilters);
    paymentFilter.addEventListener('change', applyFilters);
    dateFilter.addEventListener('change', applyFilters);
    searchInput.addEventListener('input', applyFilters);
});

function openStatusModal(bookingId, currentStatus) {
    document.getElementById('modalBookingId').value = bookingId;
    document.getElementById('down_payment_status').value = currentStatus;
    document.getElementById('statusModal').style.display = 'block';
    return false; // Prevent any default behavior
}

function closeModal() {
    document.getElementById('statusModal').style.display = 'none';
}

window.onclick = function (event) {
    const modal = document.getElementById('statusModal');
    if (event.target == modal) {
        closeModal();
    }
}