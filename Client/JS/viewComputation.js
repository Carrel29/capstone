window.addEventListener('DOMContentLoaded', () => {
    
    window.addEventListener('click', (e) => {

        if(e.target.maches(".view-computation")) {
            const computation = document.querySelector('.computation');

            computation.classList.remove('d-none');
            computation.classList.add('d-flex');
        }
    });
});