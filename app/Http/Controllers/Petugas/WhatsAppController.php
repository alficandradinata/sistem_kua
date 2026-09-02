<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppGateway;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * [SISTEM KUA] Inbox koordinasi lapangan: petugas membaca chat warga yang masuk
 * dan membalas manual. Lihat PROGRESS.md.
 */
class WhatsAppController extends Controller
{
    public function index(Request $request): View
    {
        $nomor = PhoneNumber::normalize($request->string('nomor')->toString());

        return view('petugas.whatsapp.index', [
            'conversations' => $this->daftarPercakapan(),
            'activeNumber' => $nomor,
            'messages' => $nomor
                ? WhatsAppMessage::forNumber($nomor)->orderBy('created_at')->get()
                : collect(),
            'activeUser' => $nomor ? User::findByPhone($nomor) : null,
            'canReply' => $nomor ? WhatsAppMessage::withinSessionWindow($nomor) : false,
            'windowHours' => config('whatsapp.session_window_hours', 24),
        ]);
    }

    public function reply(Request $request, WhatsAppGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'nomor' => ['required', 'string', 'max:20'],
            'body' => ['required', 'string', 'max:1000'],
        ], [], ['nomor' => 'nomor tujuan', 'body' => 'isi balasan']);

        $nomor = PhoneNumber::normalize($data['nomor']);

        if (! $nomor) {
            return back()->withErrors(['nomor' => 'Nomor tujuan tidak valid.']);
        }

        if (! WhatsAppMessage::withinSessionWindow($nomor)) {
            return back()->withErrors([
                'body' => 'Jendela balasan '.config('whatsapp.session_window_hours', 24)
                    .' jam sudah lewat. WhatsApp hanya mengizinkan template resmi di luar itu — '
                    .'hubungi warga lewat telepon, atau tunggu warga mengirim pesan lagi.',
            ]);
        }

        $pesan = $gateway->sendText($nomor, $data['body']);

        return $pesan->status === WhatsAppMessage::STATUS_SENT
            ? redirect()->route('petugas.whatsapp.index', ['nomor' => $nomor])
                ->with('status', 'Balasan terkirim.')
            : back()->withErrors(['body' => 'Gagal mengirim: '.$pesan->error]);
    }

    /**
     * Satu baris per nomor: pesan terakhir + jumlah pesan masuk.
     */
    private function daftarPercakapan()
    {
        return WhatsAppMessage::query()
            ->select('wa_number')
            ->selectRaw('MAX(created_at) as terakhir')
            ->selectRaw('COUNT(*) as jumlah')
            ->groupBy('wa_number')
            ->orderByDesc(DB::raw('MAX(created_at)'))
            ->limit(50)
            ->get()
            ->map(function ($baris) {
                $baris->user = User::findByPhone($baris->wa_number);
                $baris->terakhir_pesan = WhatsAppMessage::forNumber($baris->wa_number)
                    ->latestFirst()->value('body');

                return $baris;
            });
    }
}
