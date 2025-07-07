<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Park Ticket Payment</title>
    <script src="https://js.stripe.com/v3/"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-400 to-blue-600 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl p-8 max-w-md w-full">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">🏊‍♀️ AquaFun Water Park</h1>
            <p class="text-gray-600">Purchase your day pass</p>
        </div>

        <!-- Ticket Details -->
        <div class="bg-blue-50 rounded-lg p-4 mb-6">
            <div class="flex justify-between items-center">
                <span class="text-gray-700">Water Park Day Pass</span>
                <span class="text-xl font-bold text-blue-600">${{ number_format($amount ?? 50.00, 2) }}</span>
            </div>
            <p class="text-sm text-gray-500 mt-1">Access to all pools, slides, and attractions</p>
        </div>

        <!-- Error Display -->
        @if (isset($error) && $error)
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                {{ $error }}
            </div>
        @elseif ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        @if (!isset($error) || !$error)
        <!-- Payment Form -->
        <form id="payment-form" class="space-y-4">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order_id ?? '' }}">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Card Information</label>
                <div id="card-element" class="p-3 border border-gray-300 rounded-md bg-white">
                    <!-- Stripe Elements will create form elements here -->
                </div>
                <div id="card-errors" role="alert" class="text-red-600 text-sm mt-2"></div>
            </div>

            <button 
                id="submit-button" 
                type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-md transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span id="button-text">Pay ${{ number_format($amount ?? 50.00, 2) }}</span>
            </button>
        </form>
        @else
        <!-- Error State - Show Retry Button -->
        <div class="text-center">
            <a href="{{ route('payment.form') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md transition duration-200">
                Try Again
            </a>
        </div>
        @endif

        <div class="mt-6 text-center">
            <p class="text-xs text-gray-500">
                🔒 Secured by Stripe • Your payment information is encrypted and secure
            </p>
        </div>
    </div>

    @if (!isset($error) || !$error)
    <script>
        // Initialize Stripe
        const stripe = Stripe('{{ $publishable_key ?? '' }}');
        const elements = stripe.elements();

        // Custom styling for the card element
        const style = {
            base: {
                fontSize: '16px',
                color: '#424770',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
            invalid: {
                color: '#9e2146',
            },
        };

        // Create an instance of the card Element
        const card = elements.create('card', { style: style });
        card.mount('#card-element');

        // Handle real-time validation errors from the card Element
        card.on('change', ({ error }) => {
            const displayError = document.getElementById('card-errors');
            if (error) {
                displayError.textContent = error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Handle form submission
        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('submit-button');
        const buttonText = document.getElementById('button-text');
        const spinner = document.getElementById('spinner');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            // Disable the submit button and show loading state
            submitButton.disabled = true;
            buttonText.textContent = 'Processing...';
            spinner.classList.remove('hidden');

            try {
                const { error, paymentIntent } = await stripe.confirmCardPayment('{{ $client_secret ?? '' }}', {
                    payment_method: {
                        card: card,
                    }
                });

                if (error) {
                    // Show error to customer
                    const errorElement = document.getElementById('card-errors');
                    errorElement.textContent = error.message;
                    
                    // Re-enable the submit button
                    submitButton.disabled = false;
                    buttonText.textContent = 'Pay ${{ number_format($amount ?? 50.00, 2) }}';
                    spinner.classList.add('hidden');
                } else {
                    // Payment succeeded, redirect to success page
                    window.location.href = '/payment/success?payment_intent=' + paymentIntent.id;
                }
            } catch (err) {
                console.error('Payment error:', err);
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = 'An unexpected error occurred. Please try again.';
                
                // Re-enable the submit button
                submitButton.disabled = false;
                buttonText.textContent = 'Pay ${{ number_format($amount ?? 50.00, 2) }}';
                spinner.classList.add('hidden');
            }
        });
    </script>
    @endif
</body>
</html>
