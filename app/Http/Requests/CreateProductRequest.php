<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Product;

class CreateProductRequest extends FormRequest
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
            'description' => ['required', 'min:5', 'regex:/^[\pL\s\-\.]+$/u'],
            'available' => ['required'],
            'image' => ['required', 'image'],
            'name' => ['required', 'min:2', 'regex:/^[\pL\s\-]+$/u'],
            'price' => ['required', 'numeric'],
            'discount' => ['nullable', 'numeric', 'present'],
            'categories' => ['required', 'array', 'exists:categories,id'],
            'ingredients' => ['required', 'array', 'exists:ingredients,id'],
        ];
    }

    public function messages()
    {
        return [
            'description.required' => 'El campo descripción es obligatorio.',
            'description.min' => 'El campo descripción debe tener mínimo 5 caracteres.',
            'description.regex' => 'El campo descripción no es válido.',
            'available.required' => 'El campo disponible es obligatorio.',
            'image.required' => 'El campo imagen es obligatorio.',
            'image.image' => 'El campo imagen no es válido.',
            'name.required' => 'El campo nombre es obligatorio.',
            'name.min' => 'El campo nombre debe tener mínimo 2 caracteres.',
            'name.regex' => 'El campo nombre no es válido.',
            'price.required' => 'El campo precio es obligatorio.',
            'price.numeric' => 'El campo precio no es válido.',
            'discount.numeric' => 'El campo descuendo no es válido.',
            'discount.present' => 'El campo descuento debe estar presente.',
            'categories.required' => 'El campo categorías es obligatorio.',
            'categories.array' => 'El campo categorías no es válido.',
            'categories.exists' => 'El campo categorías no es válido.',
            'ingredients.required' => 'El campo ingredientes es obligatorio',
            'ingredients.array' => 'El campo ingredientes no es válido.',
            'categories.exists' => 'El campo ingredientes no es válido.',
        ];
    }

    public function createProduct()
    {
        $image = $this->file('image');
        $name = $image->getClientOriginalName();
        if (!@getimagesize(public_path()."/$name")){
            $image->move('media/products', $name);
        }

        $available = $this['available'] == 'yes'? true: false;

        $product = Product::create([
            'description' => $this['description'],
            'available' => $available,
            'image' => "media/products/$name",
            'name' => $this['name'],
            'price' => $this['price'],
            'discount' => $this['discount'],
        ]);

        $product->categories()->attach($this['categories']);
        $product->ingredients()->attach($this['ingredients']);
    }
}
