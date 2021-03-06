<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Ingredient;

class CreateIngredientRequest extends FormRequest
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
            'name' => ['required', 'min:2', 'regex:/^[\pL\s\-]+$/u', 'unique:ingredients,name'],
            'allergens' => ['nullable', 'array', 'exists:allergens,id'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.min' => 'El campo nombre debe tener más de dos caracteres.',
            'name.regex' => 'El campo nombre no es válido.',
            'name.unique' => 'El campo nombre no puede coincidir con el de otro ingrediente.',
            'allergens.array' => 'El campo alérgenos no es válido.',
            'allergens.exists' => 'El campo alérgenos no es válido.',
        ];
    }

    public function createIngredient()
    {
        $ingredient = Ingredient::create([
            'name' => $this['name'],
        ]);

        $ingredient->allergens()->attach($this['allergens']);

    }
}
