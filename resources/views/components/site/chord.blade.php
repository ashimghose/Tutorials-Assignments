@props(['name', 'frets'])

{{-- A guitar chord box drawn from a fret string such as "x32010" (strings low E
     to high e; x = not played, 0 = open, a number = the fret to press). --}}
@php
    $positions = str_split($frets);
    $x = fn (int $string) => 15 + $string * 14;
    $label = $name.' chord: '.collect($positions)->map(fn ($fret, $i) => ['low E', 'A', 'D', 'G', 'B', 'high e'][$i].' '.match ($fret) { 'x' => 'not played', '0' => 'open', default => 'fret '.$fret })->implode(', ');
@endphp

<figure class="flex flex-col items-center">
    <svg viewBox="0 0 100 128" class="h-36 w-auto" role="img" aria-label="{{ $label }}">
        @foreach ($positions as $i => $fret)
            @if ($fret === 'x')
                <path d="M{{ $x($i) - 4 }},12 l8,8 m0,-8 l-8,8" stroke="#64748b" stroke-width="1.6" stroke-linecap="round"/>
            @elseif ($fret === '0')
                <circle cx="{{ $x($i) }}" cy="16" r="4" fill="none" stroke="#5b3a8c" stroke-width="1.6"/>
            @endif
        @endforeach
        <rect x="15" y="27" width="70" height="4" fill="#1e293b"/>
        @foreach (range(1, 5) as $f)
            <line x1="15" y1="{{ 31 + $f * 18 }}" x2="85" y2="{{ 31 + $f * 18 }}" stroke="#cbd5e1" stroke-width="1.5"/>
        @endforeach
        @foreach (range(0, 5) as $i)
            <line x1="{{ $x($i) }}" y1="31" x2="{{ $x($i) }}" y2="121" stroke="#475569" stroke-width="{{ 1.8 - $i * 0.2 }}"/>
        @endforeach
        @foreach ($positions as $i => $fret)
            @if (ctype_digit($fret) && $fret !== '0')
                <circle cx="{{ $x($i) }}" cy="{{ 31 + ((int) $fret - 0.5) * 18 }}" r="5.5" fill="#5b3a8c"/>
            @endif
        @endforeach
    </svg>
    <figcaption class="mt-2 text-lg font-bold text-slate-900">{{ $name }}</figcaption>
</figure>
