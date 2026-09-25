@extends('layouts.app')

@section('title', 'Inventario')

@section('content')
<div class="container-fluid">
  <h1 class="mb-4">Inventario de Productos</h1>
  <div class="mb-3">
    <a href="{{ route('inventario.movimientos') }}" class="btn btn-primary">
        Historial de Movimientos
    </a>
</div>

  @if(session('success'))
    <div class="alert alert-success">
      {{ session('success') }}
    </div>
  @endif

  <form method="GET" action="{{ route('inventario.index') }}" class="row g-2 mb-3">
    <div class="col-md-5">
      <label for="buscar" class="form-label">Buscar por nombre o ID</label>
      <input type="search" name="buscar" id="buscar" class="form-control"
             value="{{ request('buscar') }}" placeholder="Ej. Nike o 12">
    </div>
    <div class="col-md-4">
      <label for="categoria" class="form-label">Categoría</label>
      <select name="categoria" id="categoria" class="form-control">
        <option value="">Todas</option>
        <option value="Ropa" {{ request('categoria') === 'Ropa' ? 'selected' : '' }}>Ropa</option>
        <option value="Accesorio" {{ request('categoria') === 'Accesorio' ? 'selected' : '' }}>Accesorio</option>
        <option value="Zapatos" {{ request('categoria') === 'Zapatos' ? 'selected' : '' }}>Zapatos</option>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <button type="submit" class="btn btn-primary mr-2">Buscar</button>
      <a href="{{ route('inventario.index') }}" class="btn btn-secondary">Limpiar</a>
    </div>
  </form>

  <table class="table table-striped table-bordered">
    <thead class="table-dark">
      <tr>
        <th>Producto</th>
        <th>Categoría</th>
        <th>Género</th>
        <th>Stock disponible</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody>
      @foreach($productos as $p)
      <tr>
        <td>{{ $p->nombre }}</td>
        <td>{{ $p->categoria }}</td>
        <td>{{ $p->genero }}</td>
        <td>
          @if($p->esZapato())
            <strong>Total: {{ $p->stock }}</strong>
            @if($p->productoTallas->isNotEmpty())
              <ul class="mb-0 mt-2 pl-3">
                @foreach($p->productoTallas as $productoTalla)
                  <li>{{ $productoTalla->talla->numero }}: {{ $productoTalla->stock }}</li>
                @endforeach
              </ul>
            @else
              <div class="text-muted mt-2">Sin tallas configuradas</div>
            @endif
          @else
            {{ $p->stock }}
          @endif
        </td>
        <td>
          <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editarInventario{{ $p->id }}">
            <i class="fas fa-edit"></i> Editar
          </button>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{ $productos->withQueryString()->links() }}

  @foreach($productos as $p)
    <div class="modal fade" id="editarInventario{{ $p->id }}" tabindex="-1" role="dialog" aria-labelledby="editarInventarioLabel{{ $p->id }}" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editarInventarioLabel{{ $p->id }}">Editar stock: {{ $p->nombre }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p><strong>Stock actual:</strong> {{ $p->stock }}</p>
            @if($p->esZapato())
              @if($p->productoTallas->isNotEmpty())
                <form action="{{ route('inventario.actualizarTallas', $p->id) }}" method="POST" class="ajustes-tallas-form">
                  @csrf
                  <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-3">
                      <thead>
                        <tr>
                          <th>Talla</th>
                          <th>Stock actual</th>
                          <th>Ajuste</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($p->productoTallas as $productoTalla)
                          <tr>
                            <td>{{ $productoTalla->talla->numero }}</td>
                            <td>{{ $productoTalla->stock }}</td>
                            <td>
                              <input type="number"
                                     name="ajustes[{{ $productoTalla->talla_id }}]"
                                     class="form-control ajuste-talla"
                                     value="0"
                                     step="1"
                                     required>
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                  <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar cambios
                  </button>
                </form>
              @else
                <span class="text-muted">Agregue tallas desde la edición del producto.</span>
              @endif
            @else
              <form action="{{ route('inventario.updateCantidad', $p->id) }}" method="POST">
                @csrf
                <label for="cantidad{{ $p->id }}">Cantidad (+ entrada / - salida)</label>
                <div class="d-flex">
                  <input type="number" name="cantidad" id="cantidad{{ $p->id }}" class="form-control mr-2" required step="1" placeholder="+/- unidades">
                  <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar
                  </button>
                </div>
              </form>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.ajustes-tallas-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      const inputs = Array.from(form.querySelectorAll('.ajuste-talla'));
      const ajustesActivos = inputs.filter(function (input) {
        return Number(input.value) !== 0;
      });

      if (ajustesActivos.length === 0) {
        event.preventDefault();
        alert('Ingresa al menos un ajuste diferente de cero.');
        return;
      }

      inputs.forEach(function (input) {
        if (Number(input.value) === 0) {
          input.disabled = true;
        }
      });
    });
  });
});
</script>
@endsection
