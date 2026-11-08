@props(['community'])
<article class="community-card">
    <div class="community-cover tone-{{ $community->type }}"><x-icon :name="['jovens' => 'people', 'estudos' => 'book', 'voluntariado' => 'heart', 'oracao' => 'sun', 'musica' => 'music'][$community->type] ?? 'people'" size="60"/><span>{{ \App\Models\Community::TYPES[$community->type] ?? 'Comunidade' }}</span></div>
    <div class="community-card-body"><div class="community-card-heading"><x-community-avatar :community="$community"/><span class="privacy-tag"><x-icon :name="$community->visibility === 'approval' ? 'lock' : 'globe'" size="12"/>{{ $community->visibility === 'approval' ? 'Com aprovação' : 'Pública' }}</span></div>
    <h2><a href="{{ route('communities.show', $community->slug) }}">{{ $community->name }}</a></h2><p>{{ Str::limit($community->description, 140) }}</p><span class="location-line"><x-icon name="pin" size="14"/>{{ $community->city }}/{{ $community->state }}</span>
    <a href="{{ route('communities.show', $community->slug) }}" class="card-link">Conhecer comunidade <x-icon name="arrow" size="17"/></a></div>
</article>
