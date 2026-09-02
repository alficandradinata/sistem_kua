<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AutoReplyRequest;
use App\Models\AutoReply;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppGateway;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * [SISTEM KUA] Pengaturan kanal WhatsApp: status koneksi, riwayat pesan,
 * dan balasan otomatis yang dikelola admin. Lihat PROGRESS.md.
 */
class WhatsAppController extends Controller
{
    public function index(WhatsAppGateway $gateway): View
    {
        return view('admin.whatsapp.index', [
            'driver' => config('whatsapp.driver'),
            'driverName' => $gateway->name(),
            'enabled' => (bool) config('whatsapp.enabled'),
            'kredensial' => $this->cekKredensial(),
            'webhookUrl' => url('/api/whatsapp/webhook'),
            'contactNumber' => config('whatsapp.contact_number'),
            'replies' => AutoReply::ordered()->get(),
            'matchTypes' => AutoReply::MATCH_TYPES,
            'messages' => WhatsAppMessage::with('user')->latestFirst()->limit(20)->get(),
            'inboundCount' => WhatsAppMessage::inbound()->count(),
            'outboundCount' => WhatsAppMessage::outbound()->count(),
            'failedCount' => WhatsAppMessage::where('status', WhatsAppMessage::STATUS_FAILED)->count(),
        ]);
    }

    /**
     * Kirim pesan uji, supaya admin tahu kredensialnya benar sebelum dipakai warga.
     */
    public function test(Request $request, WhatsAppGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', 'string', 'max:20'],
        ], [], ['to' => 'nomor tujuan']);

        $nomor = PhoneNumber::normalize($data['to']);

        if (! $nomor) {
            return back()->withErrors(['to' => 'Nomor tujuan tidak valid.']);
        }

        $pesan = $gateway->sendText(
            $nomor,
            'Pesan uji dari Sistem Reservasi Antrean KUA. Bila Anda menerima ini, sambungan WhatsApp sudah berfungsi.',
        );

        return $pesan->status === WhatsAppMessage::STATUS_SENT
            ? back()->with('status', "Pesan uji dikirim ke {$pesan->formatted_number}.")
            : back()->withErrors(['to' => 'Gagal mengirim: '.$pesan->error]);
    }

    // --- Balasan otomatis ---

    public function storeReply(AutoReplyRequest $request): RedirectResponse
    {
        AutoReply::create($request->validated());

        return back()->with('status', 'Balasan otomatis ditambahkan.');
    }

    public function updateReply(AutoReplyRequest $request, AutoReply $autoReply): RedirectResponse
    {
        $autoReply->update($request->validated());

        return back()->with('status', 'Balasan otomatis diperbarui.');
    }

    public function destroyReply(AutoReply $autoReply): RedirectResponse
    {
        $autoReply->delete();

        return back()->with('status', 'Balasan otomatis dihapus.');
    }

    /**
     * Kredensial mana yang belum diisi — ditampilkan sebagai daftar periksa.
     *
     * @return array<string, bool>
     */
    private function cekKredensial(): array
    {
        return [
            'Nomor kontak (WHATSAPP_CONTACT_NUMBER)' => (bool) config('whatsapp.contact_number'),
            'Phone Number ID' => (bool) config('whatsapp.cloud.phone_number_id'),
            'Access Token' => (bool) config('whatsapp.cloud.token'),
            'App Secret' => (bool) config('whatsapp.cloud.app_secret'),
            'Verify Token' => (bool) config('whatsapp.cloud.verify_token'),
        ];
    }
}
