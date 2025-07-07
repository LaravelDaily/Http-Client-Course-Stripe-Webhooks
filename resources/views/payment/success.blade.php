<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - Water Park</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">Payment Success!</h1>
                <p class="mt-2 text-gray-600">{{ $message }}</p>
                
                @if($order && $order->isCompleted() && $order->tickets)
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <h3 class="font-semibold text-blue-900">Your Tickets:</h3>
                        <div class="mt-2 space-y-2">
                            @foreach($order->tickets as $ticket)
                                <div class="text-sm bg-white p-2 rounded border">
                                    <strong>{{ $ticket['ticket_code'] }}</strong><br>
                                    Type: {{ $ticket['ticket_type'] }}<br>
                                    Valid: {{ $ticket['valid_date'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <div class="mt-6">
                    <a href="{{ route('payment.form') }}" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200">
                        Book Another Ticket
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 