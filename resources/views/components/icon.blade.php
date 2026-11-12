@props(['name'])
<svg {{ $attributes->merge(['class' => 'h-5 w-5 shrink-0', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
@switch($name)
    @case('home')<path d="M4 11 12 4l8 7"/><path d="M6 10v9h12v-9"/><path d="M10 19v-5h4v5"/>@break
    @case('chart')<path d="M4 20h16"/><rect x="6" y="11" width="3" height="6"/><rect x="11" y="6" width="3" height="11"/><rect x="16" y="9" width="3" height="8"/>@break
    @case('list')<path d="M9 7h11M9 12h11M9 17h11"/><circle cx="5" cy="7" r="1"/><circle cx="5" cy="12" r="1"/><circle cx="5" cy="17" r="1"/>@break
    @case('plus')<circle cx="12" cy="12" r="8"/><path d="M12 8v8M8 12h8"/>@break
    @case('scan')<path d="M4 8V5h3M17 5h3v3M20 16v3h-3M7 19H4v-3"/><path d="M4 12h16"/>@break
    @case('qr')<rect x="4" y="4" width="6" height="6"/><rect x="14" y="4" width="6" height="6"/><rect x="4" y="14" width="6" height="6"/><path d="M14 14h2v2M20 14v6h-3M14 20v-1"/>@break
    @case('bell')<path d="M6 16v-5a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 21h4"/>@break
    @case('building')<rect x="5" y="4" width="14" height="16"/><path d="M9 8h2M13 8h2M9 12h2M13 12h2M10 20v-4h4v4"/>@break
    @case('users')<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14a5 5 0 0 1 5 5"/>@break
    @case('user-off')<circle cx="10" cy="8" r="3"/><path d="M4 20a6 6 0 0 1 12 0"/><path d="M17 8l4 4M21 8l-4 4"/>@break
    @case('clock')<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>@break
    @case('refresh')<path d="M19 12a7 7 0 0 1-12 5"/><path d="M5 12a7 7 0 0 1 12-5"/><path d="M17 4v3h-3M7 20v-3h3"/>@break
    @case('check')<circle cx="12" cy="12" r="8"/><path d="M8.5 12l2.5 2.5 4.5-5"/>@break
    @case('bag')<path d="M6 8h12l1 12H5z"/><path d="M9 8V7a3 3 0 0 1 6 0v1"/>@break
    @case('search')<circle cx="11" cy="11" r="6"/><path d="M16 16l4 4"/>@break
    @case('logout')<path d="M10 5H5v14h5"/><path d="M14 8l4 4-4 4M18 12H9"/>@break
@endswitch
</svg>
