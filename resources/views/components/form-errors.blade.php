@if ($errors->any())<div class="validation-errors" role="alert"><strong>Confira os dados informados.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
