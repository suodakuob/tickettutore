<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\FootballMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode; 
use Barryvdh\DomPDF\Facade as PDF;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = auth()->user()->tickets()
            ->with(['match', 'payment'])
            ->latest()
            ->get();

        return view('tickets.index', compact('tickets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'match_id' => 'required|exists:matches,id',
            'quantity' => 'required|integer|min:1|max:10',
        ]);
    
        $match = FootballMatch::findOrFail($request->match_id);
    
        if ($match->available_tickets < $request->quantity) {
            return back()->with('error', 'Not enough tickets available.');
        }
    
        $tickets = [];
        for ($i = 0; $i < $request->quantity; $i++) {
            $ticket = Ticket::create([
                'user_id' => auth()->id(),
                'match_id' => $match->id,
                'price' => $match->ticket_price,
                'status' => 'pending',
                'ticket_number' => 'TIX-' . Str::random(10),
            ]);
    
            $qrData = json_encode([
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'match' => "{$match->home_team} vs {$match->away_team}",
                'stadium' => $match->stadium,
                'date' => $match->match_date,
                'time' => \Carbon\Carbon::parse($match->match_date)->format('g:i A'),
                'price' => $match->ticket_price,
                'ticket_number' => $ticket->ticket_number,
            ]);
    
            $qrPath = 'qrcodes/' . $ticket->ticket_number . '.png';
            QrCode::format('png')->size(300)->generate($qrData, storage_path("app/public/{$qrPath}"));
    
            $ticket->update(['qr_code' => $qrPath]);
            $tickets[] = $ticket;
        }
    
        return redirect()->route('my-tickets')->with('success', 'Booking successful! You have booked ' . $request->quantity . ' ticket(s) for ' . $match->home_team . ' vs ' . $match->away_team . '. Check your tickets below.');
    }

    public function show($ticketId)
    {
        $ticket = Ticket::with('match')->findOrFail($ticketId);
        return view('matches.show', compact('ticket'));
    }

    public function generatePdf($ticketId)
    {
        $ticket = Ticket::with(['match'])->findOrFail($ticketId);
        $match = $ticket->match;
        $user = auth()->user();
    
        $qrData = json_encode([
            'name' => $user->name,
            'email' => $user->email,
            'match' => "{$match->home_team} vs {$match->away_team}",
            'stadium' => $match->stadium,
            'date' => $match->match_date,
            'time' => \Carbon\Carbon::parse($match->match_date)->format('g:i A'),
            'price' => $match->ticket_price,
            'ticket_number' => $ticket->ticket_number,
        ]);
    
        $qrPath = storage_path("app/public/qrcodes/{$ticket->ticket_number}.png");
        QrCode::format('png')->size(300)->generate($qrData, $qrPath);

        // makaybghich ydoz lfichier 
        $pdfContent = view('tickets.pdf', compact('ticket', 'match', 'user'))->render();
        $pdf = PDF::loadHTML($pdfContent);
        
        return $pdf->download('ticket_' . $ticket->ticket_number . '.pdf');
    }
}