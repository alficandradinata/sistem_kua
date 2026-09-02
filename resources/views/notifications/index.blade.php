{{-- [SISTEM KUA] Kotak notifikasi in-app. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Notifikasi
                @if ($unreadCount > 0)
                    <span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-sm font-semibold text-red-700">
                        {{ $unreadCount }} baru
                    </span>
                @endif
            </h2>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf @method('PATCH')
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Tandai semua dibaca
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            <x-alert />

            {{-- Filter --}}
            <nav class="flex gap-1 border-b border-gray-200">
                @foreach (['all' => 'Semua', 'unread' => 'Belum dibaca'] as $value => $label)
                    <a href="{{ route('notifications.index', $value === 'all' ? [] : ['filter' => $value]) }}"
                       class="-mb-px border-b-2 px-4 py-2 text-sm font-medium transition
                              {{ $filter === $value
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="space-y-3">
                @forelse ($notifications as $notification)
                    <div class="rounded-lg border-l-4 bg-white p-4 shadow-sm
                                {{ $notification->is_read ? 'border-gray-200' : 'border-indigo-500' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="{{ $notification->is_read ? 'text-gray-600' : 'font-medium text-gray-900' }}">
                                    {{ $notification->message }}
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    {{ $notification->time_ago }} &middot; {{ $notification->type_label }}
                                    @unless ($notification->is_read)
                                        &middot; <span class="font-semibold text-indigo-600">Baru</span>
                                    @endunless
                                </p>
                            </div>

                            <div class="flex shrink-0 gap-2">
                                @unless ($notification->is_read)
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf @method('PATCH')
                                        <button class="rounded-md border border-indigo-300 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
                                            Tandai dibaca
                                        </button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('notifications.destroy', $notification) }}"
                                      onsubmit="return confirm('Hapus notifikasi ini?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg bg-white p-10 text-center text-gray-500 shadow-sm">
                        {{ $filter === 'unread' ? 'Tidak ada notifikasi yang belum dibaca.' : 'Belum ada notifikasi.' }}
                    </div>
                @endforelse
            </div>

            @if ($notifications->hasPages())
                <div>{{ $notifications->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
