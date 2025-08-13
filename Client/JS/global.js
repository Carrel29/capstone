const noTextInput =  document.querySelector('#Phonenumber');

noTextInput.addEventListener('keypress', (e) => {
    if (!/[0-9]/.test(e.key) || noTextInput.value.length >= 11) {
        e.preventDefault();
    }
});

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

