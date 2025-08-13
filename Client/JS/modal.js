
window.addEventListener('click', (event) => {

    const modal = document.querySelector('.pop-up-modal');

    const openModal = () => {
        modal.classList.remove('d-none');
    }

    const closeModal = () => {
        modal.classList.add('d-none');
    }

    if (event.target.matches('.close-modal') ) {
        event.preventDefault();
        closeModal();
    }

    if(event.target.matches('.pop-up-modal-js') && !isLoggedIn) {
        event.preventDefault();
        openModal();
    }
});