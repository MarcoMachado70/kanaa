@props(['name', 'size' => 20])
@php
$paths = [
'home' => 'm3 10 9-7 9 7 M5 9v12h5v-7h4v7h5V9',
'compass' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20 M16 8l-2 6-6 2 2-6 6-2',
'people' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
'arrow' => 'M5 12h14 m-5-5 5 5-5 5',
'pin' => 'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0 M15 10a3 3 0 1 0-6 0 3 3 0 0 0 6 0',
'search' => 'M21 21l-5-5 M18 10a8 8 0 1 0-16 0 8 8 0 0 0 16 0',
'book' => 'M12 5v16 M12 5C8 2 4 3 2 4v16c3-1 7-1 10 1 3-2 7-2 10-1V4c-2-1-6-2-10 1',
'heart' => 'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8',
'leaf' => 'M20 3C8 1 1 9 5 17s18 1 15-14 M4 21 16 9',
'sun' => 'M12 2v2 M12 20v2 M2 12h2 M20 12h2 M5 5l1 1 M18 18l1 1 M5 19l1-1 M18 6l1-1 M17 12a5 5 0 1 0-10 0 5 5 0 0 0 10 0',
'music' => 'M9 18V5l12-2v13 M9 8l12-2 M9 18a3 3 0 1 0-6 0 3 3 0 0 0 6 0 M21 16a3 3 0 1 0-6 0 3 3 0 0 0 6 0',
'lock' => 'M5 11h14v11H5z M8 11V7a4 4 0 0 1 8 0v4',
'globe' => 'M22 12a10 10 0 1 0-20 0 10 10 0 0 0 20 0 M2 12h20 M12 2c6 6 6 14 0 20-6-6-6-14 0-20',
];
@endphp
<svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['people'] }}"/></svg>
