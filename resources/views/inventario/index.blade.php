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

  <table class="table table-striped table-bordered">
    <thead class="table-dark">
      <tr>
        <th>Producto</th>
        <th>Categoría</th>
        <th>Género</th>
        <th>Stock disponible</th>
        <th>Actualizar stock</th>
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
          @if($p->esZapato())
            @forelse($p->productoTallas as $productoTalla)
              <form action="{{ route('inventario.updateCantidad', $p->id) }}" method="POST" class="d-flex align-items-center mb-2">
                @csrf
                <input type="hidden" name="talla_id" value="{{ $productoTalla->talla_id }}">
                <span class="mr-2">Talla {{ $productoTalla->talla->numero }}</span>
                <input type="number" name="cantidad" class="form-control mr-2" style="width:110px" required step="1" placeholder="+/- unidades">
                <button type="submit" class="btn btn-sm btn-success">
                  <i class="fas fa-save"></i> Guardar
                </button>
              </form>
            @empty
              <span class="text-muted">Agregue tallas desde la edición del producto.</span>
            @endforelse
          @else
            <form action="{{ route('inventario.updateCantidad', $p->id) }}" method="POST" class="d-inline">
              @csrf
              <input type="number" name="cantidad" class="form-control d-inline" style="width:100px" required placeholder="+/- unidades">
              <button type="submit" class="btn btn-sm btn-success mt-1">
                <i class="fas fa-save"></i> Guardar
              </button>
            </form>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
