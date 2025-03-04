<!DOCTYPE html>
<html>
<head>
    <title>Ticket PDF</title>
    <style>
        /* Style pour le PDF */
        body { font-family: Arial, sans-serif; }
    </style>
</head>
<body>
    <h1>Ticket Information</h1>
    <p><strong>Name:</strong> {{ $ticket->user->name }}</p>
    <p><strong>Match:</strong> {{ $match->home_team }} vs {{ $match->away_team }}</p>
    <p><strong>Stadium:</strong> {{ $match->stadium }}</p>
    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($match->match_date)->format('Y-m-d') }}</p>
    <p><strong>Time:</strong> {{ \Carbon\Carbon::parse($match->match_date)->format('g:i A') }}</p>
    <p><strong>Ticket Price:</strong> {{ $match->ticket_price }}</p>
    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($qrCodePath)) }}" alt="QR Code" />
</body>
</html>
