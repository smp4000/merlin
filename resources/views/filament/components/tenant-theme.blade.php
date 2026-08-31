@php($variables = $palette->cssVariables())
<style id="merlin-tenant-theme">
    :root {
        --merlin-accent: {{ $variables['accent'] }};
        --merlin-accent-hover: {{ $variables['accent_hover'] }};
        --merlin-accent-soft: {{ $variables['accent_soft'] }};
        --merlin-accent-soft-strong: {{ $variables['accent_soft_strong'] }};
        --merlin-accent-focus: {{ $variables['accent_focus'] }};
    }
</style>
