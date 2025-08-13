const toggleDropdown = document.querySelector('.dropdown');

window.addEventListener('click', (e) => {
    if(e.target.matches('.dropdown') || e.target.matches('.dropdown *')) {
        toggleDropdown.classList.toggle('active');
    }
});

