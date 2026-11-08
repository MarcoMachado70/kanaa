<x-layout :title="$church->name">
    <a href="{{ route('churches.index') }}" class="text-sm underline underline-offset-4">← Explorar igrejas</a>
    <p class="mt-10 text-sm font-semibold uppercase tracking-widest text-[#947325]">{{ $church->denomination ?? 'Comunidade independente' }}</p>
    <h1 class="mt-3 font-serif text-4xl md:text-6xl">{{ $church->name }}</h1>
    @if ($church->verified_at)<p class="mt-4">✓ Gestão da página verificada</p>@endif
    <p class="mt-6 max-w-3xl whitespace-pre-line text-lg leading-relaxed text-[#56685f]">{{ $church->description }}</p>
    <h2 class="mb-6 mt-12 font-serif text-3xl">Conheça nossas unidades</h2>
    <div class="grid gap-6 md:grid-cols-2">
        @forelse ($church->locations as $location)
            <article class="space-y-5 rounded-2xl border border-[#ddd8c9] bg-white p-7">
                <h3 class="text-2xl font-semibold">{{ $location->name }}</h3>
                <p>{{ $location->neighborhood }} · {{ $location->city }}/{{ $location->state }}<br>{{ $location->address }}</p>
                @if ($location->welcomes_visitors)<p class="inline-block rounded-full bg-[#e8eedf] px-4 py-2 text-sm">Aberta a visitantes</p>@endif
                <div><h4 class="font-semibold">Horários habituais</h4><p class="mt-2 whitespace-pre-line text-[#56685f]">{{ $location->regular_schedule ?? 'Consulte a igreja para saber os horários.' }}</p><p class="mt-2 text-xs text-[#56685f]">Informação institucional. Não representa um evento confirmado na agenda.</p></div>
                <div><h4 class="font-semibold">Acessibilidade</h4><p class="mt-2 text-[#56685f]">{{ $location->accessibility ?? 'Informações ainda não fornecidas.' }}</p></div>
                <div><h4 class="font-semibold">Para sua primeira visita</h4><p class="mt-2 text-[#56685f]">{{ $location->visitor_information ?? 'Consulte as orientações da igreja antes de visitar.' }}</p></div>
            </article>
        @empty
            <p>As informações das unidades serão disponibilizadas em breve.</p>
        @endforelse
    </div>
</x-layout>
