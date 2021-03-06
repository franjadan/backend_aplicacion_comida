@section('scripts')
    <script src="{{ asset('js/multi-select.js') }}"></script>
@endsection

{{ csrf_field() }}

<div class="form-group">
    <label for="inputName">Nombre*</label>
    <input type="text" class="form-control" id="inputName" name="name" value="{{ old('name', $product->name) }}" placeholder="Hamburguesa con queso">
    @if($errors->has('name'))
        <div class="alert alert-danger mt-2">{{ $errors->first('name') }}</div>
    @endif
</div>
<div class="form-group">
    <label for="inputDescription">Descripción*</label>
    <textarea name="description" id="inputDescription" cols="5" rows="5" class="form-control" placeholder="Magnígica hamburguesa con extra de queso...">{{ old('description', $product->description) }}</textarea>
    @if($errors->has('description'))
        <div class="alert alert-danger mt-2">{{ $errors->first('description') }}</div>
    @endif
</div>
<div class="form-group">
    <label for="inputPrice">Precio*</label>
    <div class="input-group">
        <input type="text" class="form-control" id="inputPrice" name="price" value="{{ old('price', $product->price) }}" placeholder="10.0">
        <div class="input-group-append">
            <span class="input-group-text">€</span>
        </div>
    </div>
    @if($errors->has('price'))
        <div class="alert alert-danger mt-2">{{ $errors->first('price') }}</div>
    @endif
</div>
<div class="form-group">
    <label for="inputDiscount">Descuento</label>
    <div class="input-group">
        <input type="text" class="form-control" id="inputDiscount" name="discount" value="{{ old('discount', $product->discount) }}" placeholder="0.00">
        <div class="input-group-append">
            <span class="input-group-text">%</span>
        </div>
    </div>
    @if($errors->has('discount'))
        <div class="alert alert-danger mt-2">{{ $errors->first('discount') }}</div>
    @endif
</div>
<div class="form-group">
    <p>¿Está disponible?*</p>
    <div class="form-check">
        <input type="radio" class="form-check-input" name="available" id="available_yes" value="yes" {{ $product->available || old('available') == 'yes' ? 'checked': '' }}>
        <label for="form-check-label" for="available_yes">Sí</label>
    </div>
    <div class="form-check">
        <input type="radio" class="form-check-input" name="available" id="available_no" value="no" {{ !$product->available || old('available') == 'no' ? 'checked': '' }}>
        <label for="form-check-label" for="available_no">No</label>
    </div>
    @if($errors->has('available'))
        <div class="alert alert-danger mt-2">{{ $errors->first('available') }}</div>
    @endif
</div>
<div class="form-group">
    <label for="selectCategories">Categorías*</label>
    @if ($categories->isNotEmpty())
        <select name="categories[]" id="selectCategories" class="form-control multi-select" multiple>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" {{ collect(old('categories', $product->categories->pluck('id')->toArray()))->contains($category->id) ? 'selected': '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
    @else
        <p>No hay categorías registradas.</p>
    @endif
    @if($errors->has('categories'))
        <div class="alert alert-danger mt-2">{{ $errors->first('categories') }}</div>
    @endif
</div>
<div class="form-group">
    <label for="selectIngredients">Ingredientes</label>
    @if ($ingredients->isNotEmpty())
        <select name="ingredients[]" id="selectIngredients" class="form-control select-categories multi-select" multiple>
            @foreach ($ingredients as $ingredient)
                <option value="{{ $ingredient->id }}" {{ collect(old('ingredients', $product->ingredients->pluck('id')->toArray()))->contains($ingredient->id) ? 'selected': '' }}>{{ $ingredient->name }}</option>
            @endforeach
        </select>
    @else
        <div class="alert alert-secondary">
            <p class="mt-3">No hay ingredientes registrados.</p>
        </div>
    @endif
    @if($errors->has('ingredients'))
        <div class="alert alert-danger mt-2">{{ $errors->first('ingredients') }}</div>
    @endif
</div>
<div class="form-group">
    <label for="inputImage">Imagen*</label>
    <div class="input-group">
        <div class="custom-file">
            <input type="file" accept="image/*" name="image" class="form-control-file" id="inputImage">
            <label for="inputImage" class="custom-file-label">Seleciona una imagen...</label>
        </div>
    </div>
    @if($errors->has('image'))
        <div class="alert alert-danger mt-2">{{ $errors->first('image') }}</div>
    @endif
</div>
