{{-- [SISTEM KUA] Pengaturan kanal WhatsApp + balasan otomatis. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-stone-800">WhatsApp</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            <x-admin-tabs />
            <x-alert />

            {{-- Status sambungan --}}
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-stone-500">Driver aktif</p>
                    <p class="mt-1 font-semibold text-stone-900">{{ $driverName }}</p>
                    @if ($driver !== 'cloud')
                        <p class="mt-2 text-xs text-amber-700">
                            Mode uji coba — pesan tidak benar-benar dikirim ke WhatsApp.
                        </p>
                    @elseif (! $enabled)
                        <p class="mt-2 text-xs text-rose-700">Kanal dimatikan (WHATSAPP_ENABLED=false).</p>
                    @else
                        <p class="mt-2 text-xs text-kua-700">Terhubung ke Meta.</p>
                    @endif
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-stone-500">Pesan masuk / keluar</p>
                    <p class="mt-1 text-3xl font-bold text-stone-900">{{ $inboundCount }} / {{ $outboundCount }}</p>
                    @if ($failedCount > 0)
                        <p class="mt-2 text-xs text-rose-700">{{ $failedCount }} gagal terkirim.</p>
                    @endif
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-stone-500">Nomor WA KUA</p>
                    <p class="mt-1 font-semibold text-stone-900">
                        {{ $contactNumber ? \App\Support\PhoneNumber::format($contactNumber) : 'Belum diatur' }}
                    </p>
                    <p class="mt-2 text-xs text-stone-500">Dipakai tombol "Chat WhatsApp" di halaman publik.</p>
                </div>
            </div>

            {{-- Daftar periksa kredensial + webhook --}}
            <div class="rounded-lg bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-semibold text-stone-800">Kesiapan Sambungan</h3>
                <ul class="space-y-1 text-sm">
                    @foreach ($kredensial as $label => $terisi)
                        <li class="flex items-center gap-2">
                            <span class="{{ $terisi ? 'text-kua-600' : 'text-stone-300' }}">{{ $terisi ? '✓' : '○' }}</span>
                            <span class="{{ $terisi ? 'text-stone-700' : 'text-stone-400' }}">{{ $label }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4 rounded-md border border-sky-200 bg-sky-50 p-3 text-xs text-sky-800">
                    <p class="font-medium">URL webhook yang didaftarkan di Meta:</p>
                    <p class="mt-1 break-all font-mono">{{ $webhookUrl }}</p>
                    <p class="mt-2">
                        Meta hanya bisa memanggil URL publik ber-HTTPS. Selama aplikasi jalan di
                        localhost, pakai tunnel seperti <span class="font-mono">ngrok http 8000</span>.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.whatsapp.test') }}" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-medium text-stone-500">Kirim pesan uji ke nomor</label>
                        <input type="text" name="to" value="{{ old('to') }}" placeholder="08123456789" required
                               class="rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">
                    </div>
                    <x-primary-button>Kirim Uji</x-primary-button>
                </form>
            </div>

            {{-- Balasan otomatis --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-stone-100 px-5 py-3">
                    <h3 class="font-semibold text-stone-800">Balasan Otomatis</h3>
                    <p class="mt-1 text-xs text-stone-500">
                        Diperiksa dari urutan terkecil. Bila tidak ada yang cocok, sistem memakai menu
                        angka bawaan (1 status reservasi, 2 jadwal &amp; layanan, 3 minta petugas).
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.whatsapp.replies.store') }}"
                      class="grid gap-4 border-b border-stone-100 bg-stone-50 p-5 sm:grid-cols-6">
                    @csrf
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-stone-500">Kata kunci</label>
                        <input type="text" name="keyword" value="{{ old('keyword') }}" required maxlength="100"
                               placeholder="mis. syarat nikah"
                               class="w-full rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-stone-500">Pencocokan</label>
                        <select name="match_type" class="w-full rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">
                            @foreach ($matchTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('match_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-stone-500">Urutan</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="9999"
                               class="w-full rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">
                    </div>
                    <div class="sm:col-span-2 sm:row-span-2">
                        <label class="mb-1 block text-xs font-medium text-stone-500">Isi balasan</label>
                        <textarea name="reply_body" rows="3" required maxlength="1000"
                                  class="w-full rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">{{ old('reply_body') }}</textarea>
                    </div>
                    <input type="hidden" name="is_active" value="1">
                    <div class="sm:col-span-4">
                        <x-primary-button>+ Tambah Balasan</x-primary-button>
                    </div>
                </form>

                <table class="min-w-full divide-y divide-stone-200 text-sm">
                    <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-5 py-3">Kata kunci</th>
                            <th class="px-5 py-3">Balasan</th>
                            <th class="px-5 py-3">Aktif</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($replies as $reply)
                            <tr class="hover:bg-stone-50">
                                <form method="POST" action="{{ route('admin.whatsapp.replies.update', $reply) }}" id="ar-{{ $reply->id }}">
                                    @csrf @method('PUT')
                                </form>
                                <td class="px-5 py-3 align-top">
                                    <input form="ar-{{ $reply->id }}" type="text" name="keyword" value="{{ $reply->keyword }}"
                                           maxlength="100"
                                           class="w-full rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">
                                    <div class="mt-2 flex gap-2">
                                        <select form="ar-{{ $reply->id }}" name="match_type"
                                                class="rounded-md border-stone-300 text-xs focus:border-kua-500 focus:ring-kua-500">
                                            @foreach ($matchTypes as $value => $label)
                                                <option value="{{ $value }}" @selected($reply->match_type === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input form="ar-{{ $reply->id }}" type="number" name="sort_order"
                                               value="{{ $reply->sort_order }}" min="0" max="9999" title="Urutan"
                                               class="w-20 rounded-md border-stone-300 text-xs focus:border-kua-500 focus:ring-kua-500">
                                    </div>
                                </td>
                                <td class="px-5 py-3 align-top">
                                    <textarea form="ar-{{ $reply->id }}" name="reply_body" rows="3" maxlength="1000"
                                              class="w-full rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">{{ $reply->reply_body }}</textarea>
                                </td>
                                <td class="px-5 py-3 align-top">
                                    <label class="flex items-center gap-2 text-xs text-stone-600">
                                        <input form="ar-{{ $reply->id }}" type="checkbox" name="is_active" value="1"
                                               @checked($reply->is_active)
                                               class="rounded border-stone-300 text-kua-600 focus:ring-kua-500">
                                        Aktif
                                    </label>
                                </td>
                                <td class="px-5 py-3 align-top">
                                    <div class="flex justify-end gap-2">
                                        <button form="ar-{{ $reply->id }}"
                                                class="rounded-md bg-kua-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-kua-700">
                                            Simpan
                                        </button>
                                        <form method="POST" action="{{ route('admin.whatsapp.replies.destroy', $reply) }}"
                                              onsubmit="return confirm('Hapus balasan otomatis ini?')">
                                            @csrf @method('DELETE')
                                            <button class="rounded-md border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-stone-500">Belum ada balasan otomatis.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Riwayat pesan --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-3">
                    <h3 class="font-semibold text-stone-800">20 Pesan Terakhir</h3>
                    <a href="{{ route('petugas.whatsapp.index') }}" class="text-xs text-kua-600 hover:underline">
                        Buka inbox koordinasi &rarr;
                    </a>
                </div>
                <table class="min-w-full divide-y divide-stone-200 text-sm">
                    <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-5 py-3">Waktu</th>
                            <th class="px-5 py-3">Arah</th>
                            <th class="px-5 py-3">Nomor</th>
                            <th class="px-5 py-3">Isi</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($messages as $message)
                            <tr class="hover:bg-stone-50">
                                <td class="px-5 py-3 whitespace-nowrap text-stone-500">{{ $message->time_ago }}</td>
                                <td class="px-5 py-3">
                                    <x-status-badge
                                        :color="$message->is_inbound ? 'bg-sky-100 text-sky-800' : 'bg-kua-100 text-kua-800'"
                                        :label="$message->is_inbound ? 'Masuk' : 'Keluar'" />
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    {{ $message->formatted_number }}
                                    @if ($message->user)
                                        <div class="text-xs text-stone-400">{{ $message->user->name }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-stone-700">{{ \Illuminate\Support\Str::limit($message->body, 90) }}</td>
                                <td class="px-5 py-3">
                                    @if ($message->status === \App\Models\WhatsAppMessage::STATUS_FAILED)
                                        <span class="text-xs text-rose-700" title="{{ $message->error }}">Gagal</span>
                                    @else
                                        <span class="text-xs text-stone-500">{{ $message->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-stone-500">Belum ada lalu lintas pesan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
