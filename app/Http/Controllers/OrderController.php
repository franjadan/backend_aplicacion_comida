<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Order;
use App\User;
use App\Product;
use DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use DateTime;
use Carbon\Carbon;


class OrderController extends Controller
{

    //Función que genera la vista para listar el pedido
    public function index(Request $request){
        $orders = Order::query()
        ->where('state', 'pending')
        ->orderByDesc('order_date')
        ->orderByDesc('estimated_time')
        ->get();

        return view('orders.index', [
            'route' => "index",
            'orders' => $orders
        ]);
    }

    //Función que genera la vista para crear el pedido
    public function create()
    {
        $order = new Order;
        $users = User::query()
        ->where('role', "user")
        ->orderBy('first_name')
        ->get();
        $products = Product::query()
        ->orderBy('name')
        ->where('active', true)
        ->where('available', true)
        ->get();
        return view('orders.create', [
            'order' => $order,
            'users' => $users,
            'products' => $products
        ]);
    }

    //Función que guarda el pedido en la bd
    public function store(Request $request)
    {

        $rules = [
            'user_id' => ['nullable', 'present', Rule::exists('users', 'id')],
            'guest_name' => ['nullable', 'present'],
            'guest_address' => ['nullable', 'present'],
            'guest_phone' => ['nullable', 'present', 'regex:/(\+34|0034|34)?[ -]*(6|7|8|9)[ -]*([0-9][ -]*){8}/'],
            'estimated_time' => ['required', 'regex:/[0-9][0-9]:[0-9][0-9]/'],
            'comment' => ['nullable', 'present'],
            'paid' => ['required', 'boolean'] //Reglas
        ];

        $messages = [
            'user_id.present' => 'El campo usuario debe esta presente.',
            'user_id.exists' => 'El campo usuario no es válido.',
            'guest_name.present' => 'El campo nombre invitado debe estar presente.',
            'guest_address.present' => 'El campo dirección invitado debe estar presente.',
            'guest_phone.present' => 'El campo teléfono invitado debe estar presente.',
            'guest_phone.regex' => 'El campo teléfono invitado no es válido.',
            'estimated_time.required' => 'El campo hora de recogida es obligatorio.',
            'estimated_time.regex' => 'El campo hora de recogida no es válido.',
            'estimated_time.present' => 'El campo hora de recogida real debe estar presente.',
            'estimated_time.regex' => 'El campo hora de recogida real debe no es válido.',
            'comments.present' => 'El campo observaciones debe estar presente.',
            'paid.required' => 'El campo pagado es obligatorio.',
            'paid.boolean' => 'El campo pagado no es válido.' //Mensajes
        ];

        $lenght = $request->get('num');

        //Compruebo los productos que recibe
        for($i = 1; $i <= $lenght; $i++){
            //Añado la validación a los productos
            $rules["product_$i"] = [Rule::exists('products', 'id')];
            $rules["cant_$i"] = ['integer', 'min:1'];
            $messages["product_$i.exists"] = 'El producto debe ser válido';
            $messages["cant_$i.integer"] = 'La cantidad debe ser un entero';
            $messages["cant_$i.min"] = 'La cantidad debe ser mayor de 0';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {

            return redirect(route('orders.create'))->withErrors($validator)->withInput();;

        } else {

            if($request->get('user_id') != null){
                if(!empty($request->get('guest_name')) || !empty($request->get('guest_address')) || !empty($request->get('guest_phone'))){
                    $validator->getMessageBag()->add('user_id', 'El pedido sólo podrá realizarse con un usuario registrado o invitado.');
                    return back()->withErrors($validator)->withInput();
                }
            }else{
                if(empty($request->get('guest_name')) || empty($request->get('guest_address')) || empty($request->get('guest_phone'))){
                    $validator->getMessageBag()->add('user_id', 'Debe haber un usuario registrado o datos de invitado con todos sus datos completos');
                    return back()->withErrors($validator)->withInput();
                }
            }

            $products = [];

            for($i = 1; $i <= $lenght; $i++){
                if($request->get("cant_$i") != null && $request->get("product_$i")){
                    for ($j = 0; $j < $request->get('cant_' . $i); $j++) {
                        array_push($products, $request->get('product_' . $i)); //Guardo los productos en el array
                    }
                }
            }

            if(count($products) <= 0){
                $validator->getMessageBag()->add('products', 'Debe haber al menos un producto');
                return back()->withErrors($validator)->withInput();
            }

            $order = new Order();
            $dt = new DateTime();

            //Rellena el pedido con los datos
            $order->forceFill([
                'user_id' => $request->get('user_id'),
                'guest_name' => $request->get('guest_name'),
                'guest_address' => $request->get('guest_address'),
                'guest_phone' => $request->get('guest_phone'),
                'order_date' => $dt->format('Y-m-d H:i:s'),
                'estimated_time' => $request->get('estimated_time'),
                'state' => 'pending',
                'comment' =>$request->get('comment'),
                'paid' => $request->get('paid'),
            ]);

            $order->save();

            //Guarda los productos de este pedido
            $order->products()->attach($products);

            $total = 0;
            foreach($order->products as $product){
                $total += ($product->price - ($product->price * ($product->discount / 100)));
            }

            $order->total = $total;

            $order->save();

            return redirect()->route('orders.index')->with('success', 'Se han guardado los cambios');
        }
    }

    //Función que genera la vista para editar el pedido
    public function edit(Order $order)
    {
        if($order->state != "pending"){ //Sólo se pueden editar pedidos pendientes
            return redirect()->route('orders.index')->with('error', 'No puedes acceder a este pedido');
        }

        $users = User::query()
        ->where('role', "user")
        ->orderBy('first_name')
        ->get();
        $products = Product::query()
        ->orderBy('name')
        ->get();
        return view('orders.edit', [
            'order' => $order,
            'users' => $users,
            'products' => $products
        ]);
    }

    //Función que actualiza el pedido en la bd
    public function update(Request $request, Order $order)
    {
        $rules = [
            'user_id' => ['nullable', 'present', Rule::exists('users', 'id')],
            'guest_name' => ['nullable', 'present'],
            'guest_address' => ['nullable', 'present'],
            'guest_phone' => ['nullable', 'present', 'regex:/(\+34|0034|34)?[ -]*(6|7|8|9)[ -]*([0-9][ -]*){8}/'],
            'estimated_time' => ['required', 'regex:/[0-9][0-9]:[0-9][0-9]/'],
            'comment' => ['nullable', 'present'],
            'paid' => ['required', 'boolean'] //Reglas
        ];

        $messages = [
            'user_id.present' => 'El campo usuario debe estar presente.',
            'user_id.exists' => 'El campo usuario no es válido.',
            'guest_name.present' => 'El campo nombre invitado debe estar presente.',
            'guest_address.present' => 'El campo dirección invitado debe estar presente.',
            'guest_phone.present' => 'El campo teléfono invitado debe estar presente.',
            'guest_phone.regex' => 'El campo teléfono invitado no es válido.',
            'estimated_time.required' => 'El campo hora de recogida es obligatorio.',
            'estimated_time.regex' => 'El campo hora de recogida no es válido.',
            'estimated_time.present' => 'El campo hora de recogida real debe estar presente.',
            'estimated_time.regex' => 'El campo hora de recogida real no es válido.',
            'comments.present' => 'El campo observaciones debe estar presente.',
            'paid.required' => 'El campo pagado es obligatorio.',
            'paid.boolean' => 'El campo pagado no es válido.' //Mensajes
        ];

        $lenght = $request->get('num');

        //Compruebo los productos que recibe
        for($i = 1; $i <= $lenght; $i++){
            //Añado la validación a los productos
            $rules["product_$i"] = [Rule::exists('products', 'id')];
            $rules["cant_$i"] = ['integer', 'min:1'];
            $messages["product_$i.exists"] = 'El producto debe ser válido';
            $messages["cant_$i.integer"] = 'La cantidad debe ser un entero';
            $messages["cant_$i.min"] = 'La cantidad debe ser mayor de 0';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {

            return redirect(route('orders.edit', ['order' => $order]))->withErrors($validator)->withInput();

        } else {

            if($request->get('user_id') != null){
                if(!empty($request->get('guest_name')) || !empty($request->get('guest_address')) || !empty($request->get('guest_phone'))){
                    $validator->getMessageBag()->add('user_id', 'El pedido sólo podrá realizarse con un usuario registrado o invitado.');
                    return back()->withErrors($validator)->withInput();
                }
            }else{
                if(empty($request->get('guest_name')) || empty($request->get('guest_address')) || empty($request->get('guest_phone'))){
                    $validator->getMessageBag()->add('user_id', 'Debe haber un usuario registrado o datos de invitado.');
                    return back()->withErrors($validator)->withInput();
                }
            }

            $products = [];

            for($i = 1; $i <= $lenght; $i++){
                if($request->get("cant_$i") != null && $request->get("product_$i")){
                    for ($j = 0; $j < $request->get('cant_' . $i); $j++) {
                        array_push($products, $request->get('product_' . $i)); //Guardo los productos en el array
                    }
                }
            }

            if(count($products) <= 0){
                $validator->getMessageBag()->add('products', 'Debe haber al menos un producto');
                return back()->withErrors($validator)->withInput();
            }

            //Rellena el pedido con los datos
            $order->forceFill([
                'user_id' => $request->get('user_id'),
                'guest_name' => $request->get('guest_name'),
                'guest_address' => $request->get('guest_address'),
                'guest_phone' => $request->get('guest_phone'),
                'estimated_time' => $request->get('estimated_time'),
                'comment' =>$request->get('comment'),
                'paid' => $request->get('paid'),
            ]);

            $order->save();

            //Actualiza los productos
            $order->products()->detach();
            $order->products()->attach($products);

            $total = 0;
            foreach($order->products as $product){
                $total += ($product->price - ($product->price * ($product->discount / 100)));
            }

            $order->total = $total;

            $order->save();

            return redirect()->route('orders.edit', ['order' => $order])->with('success', 'Se han guardado los cambios');
        }
    }

    //Función para finalizar el pedido
    public function finish(Order $order){

        $order->state = 'finished';
        $order->paid = true; //El finalizar un pedidio quiere decir que este se ha pagado
        $order->real_time = Carbon::now()->format('H:i');

        $order->save();

        return redirect()->route('orders.index')->with('success', 'Se han finalizado el pedido');
    }

    //Función para cancelar el pedido
    public function cancel(Order $order){

        $order->state = 'cancelled';
        $order->save();

        return redirect()->route('orders.index')->with('success', 'Se han cancelado el pedido');
    }

    //Función que genera la vista de detalle del pedido
    public function show(Order $order)
    {
        return view('orders.show', [
            'order' => $order
        ]);
    }

    //Función que genera el listado del historial de pedidos
    public function orderRecord(Request $request)
    {
        //Muestra todos los que no sean pendientes
        $orders = Order::query()
        ->where('state', '<>', 'pending')
        ->orderByDesc('order_date')
        ->orderByDesc('estimated_time')
        ->get();

        return view('orders.index', [
            'route' => "record",
            'orders' => $orders
        ]);
    }

    //Función que guarda el pedido recibido por la API en la bd
    public function storeAPI(Request $request)
    {
        $rules = [
            'user_id' => ['nullable', Rule::exists('users', 'id')],
            'guest_name' => ['nullable'],
            'guest_address' => ['nullable'],
            'guest_phone' => ['nullable', 'regex:/(\+34|0034|34)?[ -]*(6|7|8|9)[ -]*([0-9][ -]*){8}/'],
            'guest_token' => ['nullable'],
            'products' => ['required', 'array', Rule::exists('products', 'id')],
            'estimated_time' => ['required', 'regex:/[0-9][0-9]:[0-9][0-9]/'],
            'comment' => ['nullable'],
            'paid' => ['required', 'boolean'] //Reglas
        ];

        $messages = [
            'user_id.exists' => 'El campo usuario debe ser válido',
            'products.required' => 'El campo productos es obligatorio',
            'products.exists' => 'El campo productos debe ser válido',
            'estimated_time.required' => 'El campo hora de recogida es obligatorio',
            'estimated_time.regex' => 'El campo hora de recogida debe ser válido',
            'estimated_time.present' => 'El campo hora de recogida real debe estar presente',
            'estimated_time.regex' => 'El campo hora de recogida real debe ser válido',
            'paid.required' => 'El campo pagado es obligatorio',
            'paid.boolean' => 'El campo pagado no es válido' //Mensajes
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(["response" => ["code" => -1, "data" => $validator->errors()]], 400);

        } else {

            if($request->get('user_id') != null){
                if(!empty($request->get('guest_name')) || !empty($request->get('guest_address')) || !empty($request->get('guest_phone')) || !empty($request->get('guest_token'))){
                    return response()->json(["response" => ["code" => -1, "data" => "El pedido sólo podrá realizarse con un usuario registrado o invitado"]], 422);
                }
            }else{
                if(empty($request->get('guest_name')) || empty($request->get('guest_address')) || empty($request->get('guest_phone')) || empty($request->get('guest_token'))){
                    return response()->json(["response" => ["code" => -1, "data" => "Debe haber un usuario registrado o datos de invitado"]], 422);
                }
            }
            
            if($request->get('user_id') != null){
                $user = User::query()->where('id', $request->get('user_id'))->first();

                if($user->active){
                    $order = new Order();
                    $dt = new DateTime();
        
                    $order->forceFill([
                        'user_id' => $request->get('user_id'),
                        'guest_name' => $request->get('guest_name'),
                        'guest_address' => $request->get('guest_address'),
                        'guest_phone' => $request->get('order_date'),
                        'guest_token' => $request->get('guest_token'),
                        'order_date' => $dt->format('Y-m-d H:i:s'),
                        'estimated_time' => $request->get('estimated_time'),
                        'state' => 'pending',
                        'comment' =>$request->get('comment'),
                        'paid' => $request->get('paid'),
                    ]);
        
                    $order->save();
        
                    $order->products()->attach($request->get('products'));
        
                    $total = 0;
                    foreach($order->products as $product){
                        $total += ($product->price - ($product->price * ($product->discount / 100)));
                    }
        
                    $order->total = $total;
        
                    $order->save();
        
                    return response()->json(["response" => ["code" => 1, "data" => $order->id]], 201);
                }else{
                    return response()->json(['response' => ['code' => -1, 'data' => 'El usuario ha sido deshabilitado por el sistema']], 401);
                }
            }else{
                $order = new Order();
                $dt = new DateTime();

                $order->forceFill([
                    'user_id' => $request->get('user_id'),
                    'guest_name' => $request->get('guest_name'),
                    'guest_address' => $request->get('guest_address'),
                    'guest_phone' => $request->get('order_date'),
                    'guest_token' => $request->get('guest_token'),
                    'order_date' => $dt->format('Y-m-d H:i:s'),
                    'estimated_time' => $request->get('estimated_time'),
                    'state' => 'pending',
                    'comment' =>$request->get('comment'),
                    'paid' => $request->get('paid'),
                ]);

                $order->save();

                $order->products()->attach($request->get('products'));

                $total = 0;
                foreach($order->products as $product){
                    $total += ($product->price - ($product->price * ($product->discount / 100)));
                }

                $order->total = $total;

                $order->save();

                return response()->json(["response" => ["code" => 1, "data" => $order->id]], 201);
            }
        }
    }

    //Función que guarda un pedido como favorito desde la API en la bd
    public function storeFavoriteOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'order_id' => ['required', Rule::exists('orders', 'id')],
            'favourite_order_name' => ['required'] //Reglas
        ], [
            'order_id.required' => 'El pedido es obligatorio',
            'order_id.exists' => 'El pedido debe ser válido',
            'favourite_order_name.required' => 'El campo nombre del pedido favorito es obligatorio' //Mensajes
        ]);

        if ($validator->fails()) {
            return response()->json(["response" => ["code" => -1, "data" => $validator->errors()]], 400);
        } else {
            $order = Order::query()
            ->where('id', $request->get('order_id'))
            ->first();

            if($order->user != null){
                if($order->favourite_order_name == null || $order->favourite_order_name == ""){

                    if($order->user->active){
                        if($order->user->id == auth()->user()->id){
                            $order->favourite_order_name = $request->get('favourite_order_name');
                            $order->save();

                            return response()->json(["response" => ["code" => 1, "data" => $order->id]], 200);
                        }else{
                            return response()->json(['response' => ['code' => -1, 'data' => 'No puedes modificar pedidos de otro usuario']], 401);
                        }
                    }else{
                        return response()->json(['response' => ['code' => -1, 'data' => 'El usuario ha sido deshabilitado por el sistema']], 401);
                    }

                }else{
                    return response()->json(["response" => ["code" => -1, "data" => "Ya ha sido registrado como favorito"]], 400);
                }
            }else{
                return response()->json(["response" => ["code" => -1, "data" => "Sólo los pedidos realizados por un usuario podrán ser favoritos"]], 400);
            }
        }
    }

    //Función que quita un pedido como favorito desde la API en la bd
    public function cancelFavoriteOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'order_id' => ['required', Rule::exists('orders', 'id')],
        ], [
            'order_id.required' => 'El pedido es obligatorio',
            'order_id.exists' => 'El pedido debe ser válido',
        ]);

        if ($validator->fails()) {
            return response()->json(["response" => ["code" => -1, "data" => $validator->errors()]], 400);
        } else {
            $order = Order::query()
            ->where('id', $request->get('order_id'))
            ->first();

            if($order->favourite_order_name != null || $order->favourite_order_name != ""){

                if($order->user->active){
                    if($order->user->id == auth()->user()->id){
                        $order->favourite_order_name = null;
                        $order->save();

                        return response()->json(["response" => ["code" => 1, "data" => $order->id]], 200);
                    }else{
                        return response()->json(['response' => ['code' => -1, 'data' => 'No puedes modificar pedidos de otro usuario']], 401);

                    }
                }else{
                    return response()->json(['response' => ['code' => -1, 'data' => 'El usuario ha sido deshabilitado por el sistema']], 401);
                }
 
            }else{
                return response()->json(["response" => ["code" => -1, "data" => "Este pedido no consta como favorito"]], 400);
            }
        }
    }

    //Función que lista los pedidos favoritos de un usuario a la API
    public function listFavoriteOrders(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => ['nullable', Rule::exists('users', 'id')], //Reglas
        ], [
            'user_id.exists' => 'El campo usuario debe ser válido', //Mensajes
        ]);

        if ($validator->fails()) {
            return response()->json(["response" => ["code" => -1, "data" => $validator->errors()]], 400);
        } else {
            if($request->get('user_id') != null){
                $user = User::query()->where('id', $request->get('user_id'))->first();

                if($user->active){
                    $orders = Order::query()
                        ->where('user_id', $request->get('user_id'))
                        ->whereNotNull('favourite_order_name')
                        ->get();

                    return response()->json(["response" => ["code" => 1, "data" => OrderResource::collection($orders)]], 200);
                }else{
                    return response()->json(['response' => ['code' => -1, 'data' => 'El usuario ha sido deshabilitado por el sistema']], 401);
                }
            }else{
                return response()->json(["response" => ["code" => 1, "data" => []]], 200);
            }
        }
    }

    //Función que lista los últimos pedidios pendientes de un usuario a la API
    public function lastOrders(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => ['nullable', Rule::exists('users', 'id')],
            'guest_token' => ['nullable', Rule::exists('orders', 'guest_token')] //Reglas
        ], [
            'user_id.exists' => 'El campo usuario debe ser válido',
            'guest_token.exists' => 'El campo token del invitado debe ser válido', //Mensajes
        ]);

        if ($validator->fails()) {
            return response()->json(["response" => ["code" => -1, "data" => $validator->errors()]], 400);
        } else {

            if($request->get('guest_token') != null){
                $orders = Order::query()
                    ->where('guest_token', $request->get('guest_token'))
                    ->where('state', 'pending')
                    ->get();

                    return response()->json(["response" => ["code" => 1, "data" => OrderResource::collection($orders)]], 200);
            }else{
                if($request->get('user_id') != null){
                    $orders = Order::query()
                        ->where('user_id', $request->get('user_id'))
                        ->where('state', 'pending')
                        ->get();

                        return response()->json(["response" => ["code" => 1, "data" => OrderResource::collection($orders)]], 200);
                }else{
                    return response()->json(["response" => ["code" => 1, "data" => []]], 200);
                }
            }
        }
    }

    //Función que cancela un pedido desde la API
    public function cancelOrderAPI(Request $request){

        $validator = Validator::make($request->all(), [
            'order_id' => ['required', Rule::exists('orders', 'id')],
            'user_id' => ['nullable', Rule::exists('users', 'id')],
            'guest_token' => ['nullable', Rule::exists('orders', 'guest_token')] //Reglas
        ], [
            'order_id.required' => 'El pedido es obligatorio',
            'order_id.exists' => 'El pedido debe ser válido',
            'user_id.exists' => 'El campo usuario debe ser válido',
            'guest_token.exists' => 'El campo token del invitado debe ser válido', //Mensajes
        ]);

        if ($validator->fails()) {
            return response()->json(["response" => ["code" => -1, "data" => $validator->errors()]], 400);
        } else {

            if($request->get('guest_token') != null){
                $order = Order::query()
                    ->where('guest_token', $request->get('guest_token'))
                    ->where('state', 'pending')
                    ->where('id', $request->get('order_id'))
                    ->first();

                if($order != null){
                    $order->state = 'cancelled';
                    $order->save();

                    return response()->json(["response" => ["code" => 1, "data" => $order->id]], 200);
                }else{
                    return response()->json(["response" => ["code" => -1, "data" => "No se ha podido acceder al pedido"]], 422);
                }

            }else{
                if($request->get('user_id') != null){
                    $user = User::query()->where('id', $request->get('user_id'))->first();

                    if($user->active){
                        $order = Order::query()
                            ->where('user_id', $request->get('user_id'))
                            ->where('state', 'pending')
                            ->where('id', $request->get('order_id'))
                            ->first();

                        if($order != null){
                            $order->state = 'cancelled';
                            $order->save();

                            return response()->json(["response" => ["code" => 1, "data" => $order->id]], 200);
                        }else{
                            return response()->json(["response" => ["code" => -1, "data" => "No se ha podido acceder al pedido"]], 422);
                        }
                    }else{
                        return response()->json(['response' => ['code' => -1, 'data' => 'El usuario ha sido deshabilitado por el sistema']], 401);
                    }
                }else{
                    return response()->json(["response" => ["code" => -1, "data" => "Necesitas añadir el token de invitado o el id de usuario del pedido"]], 400);
                }
            }
        }
    }

    public function refresh()
    {
        $orders = Order::all();

        return response()->json(['response' => ['code' => 1, 'data' => count($orders)]]);
    }

}
