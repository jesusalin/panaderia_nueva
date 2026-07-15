<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') — Panadería</title>

    <!-- AdminLTE & Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <style>
        body { font-family: 'Nunito', sans-serif; }
        .brand-link { background: #b5451b; }
        .main-sidebar { background: #1a1a2e; }
        .nav-sidebar .nav-link { color: #c8c8d4; }
        .nav-sidebar .nav-link:hover,
        .nav-sidebar .nav-link.active { background: #b5451b !important; color: #fff !important; }
        .nav-sidebar .nav-header { color: #7a7a9d; font-size: 0.7rem; letter-spacing: 1px; }
        .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active { background: #b5451b; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,.08); border-radius: 12px; }
        .card-header { border-radius: 12px 12px 0 0 !important; font-weight: 700; }
        .btn { border-radius: 8px; font-weight: 600; }
        .badge { border-radius: 6px; }
        .small-box { border-radius: 12px; overflow: hidden; }
        .table thead th { border-top: none; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
        .content-header h1 { font-weight: 700; }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fas fa-user-circle mr-1"></i>
                    {{ auth()->user()->nombre ?? 'Usuario' }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#"><i class="fas fa-cog mr-2"></i>Mi perfil</a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt mr-2"></i>Cerrar sesión
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('dashboard') }}" class="brand-link text-center">
            <i class="fas fa-bread-slice mr-2"></i>
            <span class="brand-text font-weight-bold">Panadería</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    @if(auth()->user()->hasRole('admin'))
                    <li class="nav-header">CATÁLOGO</li>
                    <li class="nav-item">
                        <a href="{{ route('categorias.index') }}" class="nav-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tags"></i><p>Categorías</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('productos.index') }}" class="nav-link {{ request()->routeIs('productos.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-bread-slice"></i><p>
                                Productos
                                @if($stockBajoProductosCount > 0)
                                    <span class="badge badge-danger right">{{ $stockBajoProductosCount }}</span>
                                @endif
                            </p>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasRole(['admin', 'almacenero']))
                    <li class="nav-header">INVENTARIO</li>
                    <li class="nav-item">
                        <a href="{{ route('materia-prima.index') }}" class="nav-link {{ request()->routeIs('materia-prima.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-boxes"></i><p>
                                Materia Prima
                                @if($stockBajoMateriaPrimaCount > 0)
                                    <span class="badge badge-danger right">{{ $stockBajoMateriaPrimaCount }}</span>
                                @endif
                            </p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('movimientos.index') }}" class="nav-link {{ request()->routeIs('movimientos.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-exchange-alt"></i><p>Movimientos de Materia Prima</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('kardex.index') }}" class="nav-link {{ request()->routeIs('kardex.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book-open"></i><p>Movimientos de Productos</p>
                        </a>
                    </li>

                    <li class="nav-header">PRODUCCIÓN</li>
                    <li class="nav-item">
                        <a href="{{ route('produccion.index') }}" class="nav-link {{ request()->routeIs('produccion.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-industry"></i><p>Producción</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('produccion.recetas') }}" class="nav-link {{ request()->is('produccion/recetas*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book"></i><p>Recetas</p>
                        </a>
                    </li>

                    <li class="nav-header">COMPRAS</li>
                    <li class="nav-item">
                        <a href="{{ route('proveedores.index') }}" class="nav-link {{ request()->routeIs('proveedores.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-truck"></i><p>Proveedores</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('compras.index') }}" class="nav-link {{ request()->routeIs('compras.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shopping-cart"></i><p>Compras</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('ordenes-automaticas.index') }}" class="nav-link {{ request()->routeIs('ordenes-automaticas.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-robot"></i><p>Órdenes Automáticas</p>
                        </a>
                    </li>

                    <li class="nav-header">REPORTES</li>
                    <li class="nav-item">
                        <a href="{{ route('tiempos-operacion.index') }}" class="nav-link {{ request()->routeIs('tiempos-operacion.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-stopwatch"></i><p>Tiempos por Operación</p>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasRole(['admin', 'vendedor']))
                    <li class="nav-header">CLIENTES</li>
                    <li class="nav-item">
                        <a href="{{ route('clientes.index') }}" class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i><p>Clientes</p>
                        </a>
                    </li>

                    <li class="nav-header">VENTAS</li>
                    <li class="nav-item">
                        <a href="{{ route('ventas.index') }}" class="nav-link {{ request()->routeIs('ventas.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cash-register"></i><p>Ventas</p>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasRole('admin'))
                    <li class="nav-header">ADMINISTRACIÓN</li>
                    <li class="nav-item">
                        <a href="{{ route('usuarios.index') }}" class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i><p>Usuarios</p>
                        </a>
                    </li>
                    @endif

                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">@yield('title', 'Dashboard')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                            @yield('breadcrumb')
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">

                {{-- Alertas --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer text-center text-muted small">
        Sistema de Gestión &mdash; Panadería &copy; {{ date('Y') }}
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="{{ asset('js/tiempos-operacion.js') }}"></script>
@stack('scripts')
</body>
</html>
