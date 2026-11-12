<x-layout title="Início">
<div class="home-grid">
    <div class="feed-column">
        <div class="page-heading"><div><span class="eyebrow">BEM-VINDO À KANAÃ</span><h1>É bom caminhar junto.</h1></div><span class="heading-symbol"><x-icon name="sun" size="28"/></span></div>
        <section class="welcome-banner"><x-landscape class="banner-landscape"/><div class="banner-copy"><span class="eyebrow">CONEXÕES COM PROPÓSITO</span><h2>Encontre seu lugar.<br>Viva em comunidade.</h2><p>A fé ganha vida nos encontros.<br>Descubra pessoas com quem caminhar.</p><a href="{{ route('communities.index') }}" class="button button-light">Explorar comunidades <x-icon name="arrow" size="17"/></a></div></section>
        <section class="discovery-strip" aria-label="Explore por interesse"><a href="{{ route('communities.index', ['type' => 'jovens']) }}"><span class="interest-icon tone-jovens"><x-icon name="people"/></span>Fazer parte</a><a href="{{ route('communities.index', ['type' => 'estudos']) }}"><span class="interest-icon tone-estudos"><x-icon name="book"/></span>Aprender juntos</a><a href="{{ route('communities.index', ['type' => 'voluntariado']) }}"><span class="interest-icon tone-voluntariado"><x-icon name="heart"/></span>Servir ao próximo</a></section>
        <x-composer/>
@auth
<nav class="filter-tabs" aria-label="Mural"><a href="{{ route('home') }}" @class(['selected'=>request('feed') !== 'following'])>Descobrir</a><a href="{{ route('home',['feed'=>'following']) }}" @class(['selected'=>request('feed') === 'following'])>Seguindo</a><a href="{{ route('people.index') }}">Encontrar pessoas →</a></nav>
@endauth
<div class="section-heading feed-heading"><div><span class="eyebrow">VIDA EM COMUNIDADE</span><h2>No mural da Kanaã</h2></div><span class="muted text-xs">Publicações públicas</span></div>
        <x-post-feed :posts="$posts"/>
    </div>
    <aside class="right-rail">
        <section class="rail-card"><span class="eyebrow">SEU PRÓXIMO ENCONTRO</span><h2>Uma igreja.<br>Muitas possibilidades.</h2><p>Na sua cidade ou em uma nova jornada, encontre uma comunidade de fé para conhecer.</p><a href="{{ route('churches.index') }}" class="button"><x-icon name="compass" size="17"/>Explorar igrejas</a><x-landscape class="rail-landscape"/></section>
        <section class="rail-card suggestions"><div class="section-heading"><h2>Conheça também</h2><x-icon name="people" size="18"/></div><p class="muted">Comunidades para descobrir</p>@forelse ($communities as $community)<a href="{{ route('communities.show', $community->slug) }}" class="suggestion"><x-community-avatar :community="$community"/><span><strong>{{ $community->name }}</strong><small>{{ \App\Models\Community::TYPES[$community->type] ?? 'Comunidade' }}</small></span><x-icon name="arrow" size="16"/></a>@empty<p class="muted">Novas comunidades em breve.</p>@endforelse<a href="{{ route('communities.index') }}" class="rail-link">Ver todas as comunidades <x-icon name="arrow" size="16"/></a></section>
        <p class="rail-footnote">Um espaço de fé, respeito e acolhimento.<br><span>Kanaã · Comunidade evangélica no Brasil</span></p>
    </aside>
</div>
</x-layout>
