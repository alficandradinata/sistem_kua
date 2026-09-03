{{-- [SISTEM KUA] Inbox koordinasi WhatsApp untuk petugas. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-stone-800">Koordinasi WhatsApp</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            <x-petugas-tabs />
            <x-alert />

            <div class="grid gap-4 lg:grid-cols-3">
                {{-- Daftar percakapan --}}
                <div class="overflow-hidden rounded-lg bg-white shadow-sm lg:col-span-1">
                    <div class="border-b border-stone-100 px-4 py-3 text-sm font-semibold text-stone-700">
                        Percakapan
                    </div>
                    <ul class="divide-y divide-stone-100">
                        @forelse ($conversations as $percakapan)
                            <li>
                                <a href="{{ route('petugas.whatsapp.index', ['nomor' => $percakapan->wa_number]) }}"
                                   class="block px-4 py-3 transition hover:bg-stone-50
                                          {{ $activeNumber === $percakapan->wa_number ? 'bg-kua-50' : '' }}">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <span class="font-medium text-stone-900">
                                            {{ $percakapan->user?->name ?? \App\Support\PhoneNumber::format($percakapan->wa_number) }}
                                        </span>
                                        <span class="shrink-0 text-xs text-stone-400">{{ $percakapan->jumlah }} pesan</span>
                                    </div>
                                    @if ($percakapan->user)
                                        <div class="text-xs text-stone-400">{{ \App\Support\PhoneNumber::format($percakapan->wa_number) }}</div>
                                    @endif
                                    <p class="mt-1 truncate text-xs text-stone-500">{{ $percakapan->terakhir_pesan }}</p>
                                </a>
                            </li>
                        @empty
                            <li class="px-4 py-10 text-center text-sm text-stone-500">
                                Belum ada chat masuk.
                            </li>
                        @endforelse
                    </ul>
                </div>

                {{-- Isi percakapan --}}
                <div class="overflow-hidden rounded-lg bg-white shadow-sm lg:col-span-2">
                    @if (! $activeNumber)
                        <p class="px-5 py-16 text-center text-sm text-stone-500">
                            Pilih satu percakapan di kiri untuk membacanya.
                        </p>
                    @else
                        <div class="border-b border-stone-100 px-5 py-3">
                            <p class="font-semibold text-stone-900">
                                {{ $activeUser?->name ?? \App\Support\PhoneNumber::format($activeNumber) }}
                            </p>
                            <p class="text-xs text-stone-500">
                                {{ \App\Support\PhoneNumber::format($activeNumber) }}
                                @if ($activeUser)
                                    &middot; terdaftar sebagai {{ $activeUser->role_label }}
                                @else
                                    &middot; nomor belum terdaftar di sistem
                                @endif
                            </p>
                        </div>

                        {{-- Alpine (sudah dimuat Breeze) dipakai sekadar untuk membuka
                             percakapan pada pesan terbaru, seperti aplikasi chat. --}}
                        <div x-data x-init="$el.scrollTop = $el.scrollHeight"
                             class="max-h-[26rem] space-y-3 overflow-y-auto px-5 py-4">
                            @foreach ($messages as $message)
                                <div class="flex {{ $message->is_inbound ? 'justify-start' : 'justify-end' }}">
                                    <div class="max-w-[80%] rounded-lg px-3 py-2 text-sm
                                                {{ $message->is_inbound
                                                    ? 'bg-stone-100 text-stone-800'
                                                    : 'bg-kua-600 text-white' }}">
                                        <p class="whitespace-pre-line">{{ $message->body }}</p>
                                        <p class="mt-1 text-[10px] {{ $message->is_inbound ? 'text-stone-400' : 'text-kua-200' }}">
                                            {{ $message->created_at->locale('id')->translatedFormat('j M H:i') }}
                                            @if ($message->is_auto_reply)
                                                &middot; balasan otomatis
                                            @endif
                                            @if ($message->status === \App\Models\WhatsAppMessage::STATUS_FAILED)
                                                &middot; <span class="text-rose-200">gagal</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-stone-100 px-5 py-4">
                            @if ($canReply)
                                <form method="POST" action="{{ route('petugas.whatsapp.reply') }}" class="space-y-2">
                                    @csrf
                                    <input type="hidden" name="nomor" value="{{ $activeNumber }}">
                                    <textarea name="body" rows="3" required maxlength="1000"
                                              placeholder="Tulis balasan untuk warga…"
                                              class="w-full rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">{{ old('body') }}</textarea>
                                    <div class="flex justify-end">
                                        <x-primary-button>Kirim Balasan</x-primary-button>
                                    </div>
                                </form>
                            @else
                                <p class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                                    Jendela balasan {{ $windowHours }} jam sudah lewat. WhatsApp hanya mengizinkan
                                    template resmi di luar itu, jadi balasan bebas tidak bisa dikirim sampai warga
                                    mengirim pesan lagi.
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
