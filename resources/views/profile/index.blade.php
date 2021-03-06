@extends('layout')

@section('title', "Perfil")

@section('content')

    <h1 class="mb-3">Perfil.</h1>

    <h5 class="info_field_title">Email</h5>
    <p>{{ $user->email }}</p>

    <h5 class="mt-5 info_field_title">Rol</h5>
    @if($user->role == "superadmin")
        <p>Super administrador</p>
    @elseif($user->role == "admin")
        <p>Administrador</p>
    @elseif($user->role == "operator")
        <p>Operario</p>
    @else
        <p>Usuario</p>
    @endif
    <div class="my-custom-panel my-4 shadow-sm p-4">
        <a class="btn my-btn-primary" href="{{ route('profile.changePassword') }}"><i class="fas fa-lock"></i> Cambiar contraseña</a>
    </div>
@endsection
