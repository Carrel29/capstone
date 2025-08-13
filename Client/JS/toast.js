const toast = document.querySelector('.toast');

const showToast = (message) => {
    toast.querySelector('.toast-body').textContent = message;
    toast.classList.remove('d-none');
    setTimeout(() => {
        hideToast();
    }, 3000);
}

const hideToast = () => {
    toast.classList.add('d-none');
}

if (message) {
    showToast(message);
}