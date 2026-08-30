<x-layouts.public :title="$document->key === 'terms' ? 'Nutzungsbedingungen' : 'Datenschutzhinweise'" :noindex="true">
    <article class="merlin-legal-page merlin-legal-content">
        <span class="merlin-eyebrow">Version {{ $document->version }}</span>
        {!! $html !!}
    </article>
</x-layouts.public>
