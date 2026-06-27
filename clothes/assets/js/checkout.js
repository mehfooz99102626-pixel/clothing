/**
 * checkout.js - Controller for checkout payment gateways simulation
 */

function togglePaymentSelection(method) {
    // Clear all selected card styles
    document.querySelectorAll('.payment-method-card').forEach(card => {
        card.classList.remove('selected');
        // Uncheck inside radio (for click fallback)
        const radio = card.querySelector('input[type="radio"]');
        if (radio && radio.value !== method) {
            radio.checked = false;
        }
    });

    // Mark current selected card
    const activeCard = document.getElementById(`label-${method}`);
    if (activeCard) {
        activeCard.classList.add('selected');
        const radio = activeCard.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const checkoutForm = document.getElementById('checkout-form');
    const simulationModal = document.getElementById('gateway-simulation-modal');

    if (checkoutForm && simulationModal) {
        const rzpContent = document.getElementById('razorpay-simulation-content');
        const paypalContent = document.getElementById('paypal-simulation-content');
        const successBtn = document.getElementById('simulate-success-btn');
        const failBtn = document.getElementById('simulate-fail-btn');

        checkoutForm.addEventListener('submit', (e) => {
            const selectedRadio = checkoutForm.querySelector('input[name="payment_method"]:checked');
            const selectedMethod = selectedRadio ? selectedRadio.value : 'cod';

            if (selectedMethod === 'razorpay' || selectedMethod === 'paypal') {
                e.preventDefault(); // Halt normal form post submission

                // Open overlay dialog
                simulationModal.style.display = 'flex';

                if (selectedMethod === 'razorpay') {
                    rzpContent.style.display = 'block';
                    paypalContent.style.display = 'none';
                } else {
                    rzpContent.style.display = 'none';
                    paypalContent.style.display = 'block';
                }
            }
        });

        // Set action handlers inside modal
        successBtn.addEventListener('click', () => {
            simulationModal.style.display = 'none';
            // Submit form to backend normally to record order with payment completed state
            checkoutForm.submit();
        });

        failBtn.addEventListener('click', () => {
            simulationModal.style.display = 'none';
            // Redirect to failure details page
            window.location.href = 'payment-failed.php';
        });
    }
});
