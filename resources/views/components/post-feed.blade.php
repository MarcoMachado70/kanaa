@props(['posts'])
<section data-infinite-feed aria-label="Publicações">
    <div class="post-list" data-feed-items>
        @include('components.post-feed-items', ['posts' => $posts])
        @if ($posts->isEmpty())<div class="empty-state"><x-icon name="leaf" size="32"/><h2>Novas histórias começam aqui.</h2><p>Ainda não há publicações para mostrar.</p></div>@endif
    </div>
    <div class="feed-loader" data-feed-sentinel>
        <p class="feed-status" data-feed-status role="status" aria-live="polite">{{ $posts->hasMorePages() ? '' : ($posts->isEmpty() ? '' : 'Você chegou ao fim das publicações.') }}</p>
        @if ($posts->hasMorePages())<a class="button button-light" href="{{ $posts->nextPageUrl() }}" data-feed-next>Carregar mais</a>@endif
    </div>
</section>
