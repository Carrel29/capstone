window.addEventListener('DOMContentLoaded', () => {
    
    window.addEventListener('click', (e) => {

        if (e.target.value === 'Gcash') {
            const Gcash = document.querySelector('.gcash');

            Gcash.classList.remove('d-none');
            Gcash.classList.add('d-flex');
        }

        if (e.target.matches(".payment-confirm")) {
            const payment = document.querySelector('.payment');

            payment.classList.remove('d-none');
            payment.classList.add('d-flex');
        }

        if (e.target.matches(".payment-proceed") && document.querySelector("#refId").value) {
            
            
        }

    });
});