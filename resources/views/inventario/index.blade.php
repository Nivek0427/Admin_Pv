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
        <td>{{ $p->stock }}</td>
        <td>
          <form action="{{ route('inventario.updateCantidad', $p->id) }}" method="POST" class="d-inline">
            @csrf
            <input type="number" name="cantidad" class="form-control d-inline" style="width:100px" required placeholder="+/- unidades">
            <button type="submit" class="btn btn-sm btn-success mt-1">
              <i class="fas fa-save"></i> Guardar
            </button>
          </form>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
