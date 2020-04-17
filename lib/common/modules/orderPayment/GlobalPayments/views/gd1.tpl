<form id="payment-form" action="/charge" method="get">
    <!-- Other input fields to capture relevant data -->
    <label for="billing-zip">Billing Zip Code</label>
    <input id="billing-zip" name="billing-zip" type="tel" />

    <!-- Target for the credit card form -->
    <div id="credit-card"></div>
</form>
<script src="/themes/basic/js/globalpayments-js/dist/globalpayments.js"></script>
<script>
    tl(function(){
    // Configure account
    GlobalPayments.configure({
        publicApiKey: "pkapi_cert_dNpEYIISXCGDDyKJiV"
    });

    // Create Form
    const cardForm = GlobalPayments.creditCard.form("#credit-card");

    // form-level event handlers. examples:
    cardForm.ready(() => {
        console.log("Registration of all credit card fields occurred");
    });
    cardForm.on("token-success", (resp) => {
        // add payment token to form as a hidden input
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "payment-reference";
        token.value = resp.paymentReference;

        // submit data to the integration's backend for processing
        const form = document.getElementById("payment-form");
        form.submit();
    });
    cardForm.on("token-error", (resp) => {
        // show error to the consumer
    });

    // field-level event handlers. example:
    cardForm.on("card-number", "register", () => {
        console.log("Registration of Card Number occurred");
    });
    });
</script>
