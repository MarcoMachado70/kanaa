<x-layout :title="$community->name">
<a href="{{ route('communities.index') }}" class="back-link">← Todas as comunidades</a>
<div class="community-profile-cover tone-{{ $community->type }}"><x-landscape/><span>{{ \App\Models\Community::TYPES[$community->type] ?? 'Comunidade' }}</span></div>
<div class="community-profile-heading"><x-community-avatar :community="$community"/><div><h1>{{ $community->name }}</h1><p class="location-line"><x-icon name="pin" size="15"/>{{ $community->city }}/{{ $community->state }}<span>·</span>{{ $community->visibility === 'approval' ? 'Entrada mediante aprovação' : 'Comunidade pública' }}</p></div></div>
<div class="community-detail-grid"><section>
@if($membership?->status === 'active')<x-composer :community="$community"/>@endif
<div class="section-heading"><h2>Mural da comunidade</h2><span class="muted text-xs">Publicações da comunidade</span></div><x-post-feed :posts="$posts"/></section>
<aside><section class="rail-card"><h2>Sobre a comunidade</h2><p class="whitespace-pre-line">{{ $community->description }}</p>@if($community->church)<div class="affiliation"><span class="eyebrow">VINCULADA À IGREJA</span><a href="{{ route('churches.show', $community->church) }}">{{ $community->church->name }} <x-icon name="arrow" size="16"/></a></div>@else<p class="privacy-tag">Comunidade independente</p>@endif
@can('manage', $community)<div class="participation-box"><a class="button" href="{{ route('communities.manage', $community) }}">Gerenciar comunidade <x-icon name="people" size="17"/></a></div>@endcan
<div class="participation-box">
@guest<p>Encontre seu lugar nesta comunidade. Entre na Kanaã para participar.</p><a class="button" href="{{ route('login') }}">Entrar para participar <x-icon name="arrow" size="16"/></a>
@else
@if($membership)<p class="membership-badge">{{ match ($membership->status) { 'pending' => 'Sua solicitação aguarda aprovação.', 'rejected' => 'Sua solicitação não foi aprovada.', default => 'Você faz parte desta comunidade.' } }}</p><form method="post" action="{{ route('memberships.destroy', $community->slug) }}">@csrf @method('DELETE')<button class="button button-light">{{ $membership->status === 'pending' ? 'Cancelar solicitação' : 'Sair da comunidade' }}</button></form>
@else<p>{{ $community->visibility === 'approval' ? 'A entrada depende da aprovação da gestão. Sua solicitação não libera conteúdos restritos.' : 'Participe para incluir este grupo nas suas comunidades.' }}</p><form method="post" action="{{ route('memberships.store', $community->slug) }}">@csrf @method('PUT')<button class="button">{{ $community->visibility === 'approval' ? 'Solicitar participação' : 'Participar da comunidade' }}</button></form>@endif
@endguest</div></section></aside></div>
</x-layout>
