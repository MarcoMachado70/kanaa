@props(['title' => 'Início'])
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="theme-color" content="#153f30">
    <meta name="description" content="Kanaã: igrejas, comunidades e caminhos de fé. Encontre seu lugar e caminhe junto.">
    <title>{{ $title }} · Kanaã</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="social-body">
<a href="#conteudo" class="skip-link">Pular para o conteúdo</a>
<header class="topbar">
    <a href="{{ route('home') }}" class="brand"><span class="brand-mark"><x-icon name="sun" size="32"/></span>Kanaã</a>
    <span class="brand-caption">Igrejas, pessoas e caminhos de fé.</span>
    <form action="{{ route('communities.index') }}" method="get" class="header-search" role="search"><x-icon name="search"/><input name="q" aria-label="Buscar comunidades" placeholder="Encontre uma comunidade..."><button aria-label="Pesquisar comunidades"><x-icon name="arrow" size="18"/></button></form>
    <a class="account-link" href="{{ auth()->check() ? route('profile') : route('login') }}"><span class="account-avatar">{{ auth()->check() ? mb_substr(auth()->user()->name, 0, 1) : '→' }}</span><span>{{ auth()->check() ? 'Meu perfil' : 'Entrar' }}</span></a>
</header>
<div class="app-shell">
    <aside class="sidebar">
        <p class="eyebrow">SEU CAMINHO COMEÇA AQUI</p>
        <nav class="main-nav" aria-label="Navegação principal">
            @foreach (['home' => ['home', 'Início'], 'churches.index' => ['compass', 'Explorar igrejas'], 'communities.index' => ['people', 'Comunidades'], 'people.index' => ['people', 'Pessoas'], 'singles.index' => ['heart', 'Solteiros'], 'profile' => ['people', 'Perfil']] as $route => [$icon, $label])
            @php($active = $route === 'home' ? request()->routeIs('home') : request()->routeIs(str_replace('.index', '.*', $route)))
            <a href="{{ route($route) }}" @class(['nav-item', 'active' => $active]) @if($active) aria-current="page" @endif><x-icon :name="$icon"/><span>{{ $label }}</span>@if($active)<span class="nav-dot"></span>@endif</a>
            @endforeach
        </nav>
        <div class="sidebar-note"><span class="small-icon"><x-icon name="leaf"/></span><h3>A fé nos aproxima.<br>A comunidade nos fortalece.</h3><p>Descubra novos lugares para pertencer, aprender e servir.</p><a href="{{ route('communities.index') }}">Encontre seu lugar <x-icon name="arrow" size="17"/></a></div>
        <div class="sidebar-bottom"><span class="brand small-brand">Kanaã</span><p>Feita de encontros.<br>Guiada por propósito.</p><span class="demo-label">Ambiente de desenvolvimento</span></div>
    </aside>
    <main id="conteudo" class="main-content">@if(session('status'))<div class="status-message" role="status">{{ session('status') }}</div>@endif{{ $slot }}</main>
</div>
<nav class="mobile-nav" aria-label="Navegação móvel">
    @foreach (['home' => ['home', 'Início'], 'churches.index' => ['compass', 'Explorar'], 'communities.index' => ['people', 'Comunidades'], 'people.index' => ['people', 'Pessoas'], 'profile' => ['heart', 'Perfil']] as $route => [$icon, $label])
    @php($active = $route === 'home' ? request()->routeIs('home') : request()->routeIs(str_replace('.index', '.*', $route)))
    <a href="{{ route($route) }}" @class(['active' => $active]) @if($active) aria-current="page" @endif><x-icon :name="$icon"/><span>{{ $label }}</span></a>
    @endforeach
</nav>
</body></html>
