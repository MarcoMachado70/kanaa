@props(['post'])
<article class="post-card" id="post-{{ $post->id }}"><header class="post-header">
@if($post->author)<a class="request-avatar" href="{{ route('people.show',$post->author) }}">{{ mb_substr($post->author->name,0,1) }}</a>@elseif($post->community)<x-community-avatar :community="$post->community"/>@endif
<div>@if($post->author)<a class="post-author" href="{{ route('people.show',$post->author) }}">{{ $post->author->name }}</a>@else<a class="post-author" href="{{ route('communities.show',$post->community->slug) }}">{{ $post->community->name }}</a>@endif
<p><time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->timezone('America/Sao_Paulo')->format('d/m/Y · H:i') }}</time> · {{ ['public'=>'Público','members'=>'Membros','private'=>'Só eu'][$post->audience] ?? 'Restrito' }}</p></div></header>
<div class="post-body">@if($post->community)<a class="eyebrow" href="{{ route('communities.show',$post->community->slug) }}">{{ $post->community->name }}</a>@endif
@if($post->title)<h2>{{ $post->title }}</h2>@endif<p>{{ $post->body }}</p></div>
@if($post->photo_path)<a class="post-photo" href="{{ route('posts.show',$post) }}"><img src="{{ route('posts.photo',$post) }}" alt="Foto compartilhada na publicação{{ $post->author ? ' por '.$post->author->name : '' }}" loading="lazy" decoding="async"></a>@endif
<div class="post-interactions">
@auth
<form method="post" action="{{ $post->liked_by_me ? route('posts.unlike',$post) : route('posts.like',$post) }}">@csrf @method($post->liked_by_me ? 'DELETE' : 'PUT')<button @class(['like-button','is-liked'=>$post->liked_by_me]) aria-pressed="{{ $post->liked_by_me ? 'true' : 'false' }}"><x-icon name="heart" size="19"/>{{ $post->liked_by_me ? 'Curtiu' : 'Curtir' }} <span>{{ $post->likes_count ?? 0 }}</span></button></form>
@else
<a href="{{ route('login') }}" class="like-button"><x-icon name="heart" size="19"/>{{ $post->likes_count ?? 0 }} curtidas</a>
@endauth
<a href="{{ route('posts.show',$post) }}#comentarios" class="comment-link">{{ $post->comments_count ?? 0 }} comentários <x-icon name="arrow" size="15"/></a>
</div>

@if(auth()->id() === $post->author_id && $post->author_id)<footer class="post-footer"><form method="post" action="{{ route('posts.destroy',$post) }}">@csrf @method('DELETE')<button>Excluir minha publicação</button></form></footer>@endif
</article>
