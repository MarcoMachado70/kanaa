<x-layout>
    <div class="mb-10 max-w-2xl">
        <p class="mb-3 text-sm font-semibold uppercase tracking-widest text-[#947325]">Encontre sua comunidade</p>
        <h1 class="font-serif text-4xl md:text-6xl">Um caminho de fé.<br>Uma comunidade perto.</h1>
        <p class="mt-5 text-lg text-[#56685f]">Conheça igrejas, descubra suas unidades e prepare sua próxima visita — na sua cidade ou em outro destino.</p>
    </div>
    <form action="{{ route('churches.index') }}" method="GET" class="mb-10 grid gap-5 rounded-2xl border border-[#ddd8c9] bg-white p-6 md:grid-cols-2 lg:grid-cols-5">
        <label class="block text-sm font-semibold">Cidade<input name="city" value="{{ $filters['city'] ?? '' }}" placeholder="Ex.: Curitiba" maxlength="100" class="mt-2 w-full rounded-lg border border-[#ccc7b9] p-3"></label>
        <label class="block text-sm font-semibold">UF<input name="state" value="{{ $filters['state'] ?? '' }}" placeholder="PR" minlength="2" maxlength="2" class="mt-2 w-full rounded-lg border border-[#ccc7b9] p-3 uppercase"></label>
        <label class="block text-sm font-semibold">Tradição<select name="denomination" class="mt-2 w-full rounded-lg border border-[#ccc7b9] bg-white p-3"><option value="">Todas as tradições</option>@foreach ($denominations as $denomination)<option value="{{ $denomination }}" @selected(($filters['denomination'] ?? '') === $denomination)>{{ $denomination }}</option>@endforeach</select></label>
        <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="visitors" value="1" @checked($filters['visitors'] ?? false) class="size-5 accent-[#193e32]">Abertas a visitantes</label>
        <button class="self-center rounded-lg bg-[#193e32] px-5 py-3 font-semibold text-white hover:bg-[#295744]">Buscar igrejas</button>
        @if ($errors->any())<div role="alert" class="text-red-700 md:col-span-2 lg:col-span-5">Confira os filtros informados. @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    </form>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3"><h2 class="text-xl font-semibold">{{ $churches->total() }} {{ $churches->total() === 1 ? 'igreja encontrada' : 'igrejas encontradas' }}</h2><a href="{{ route('churches.index') }}" class="text-sm underline underline-offset-4">Limpar filtros</a></div>
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($churches as $church)
            <article class="flex flex-col gap-4 rounded-2xl border border-[#ddd8c9] bg-white p-6">
                <p class="text-sm text-[#806625]">{{ $church->denomination ?? 'Comunidade independente' }}</p>
                <h3 class="font-serif text-2xl"><a href="{{ route('churches.show', $church) }}" class="hover:underline">{{ $church->name }}</a></h3>
                @if ($church->verified_at)<p class="text-sm">✓ Gestão da página verificada</p>@endif
                <p class="text-sm leading-relaxed text-[#56685f]">{{ Str::limit($church->description, 145) }}</p>
                <ul class="space-y-2 text-sm">@foreach ($church->locations as $location)<li>{{ $location->neighborhood }} · {{ $location->city }}/{{ $location->state }} @if ($location->welcomes_visitors)<span class="block text-[#49764c]">Aberta a visitantes</span>@endif</li>@endforeach</ul>
                <a href="{{ route('churches.show', $church) }}" class="mt-auto border-t border-[#eee9dd] pt-4 font-semibold">Conhecer igreja <span aria-hidden="true">→</span></a>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-[#ccc7b9] p-10 md:col-span-2 lg:col-span-3"><h3 class="text-xl font-semibold">Ainda não encontramos uma igreja por aqui.</h3><p class="mt-3 text-[#56685f]">Tente outra cidade ou remova alguns filtros para ampliar a busca.</p></div>
        @endforelse
    </div>
    <div class="mt-8">{{ $churches->links() }}</div>
</x-layout>
