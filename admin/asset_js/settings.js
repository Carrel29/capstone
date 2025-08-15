  // Open Modal
  function openModal(modalId) {
    document.getElementById(modalId).style.display = "block";
}

// Close Modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
}

// Apply Website Customization
function applyCustomization() {
    const color = document.getElementById('color-picker').value;
    const font = document.getElementById('font-picker').value;

    // Apply color and font to the website
    document.body.style.backgroundColor = color;
    document.body.style.fontFamily = font;

    alert("Customization applied!");
}
function saveCustomization() {
    const color = document.getElementById('color-picker').value;
    const font = document.getElementById('font-picker').value;

    fetch('save_customization.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `color=${encodeURIComponent(color)}&font=${encodeURIComponent(font)}`
    })
        .then(response => response.text())
        .then(data => {
            alert(data); // Show success message
            location.reload(); // Reload page to apply changes
        });
}