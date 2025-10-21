// Admin entrypoint for Exchange Rate Plugin
import './styles.scss';

// Handle native dialog for exchange rate synchronization
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('dialog-synchronize-exchange-rates');
    const triggerButton = document.getElementById('dialog-synchronize-exchange-rates-trigger');
    const cancelButton = document.getElementById('dialog-synchronize-exchange-rates-cancel');

    if (dialog && triggerButton) {
        // Open dialog when trigger button is clicked
        triggerButton.addEventListener('click', () => {
            dialog.showModal();
        });

        // Close dialog when cancel button is clicked
        if (cancelButton) {
            cancelButton.addEventListener('click', () => {
                dialog.close();
            });
        }

        // Close dialog when clicking outside (on the backdrop)
        dialog.addEventListener('click', (event) => {
            const rect = dialog.getBoundingClientRect();
            const isInDialog = (
                rect.top <= event.clientY &&
                event.clientY <= rect.top + rect.height &&
                rect.left <= event.clientX &&
                event.clientX <= rect.left + rect.width
            );

            if (!isInDialog) {
                dialog.close();
            }
        });
    }
});
