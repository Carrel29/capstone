// ===============================
// Tab functionality
// ===============================
function openTab(tabName) {
    // Hide all tab content
    var tabContents = document.getElementsByClassName('tab-content');
    for (var i = 0; i < tabContents.length; i++) {
        tabContents[i].style.display = 'none';
    }
    
    // Remove active class from all tabs
    var tabs = document.getElementsByClassName('tab');
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].className = tabs[i].className.replace(" active", "");
    }
    
    // Show the current tab and set as active
    document.getElementById(tabName).style.display = 'block';
    document.querySelector(".tab[onclick=\"openTab('" + tabName + "')\"]").className += " active";
}

// ===============================
// Modal functions (Edit User)
// ===============================
function editUser(id, firstName, lastName, email, phone) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_first_name').value = firstName;
    document.getElementById('edit_last_name').value = lastName;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone;   // ✅ Added
    document.getElementById('edit_password').value = '';   // Keep blank

    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// ===============================
// Confirmation Modals (Archive / Restore / Delete)
// ===============================
function confirmArchive(userId) {
    document.getElementById('confirmTitle').innerHTML = 'Archive Admin';
    document.getElementById('confirmMessage').innerHTML = 'Are you sure you want to archive this admin? This will remove their access to the system.';
    document.getElementById('confirm_action').value = 'archive_user';
    document.getElementById('confirm_id').name = 'user_id';  // ✅ archive needs user_id
    document.getElementById('confirm_id').value = userId;
    document.getElementById('confirmButton').style.backgroundColor = '#e74c3c';
    document.getElementById('confirmModal').style.display = 'flex';
}

function confirmRestore(archiveId) {
    document.getElementById('confirmTitle').innerHTML = 'Restore Admin';
    document.getElementById('confirmMessage').innerHTML = 'Are you sure you want to restore this admin?';
    document.getElementById('confirm_action').value = 'restore_user';
    document.getElementById('confirm_id').name = 'archive_id'; // ✅ restore needs archive_id
    document.getElementById('confirm_id').value = archiveId;
    document.getElementById('confirmButton').style.backgroundColor = '#27ae60';
    document.getElementById('confirmModal').style.display = 'flex';
}

function confirmDelete(archiveId) {
    document.getElementById('confirmTitle').innerHTML = 'Permanently Delete Admin';
    document.getElementById('confirmMessage').innerHTML = 'Are you sure you want to permanently delete this admin? This action cannot be undone.';
    document.getElementById('confirm_action').value = 'permanently_delete';
    document.getElementById('confirm_id').name = 'archive_id'; // ✅ delete needs archive_id
    document.getElementById('confirm_id').value = archiveId;
    document.getElementById('confirmButton').style.backgroundColor = '#c0392b';
    document.getElementById('confirmModal').style.display = 'flex';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

// ===============================
// Close modals when clicking outside
// ===============================
window.onclick = function(event) {
    var editModal = document.getElementById('editModal');
    var confirmModal = document.getElementById('confirmModal');
    
    if (event.target === editModal) {
        editModal.style.display = 'none';
    }
    
    if (event.target === confirmModal) {
        confirmModal.style.display = 'none';
    }
}
