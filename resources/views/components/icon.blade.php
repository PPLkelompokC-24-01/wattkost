{{-- Ikon garis 16×16 dari desain (14 ikon). Pemakaian: <x-icon name="plug" class="size-4" /> --}}
@props(['name' => 'bolt'])

<svg viewBox="0 0 16 16" {{ $attributes->class('size-5 shrink-0') }} fill="none" stroke="currentColor"
    stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('home')
            <path d="m2 7 6-5 6 5v7H2V7Z" />
            <path d="M6 14V9h4v5" />
        @break

        @case('plug')
            <path d="M5 2v4M11 2v4M3.5 6h9v2A4.5 4.5 0 0 1 8 12.5V15" />
            <path d="M6 9h4" />
        @break

        @case('calendar')
            <rect x="2" y="3.5" width="12" height="10.5" rx="2" />
            <path d="M5 2v3M11 2v3M2 7h12M5 10h2" />
        @break

        @case('receipt')
            <path d="M3 2h10v13l-2-1.2L9 15l-2-1.2L5 15l-2-1.2V2Z" />
            <path d="M6 6h4M6 9h4" />
        @break

        @case('chart')
            <path d="M2 14V3M2 14h12" />
            <path d="m4 11 3-3 2 2 4-5" />
            <circle cx="13" cy="5" r="1" fill="#C9F24A" stroke="currentColor" />
        @break

        @case('target')
            <circle cx="8" cy="8" r="6" />
            <circle cx="8" cy="8" r="2.5" />
            <path d="m10 6 4-4M11.5 2H14v2.5" />
        @break

        @case('trophy')
            <path d="M5 2h6v3a3 3 0 0 1-6 0V2ZM8 8v3M5 14h6M6 11h4" />
            <path d="M5 4H2v1a3 3 0 0 0 3 3M11 4h3v1a3 3 0 0 1-3 3" />
        @break

        @case('file')
            <path d="M4 1.5h5L13 5v9.5H4v-13Z" />
            <path d="M9 1.5V5h4M6.5 8h4M6.5 11h4" />
        @break

        @case('building')
            <path d="M3 14V3h7v11M10 7h3v7M1.5 14h13" />
            <path d="M5.5 6h2M5.5 9h2" />
        @break

        @case('bell')
            <path d="M4 11.5V7a4 4 0 0 1 8 0v4.5l1.5 2h-11l1.5-2Z" />
            <path d="M6.5 14a1.5 1.5 0 0 0 3 0" />
        @break

        @case('search')
            <circle cx="7" cy="7" r="4.5" />
            <path d="m10.5 10.5 3.5 3.5" />
        @break

        @case('logout')
            <path d="M7 3H3v10h4M10 5l3 3-3 3M6 8h7" />
        @break

        @case('leaf')
            <path d="M13.5 2.5C7 2.5 3 5.5 3 10c0 2 1.5 3.5 3.5 3.5 4.5 0 7-4 7-11Z" />
            <path d="M4.5 12c2-2.5 4-4 7-6" />
        @break

        @default
            <path d="M9.5 1.8 4 9h4l-1 5.2L12.5 7h-4l1-5.2Z" />
    @endswitch
</svg>
