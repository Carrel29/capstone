document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('historyModal');
    const historyBtn = document.getElementById('historyButton');
    const closeBtn = document.querySelector('.close-modal');
    const clearBtn = document.getElementById('clearArchiveBtn');

    // Show modal
    historyBtn.onclick = function () {
        modal.style.display = 'block';
    };

    // Close modal
    closeBtn.onclick = function () {
        modal.style.display = 'none';
    };

    // Close when clicking outside
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };

    // Clear archive functionality
    clearBtn.onclick = function () {
        if (confirm('Are you sure you want to clear all archived inquiries? This action cannot be undone.')) {
            fetch('clear_archive.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('.modal-table tbody').innerHTML = '';
                        alert('All archived inquiries have been cleared.');
                    } else {
                        alert(data.message || 'Failed to clear archived inquiries.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while clearing the archive.');
                });
        }
    };
});