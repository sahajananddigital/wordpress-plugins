document.addEventListener('DOMContentLoaded', function() {
    if (!wscInquiry.enabled) return;

    const observer = new MutationObserver(function(mutations) {
        // defined in localization
        const checkoutBtnText = wscInquiry.checkoutBtnText; 
        const orderBtnText = wscInquiry.orderBtnText;

        // Block Cart Button
        // Selector: .wc-block-cart__submit-button or .wc-block-components-checkout-button
        const cartButtons = document.querySelectorAll('.wc-block-cart__submit-button, .wc-block-components-checkout-button');
        cartButtons.forEach(btn => {
            if (btn.innerText !== checkoutBtnText) {
                // Check if it matches likely standard text to avoid overwriting random buttons? 
                // Mostly safe due to specific classes.
                // We use span if present, else direct text
                const span = btn.querySelector('.wc-block-components-button__text');
                if (span) {
                    if (span.innerText !== checkoutBtnText) span.innerText = checkoutBtnText;
                } else {
                    btn.innerText = checkoutBtnText;
                }
            }
        });

        // Block Checkout "Place Order" Button
        // Selector: .wc-block-components-checkout-place-order-button
        const placeOrderButtons = document.querySelectorAll('.wc-block-components-checkout-place-order-button');
        placeOrderButtons.forEach(btn => {
             // It might have a label inside
             const label = btn.querySelector('.wc-block-components-button__text');
             if (label) {
                 if (label.innerText !== orderBtnText) label.innerText = orderBtnText;
             } else {
                 if (btn.innerText !== orderBtnText) btn.innerText = orderBtnText;
             }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
