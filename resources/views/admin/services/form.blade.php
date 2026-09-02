{{-- [SISTEM KUA] Form tambah/ubah layanan. --}}
@php $editing = $service->exists; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ $editing ? 'Ubah Layanan' : 'Tambah Layanan' }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            <x-admin-tabs />
            <x-alert />

            <form method="POST"
                  action="{{ $editing ? route('admin.services.update', $service) : route('admin.services.store') }}"
                  class="space-y-5 rounded-lg bg-white p-6 shadow-sm">
                @csrf
                @if ($editing) @method('PUT') @endif

                <div>
                    <x-input-label for="name" value="Nama layanan" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                  :value="old('name', $service->name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" value="Deskripsi" />
                    <textarea id="description" name="description" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >{{ old('description', $service->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="duration" value="Durasi layanan (menit)" />
                        <x-text-input id="duration" name="duration" type="number" min="5" max="480" step="5"
                                      class="mt-1 block w-full" :value="old('duration', $service->duration)" required />
                        <x-input-error :messages="$errors->get('duration')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="fee" value="Biaya (Rp)" />
                        <x-text-input id="fee" name="fee" type="number" min="0" step="1000"
                                      class="mt-1 block w-full" :value="old('fee', (int) $service->fee)" required />
                        <p class="mt-1 text-xs text-gray-500">Isi 0 bila layanan gratis.</p>
                        <x-input-error :messages="$errors->get('fee')" class="mt-2" />
                    </div>
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">Aktif (muncul di form reservasi warga)</span>
                </label>

                <div class="flex items-center gap-3 border-t border-gray-100 pt-4">
                    <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Simpan' }}</x-primary-button>
                    <a href="{{ route('admin.services.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
