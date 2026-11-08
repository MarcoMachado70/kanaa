@props(['community'])
<span {{ $attributes->class(['community-avatar', 'tone-'.$community->type]) }}><x-icon :name="['jovens' => 'people', 'estudos' => 'book', 'voluntariado' => 'heart', 'oracao' => 'sun', 'musica' => 'music'][$community->type] ?? 'people'" size="24" /></span>
