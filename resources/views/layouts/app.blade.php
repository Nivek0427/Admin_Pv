<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Panel de Control')</title>

  <!-- CSS de AdminLTE -->
   <link rel="stylesheet" href="{{ asset('css/frstore.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
  @stack('styles')
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">

    <!-- Botón del menú -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <!-- Usuario a la derecha con dropdown -->
    <ul class="navbar-nav ml-auto mr-3"> <!-- ml-auto = empuja a la derecha en AdminLTE/Bootstrap4 -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-toggle="dropdown">
                <i class="fas fa-user-circle fa-lg mr-2"></i>
                <span>{{ Auth::user()->name }}</span>
            </a>

            <div class="dropdown-menu dropdown-menu-right">
                <form action="{{ route('logout') }}" method="POST" class="dropdown-item p-0 m-0">
                    @csrf
                    <button type="submit" class="btn w-100 text-left">
                        <i class="fas fa-sign-out-alt mr-2"></i> Cerrar sesión
                    </button>
                </form>
            </div>
        </li>
    </ul>

</nav>




  <!-- Sidebar -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a class="navbar-brand d-flex align-items-center justify-content-center" href="/">
      <img src="{{ asset('images/logo_FrStore.png') }}"
          alt="Logo"
          style="width: 50%; height: auto;">
    </a>

    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column"
            data-widget="treeview" role="menu" data-accordion="false">

        <li class="nav-item">
            <a class="nav-link {{ request()->is('/') ? 'sidebar-active' : '' }}" href="/">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
            </a>
        </li>

        <li class="nav-item" >
            <a href="/productos" class="nav-link">
            <i class="nav-icon fas fa-tshirt"></i>
            <p>Productos</p>
            </a>
        </li>

        <li class="nav-item has-treeview {{ request()->is('ventas*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ request()->is('ventas*') ?  : '' }}">
                <i class="nav-icon fas fa-shopping-cart"></i>
                <p>
                    Ventas
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="{{ route('ventas.index') }}" class="nav-link {{ request()->routeIs('ventas.index') ?  : '' }}">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Ver Ventas</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('ventas.create') }}" class="nav-link {{ request()->routeIs('ventas.create') ?  : '' }}">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Realizar Venta</p>
                    </a>
                </li>
            </ul>
        </li>


    @can('inventario')
    <li class="nav-item">
        <a href="/inventario" class="nav-link">
        <i class="nav-icon fas fa-boxes"></i>
        <p>Inventario</p>
        </a>
    </li>
    @endcan
    @can('reportes')
            <li class="nav-item">
                <a href="/reportes" class="nav-link">
                <i class="nav-icon fas fa-chart-line"></i>
                <p>Reportes</p>
                </a>
            </li>
        </ul>
    </nav>
    @endcan
    </div>


  </aside>

  <!-- Contenido principal -->
  <div class="content-wrapper p-3">
    @yield('content')
  </div>

  <!-- Footer -->
  <footer class="main-footer text-center">
    <strong>© {{ date('Y') }} FrStore</strong>
  </footer>

</div>

<!-- JS de AdminLTE -->
<script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('adminlte/dist/js/adminlte.min.js') }}"></script>

</body>
</html>
