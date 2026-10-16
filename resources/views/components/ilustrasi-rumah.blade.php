{{-- Ilustrasi rumah kost (HouseIllustration di desain). <x-ilustrasi-rumah class="w-full h-36" /> --}}
<svg viewBox="0 0 320 190" {{ $attributes }} fill="none" role="img" aria-label="Ilustrasi rumah kost dengan panel surya">
    <ellipse cx="160" cy="168" rx="132" ry="16" fill="#03151D" opacity=".55" />
    <path d="m52 85 96-50 112 54-97 52L52 85Z" fill="#0C3038" stroke="#25C6AC" />
    <path d="m52 85 111 56v35L52 119V85Z" fill="#08242C" stroke="#41636A" />
    <path d="m163 141 97-52v35l-97 52v-35Z" fill="#0B4848" stroke="#41636A" />
    <path d="m91 67 57-30 68 33-58 30-67-33Z" fill="#052B35" stroke="#25C6AC" />
    @foreach ([0, 1, 2] as $i)
        <path d="m{{ 104 + $i * 19 }} {{ 62 - $i * 10 }} 34 17 12-6-34-17-12 6Z" fill="#0A5558" stroke="#25C6AC" stroke-width=".7" />
    @endforeach
    @foreach ([0, 1, 2] as $i)
        <rect x="{{ 78 + $i * 31 }}" y="104" width="18" height="25" rx="3" fill="#F5B84B" opacity=".82" />
    @endforeach
    <rect x="188" y="119" width="20" height="29" rx="3" fill="#F5B84B" opacity=".75" />
    <rect x="220" y="102" width="18" height="27" rx="3" fill="#F5B84B" opacity=".75" />
</svg>
