<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\User;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->user)],
            'password' => ['nullable', 'present', 'min:6'],
            'address' => 'required',
            'phone' => ['required', 'regex:/(\+34|0034|34)?[ -]*(6|7)[ -]*([0-9][ -]*){8}/'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'active' => Rule::in(['on', 'off'])
        ];
    }

    public function messages()
    {
        return [
            'first_name.required' => 'El campo nombre es obligatorio',
            'last_name.required' => 'El campo apellido es obligatorio',
            'email.required' => 'El campo email es obligatorio',
            'email.email' => 'El campo email debe ser válido',
            'email.unique' => 'El campo email debe ser único',
            'password.min' => 'El campo contraseña debe tener mínimo 6 caracteres',
            'address.required' => 'El campo dirección es obligatorio',
            'phone.required' => 'El campo teléfono es obligatorio',
            'phone.regex' => 'El teléfono debe ser válido',
            'role.required' => 'El campo rol es obligatorio',
            'role.in' => 'El rol debe ser válido',
            'active.in' => 'El campo habilitado debe ser válido'
        ];
    }

    public function updateUser(User $user)
    {

        $user->forceFill([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,                
            'email' => $this->email,
            'address' => $this->address,
            'phone' => $this->phone,
            'role' => $this->role ?? 'user',
            'active' => $this->active == "on" ? true : false
        ]);

        if($this->password != null){
            $user->password = bcrypt($this->password);
        }    

        $user->save();

    }
    
}
