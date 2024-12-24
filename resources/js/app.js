document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('confirm-resignation', event => {
        const loans = event.detail.loans;
        let message = "The employee has the following active loans:\n\n";

        loans.forEach(loan => {
            message += `Bank: ${loan.bank}\nAmount: ${loan.amount}\nStart Date: ${loan.start_date}\nEnd Date: ${loan.end_date}\n\n`;
        });

        message += "Do you want to proceed with the resignation? An email will be sent to the bank.";

        if (confirm(message)) {
            Livewire.emit('saveResignationConfirmed'); // حدث لإكمال العملية
        }
    });
});
