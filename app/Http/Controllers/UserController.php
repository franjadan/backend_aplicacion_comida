<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use DataTables;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();

        return view('users.index', [
            'users' => $users
        ]);
    }

    public function create()
    {
        $user = new User;
        return view('users.create', [
            'user' => $user,
            'roles' => ['admin' => 'Admin', 'user' => 'Usuario']
        ]);
    }

    public function store(CreateUserRequest $request)
    {
        $request->createUser();

        return redirect()->route('users.index')->with('success', 'Se ha creado el usuario con éxito.');
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => ['admin' => 'Admin', 'user' => 'Usuario']
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $request->updateUser($user);

        return redirect()->route('users.edit', ['user' => $user])->with('success', 'Se han guardado los cambios.');
    }

    public function changeStatus(User $user)
    {

        if($user->active) {
            $user->active = false;
        } else {
            $user->active = true;
        }

        $user->save();

        return redirect()->route('users.edit', ['user' => $user])->with('success', 'Se han guardado los cambios.');

    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Se ha eliminado con éxito.');
    }

    public function register(Request $request) 
    {
        $validator = Validator::make($request->all(), [ 
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'present', 'min:6'],
            'address' => 'required',
            'phone' => ['required', 'regex:/(\+34|0034|34)?[ -]*(6|7)[ -]*([0-9][ -]*){8}/'],
        ], [
            'first_name.required' => 'El campo nombre es obligatorio',
            'last_name.required' => 'El campo apellido es obligatorio',
            'email.required' => 'El campo email es obligatorio',
            'email.email' => 'El campo email debe ser válido',
            'email.unique' => 'El campo email debe ser único',
            'password.required' => 'El campo contraseña debe ser obligatorio',
            'password.min' => 'El campo contraseña debe tener mínimo 6 caracteres',
            'address.required' => 'El campo dirección es obligatorio',
            'phone.required' => 'El campo teléfono es obligatorio',
            'phone.regex' => 'El teléfono debe ser válido',
        ]);

        if ($validator->fails()) { 
            return response()->json(["response" => ["code" => -1, "data" => $validator->errors()]], 422);         
        } else {
            
            $user = new User();
        
            $user->forceFill([
                'first_name' => $request->get('first_name'),
                'last_name' => $request->get('last_name'),
                'email' => $request->get('email'),
                'password' => bcrypt($request->get('password')),
                'address' => $request->get('address'),
                'phone' => $request->get('phone'),
                'role' => 'user',
                'active' => true
            ]);
        
            $user->save();

            $token = JWTAuth::fromUser($user);

            $data = compact('user','token');
            return response()->json(["response" => ["code" => 1, "data" => $data]], 201);
            
        } 
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(["response" => ["code" => -1, "data" => "Credenciales no válidas"]], 400);         
            }
        } catch (JWTException $e) {
            return response()->json(["response" => ["code" => -1, "data" => "No se ha podido crear un token"]], 500);         
        }

        $user = DB::table('users')->where('email', $request->get('email'))->first();

        $data = compact('user','token');
        return response()->json(["response" => ["code" => 1, "data" => $data]], 200);
    }
}
