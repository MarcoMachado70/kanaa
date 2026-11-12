@props(['user', 'following' => false])
@auth
@if(auth()->id() !== $user->id)<form method="post" action="{{ $following ? route('people.unfollow',$user) : route('people.follow',$user) }}">@csrf @method($following ? 'DELETE' : 'PUT')<button class="button {{ $following ? 'button-light' : '' }}">{{ $following ? 'Deixar de seguir' : 'Seguir' }}<span class="sr-only"> {{ $user->name }}</span></button></form>@endif
@endauth
