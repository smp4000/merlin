<div class="merlin-tenant-switcher" aria-label="{{ __('merlin.tenant_switcher.aria_label') }}">
    <span class="merlin-tenant-switcher__mark" aria-hidden="true">
        {{ mb_strtoupper(mb_substr($context->tenant->display_name, 0, 1)) }}
    </span>
    <span class="merlin-tenant-switcher__copy">
        <small>{{ __('merlin.tenant_switcher.active') }}</small>
        <strong>{{ $context->tenant->display_name }}</strong>
    </span>
    @if ($membershipCount > 1)
        <a href="{{ route('tenant-selection.show') }}">
            {{ __('merlin.tenant_switcher.change') }}
        </a>
    @endif
</div>
