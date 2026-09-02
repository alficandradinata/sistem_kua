{{-- [SISTEM KUA] Form tambah/ubah akun. --}}
@php $editing = $user->exists; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ $editing ? 'Ubah Akun' : 'Tambah Akun' }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            <x-admin-tabs />
            <x-alert />

            <form method="POST"
                  action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}"
                  class="space-y-5 rounded-lg bg-white p-6 shadow-sm">
                @csrf
                @if ($editing) @method('PUT') @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="name" value="Nama lengkap" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                      :value="old('name', $user->name)" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                      :value="old('email', $user->email)" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="phone" value="No. HP" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                                      :value="old('phone', $user->phone)" placeholder="08xxxxxxxxxx" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="role" value="Peran" />
                        <select id="role" name="role" required
                                @disabled($editing && $user->is(auth()->user()))
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100">
                            @foreach (App\Models\User::ROLES as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($editing && $user->is(auth()->user()))
                            <input type="hidden" name="role" value="{{ $user->role }}">
                            <p class="mt-1 text-xs text-gray-500">Peran akun sendiri tidak bisa diubah.</p>
                        @endif
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-5 border-t border-gray-100 pt-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="password" :value="$editing ? 'Kata sandi baru (opsional)' : 'Kata sandi'" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                                      autocomplete="new-password" :required="! $editing" />
                        @if ($editing)
                            <p class="mt-1 text-xs text-gray-500">Kosongkan bila tidak ingin mengubah sandi.</p>
                        @endif
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="Ulangi kata sandi" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                                      class="mt-1 block w-full" autocomplete="new-password" :required="! $editing" />
                    </div>
                </div>

                <div class="flex items-center gap-3 border-t border-gray-100 pt-4">
                    <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Buat Akun' }}</x-primary-button>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
