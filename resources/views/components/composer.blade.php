@props(['community' => null])
@auth
<section class="post-card composer"><div class="section-heading"><h2>O que está no seu coração?</h2><x-icon name="leaf"/></div><x-form-errors/><form action="{{ route('posts.store') }}" method="post" enctype="multipart/form-data" class="account-form" data-photo-composer>@csrf
@if($community)<input type="hidden" name="community_id" value="{{ $community->id }}">@endif
<label><span class="sr-only">Sua publicação</span><textarea name="body" rows="3" maxlength="5000" placeholder="Compartilhe uma reflexão, uma conquista ou um convite...">{{ old('body') }}</textarea></label>
<div class="photo-upload"><label>Adicionar foto<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-photo-input></label><p>JPG, PNG ou WebP · até 5 MB · máximo de 3000 × 3000 pixels</p><div data-photo-preview hidden><img alt="Prévia da foto selecionada"><button type="button" data-photo-remove>Remover foto</button></div><p data-photo-error role="alert" hidden></p></div>
<div class="composer-actions"><label>Audiência<select name="audience">@if($community)<option value="members">Membros da comunidade</option>@else<option value="private">Só eu</option>@endif<option value="public">Público</option></select></label><button class="button">Publicar <x-icon name="arrow" size="16"/></button></div>
@if(!$community && !auth()->user()->public_profile)<p class="muted text-xs">Seu perfil está privado. Posts públicos só aparecem no mural quando você tornar seu perfil público.</p>@endif
</form></section>
@endauth
