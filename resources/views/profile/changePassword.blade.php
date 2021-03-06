@extends('layout')

@section('title', 'Cambiar contraseña')

@section('content')
    <h1>Cambiar contraseña</h1>

    <form action="{{ route('profile.changePassword') }}" method="post" class="mt-3" enctype="multipart/form-data">
        {{ method_field('PUT') }}
        {{ csrf_field() }}

        <div class="form-group">
            <label for="inputOldPassword">Contraseña antigua</label>
            <input type="password" class="form-control" id="inputOldPassword" name="old_password">
            @if ($errors->has('old_password'))
                <div class="alert alert-danger mt-2">{{ $errors->first('old_password') }}</div>
            @endif
        </div>

        <div class="form-group">
            <label for="inputNewPassword">Contraseña nueva</label>
            <input type="password" class="form-control" id="inputNewPassword" name="new_password" placeholder="Mayor a 6 caracteres">
            @if ($errors->has('new_password'))
                <div class="alert alert-danger mt-2">{{ $errors->first('new_password') }}</div>
            @endif
        </div>

        <div class="form-group">
            <label for="inputVerifyPassword">Repetir contraseña</label>
            <input type="password" class="form-control" id="inputVerifyPassword" name="verify_password" placeholder="Mayor a 6 caracteres">
            @if ($errors->has('verify_password'))
                <div class="alert alert-danger mt-2">{{ $errors->first('verify_password') }}</div>
            @endif
        </div>

        <div class="my-custom-panel my-4 shadow-sm p-4">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar cambios</button>
            <a href="{{ route('profile.index') }}" class="btn my-btn-other"><i class="fas fa-arrow-left"></i> Volver al perfil </a>
        </div>
    </form>
@endsection
