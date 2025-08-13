
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

    if(event.target.matches('.open-verify-modal')) {
        event.preventDefault();

        const refNo =  document.querySelector('#ref_no');
        const salesId = document.querySelector('#sales_id');

        const dataSalesId = event.target.getAttribute('data-id');
        const dataRefNo = event.target.getAttribute('data-ref');
        const dataTotal = event.target.getAttribute('data-total');
        const dataAmmount = event.target.getAttribute('data-paid');
        
        document.querySelector('.data-refno').innerHTML = '<strong>Ref No:</strong> ' + dataRefNo;
        document.querySelector('.data-pending-ammount').innerHTML = '<strong>Pending Amount:</strong> ₱' + (dataTotal - dataAmmount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.querySelector('#totalAmmount').value = (dataTotal - dataAmmount);

        refNo.value = dataRefNo;
        salesId.value = dataSalesId;

        openModal();
    }
});