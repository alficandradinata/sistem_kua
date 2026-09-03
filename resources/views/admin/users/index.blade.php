{{-- [SISTEM KUA] Kelola akun & peran. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-stone-800">Master Data — Pengguna</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            <x-admin-tabs />
            <x-alert />

            <div class="flex flex-col gap-4 rounded-lg bg-white p-5 shadow-sm sm:flex-row sm:items-end sm:justify-between">
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-stone-500">Peran</label>
                        <select name="role" class="rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">
                            <option value="">Semua peran</option>
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-stone-500">Cari nama / email</label>
                        <input type="search" name="q" value="{{ $search }}"
                               class="rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">
                    </div>
                    <button class="rounded-md bg-stone-800 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-700">Cari</button>
                    <a href="{{ route('admin.users.index') }}" class="py-2 text-sm text-stone-500 hover:text-stone-800">Reset</a>
                </form>

                <a href="{{ route('admin.users.create') }}"
                   class="self-start rounded-md bg-kua-600 px-4 py-2 text-sm font-semibold text-white hover:bg-kua-700">
                    + Tambah Akun
                </a>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                            <tr>
                                <th class="px-5 py-3">Nama</th>
                                <th class="px-5 py-3">Kontak</th>
                                <th class="px-5 py-3">Peran</th>
                                <th class="px-5 py-3 text-center">Reservasi</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($users as $user)
                                <tr class="hover:bg-stone-50">
                                    <td class="px-5 py-3">
                                        <div class="font-medium text-stone-900">{{ $user->name }}</div>
                                        @if ($user->is(auth()->user()))
                                            <span class="text-xs text-kua-600">akun Anda</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-stone-600">
                                        <div>{{ $user->email }}</div>
                                        <div class="text-xs text-stone-400">{{ $user->phone ?? '—' }}</div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-status-badge
                                            :color="match ($user->role) {
                                                'admin' => 'bg-rose-100 text-rose-800',
                                                'petugas' => 'bg-sky-100 text-sky-800',
                                                default => 'bg-stone-100 text-stone-800',
                                            }"
                                            :label="$user->role_label" />
                                    </td>
                                    <td class="px-5 py-3 text-center">{{ $user->reservations_count }}</td>
                                    <td class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                               class="rounded-md border border-stone-300 px-3 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-50">
                                                Ubah
                                            </a>
                                            @unless ($user->is(auth()->user()))
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                      onsubmit="return confirm('Hapus akun {{ $user->name }}?')">
                                                    @csrf @method('DELETE')
                                                    <button @disabled($user->reservations_count > 0)
                                                        title="{{ $user->reservations_count > 0 ? 'Punya riwayat reservasi' : '' }}"
                                                        class="rounded-md px-3 py-1.5 text-xs font-semibold
                                                            {{ $user->reservations_count > 0
                                                                ? 'cursor-not-allowed border border-stone-200 text-stone-300'
                                                                : 'border border-rose-300 text-rose-700 hover:bg-rose-50' }}">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-10 text-center text-stone-500">Tidak ada pengguna yang cocok.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($users->hasPages())
                    <div class="border-t border-stone-100 px-5 py-3">{{ $users->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
