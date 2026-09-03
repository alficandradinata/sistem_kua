{{-- [SISTEM KUA] Navigasi utama: bilah hijau institusional + garis emas. --}}
<nav x-data="{ open: false }" class="bg-kua-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                {{-- Lambang + nama instansi --}}
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <x-application-logo class="h-8 w-auto text-emas-300" />
                        <span class="hidden font-display text-base font-semibold tracking-tight text-white sm:block">
                            Reservasi Antrean KUA
                        </span>
                    </a>
                </div>

                {{-- Menu per peran --}}
                <div class="hidden sm:ms-10 sm:flex sm:space-x-8">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-nav-link>
                    @if (auth()->user()->hasRole(['petugas', 'admin']))
                        <x-nav-link :href="route('petugas.dashboard')" :active="request()->routeIs('petugas.*')">
                            Petugas
                        </x-nav-link>
                    @endif
                    @if (auth()->user()->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                            Admin
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:ms-6 sm:flex sm:items-center">
                {{-- Lonceng notifikasi in-app --}}
                @php $unread = auth()->user()->unreadNotificationCount(); @endphp
                <a href="{{ route('notifications.index') }}" title="Notifikasi"
                   class="relative me-2 rounded-lg p-2 text-kua-100 transition hover:bg-white/10 hover:text-white
                          {{ request()->routeIs('notifications.*') ? 'bg-white/10 text-white' : '' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                    @if ($unread > 0)
                        <span class="absolute -right-0.5 -top-0.5 inline-flex min-w-[1.15rem] items-center justify-center
                                     rounded-full bg-emas-400 px-1 text-[0.65rem] font-bold leading-4 text-kua-950">
                            {{ $unread > 9 ? '9+' : $unread }}
                        </span>
                    @endif
                </a>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium leading-4
                                       text-kua-100 transition hover:bg-white/10 hover:text-white focus:outline-none">
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="ms-1.5 h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="border-b border-stone-100 px-4 py-3">
                            <p class="text-sm font-semibold text-stone-800">{{ Auth::user()->name }}</p>
                            <p class="mt-0.5 truncate text-xs text-stone-500">{{ Auth::user()->email }}</p>
                            <p class="mt-1.5 inline-block rounded-full bg-kua-50 px-2 py-0.5 text-[0.7rem] font-semibold uppercase tracking-wide text-kua-700">
                                {{ Auth::user()->role_label }}
                            </p>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                Keluar
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Hamburger --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                        class="inline-flex items-center justify-center rounded-lg p-2 text-kua-100
                               transition hover:bg-white/10 hover:text-white focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Menu ringkas (mobile) --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-white/10 sm:hidden">
        <div class="space-y-1 py-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Dashboard
            </x-responsive-nav-link>
            @if (auth()->user()->hasRole(['petugas', 'admin']))
                <x-responsive-nav-link :href="route('petugas.dashboard')" :active="request()->routeIs('petugas.*')">
                    Petugas
                </x-responsive-nav-link>
            @endif
            @if (auth()->user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                    Admin
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="border-t border-white/10 py-4">
            <div class="px-4">
                <div class="text-base font-semibold text-white">{{ Auth::user()->name }}</div>
                <div class="text-sm text-kua-200">{{ Auth::user()->email }}</div>
                <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-emas-300">
                    {{ Auth::user()->role_label }}
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                    Notifikasi
                    @if ($unread = auth()->user()->unreadNotificationCount())
                        <span class="ms-1 rounded-full bg-emas-400 px-1.5 text-xs font-bold text-kua-950">
                            {{ $unread }}
                        </span>
                    @endif
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('profile.edit')">Profil</x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        Keluar
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>

    {{-- Garis rambut emas: pemisah navigasi dari isi halaman --}}
    <div class="garis-emas h-px"></div>
</nav>
