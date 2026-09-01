<div class="merlin-tenant-switcher" aria-label="{{ __('merlin.station_switcher.aria_label') }}">
    <span class="merlin-tenant-switcher__mark" aria-hidden="true">
        {{ mb_strtoupper(mb_substr($activeStation?->name ?? $context->tenant->display_name, 0, 1)) }}
    </span>
    <span class="merlin-tenant-switcher__copy">
        <small>
            {{ $activeStation === null ? __('merlin.station_switcher.missing') : __('merlin.station_switcher.active') }}
        </small>
        <strong>{{ $activeStation?->name ?? __('merlin.station_switcher.select') }}</strong>
    </span>
    @if ($activeStationCount > 1 || $activeStation === null)
        <a href="{{ $stationSelectionUrl }}" wire:navigate>
            {{ __('merlin.station_switcher.change') }}
        </a>
    @endif
    @if ($membershipCount > 1)
        <a class="merlin-tenant-switcher__tenant-link" href="{{ route('tenant-selection.show') }}">
            {{ __('merlin.tenant_switcher.change_partner') }}
        </a>
    @endif
</div>
