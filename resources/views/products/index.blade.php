@extends('layout')

@section('title', 'Listado de productos')

@section('content')
    <h1>Listado de productos</h1>

    <div>
        <a href="{{ route('products.create') }}" class="btn btn-primary mb-3">Nuevo Producto</a>
    </div>

    @if(!$products->isEmpty())

        <table class="table table-bordered data-table">
            <thead class="thead-dark">
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Precio</th>
                    <th scope="col">Descuento</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>

    @else
        <p class="mt-3">No hay productos registrados</p>
    @endif

@endsection

@section('datatable')

<script type="text/javascript">
  $(function () {
    var table = $('.data-table').DataTable({
        "language": {
            "sProcessing":    "Procesando...",
            "sLengthMenu":    "Mostrar _MENU_ registros",
            "sZeroRecords":   "No se encontraron resultados",
            "sEmptyTable":    "Ningún dato disponible en esta tabla",
            "sInfo":          "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty":     "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered":  "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix":   "",
            "sSearch":        "Buscar:",
            "sUrl":           "",
            "sInfoThousands":  ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":    "Último",
                "sNext":    "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        },
        processing: true,
        serverSide: true,
        ajax: "{{ route('products') }}",
        columns: [
            {data: 'id', name: 'id'},
            {data: 'name', name: 'name'},
            {data: 'price', name: 'price'},
            {data: 'discount', name: 'discount'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false},
        ]
    });

    $(".dataTables_filter input")
    .unbind() // Unbind previous default bindings
    .bind("input", function(e) { // Bind our desired behavior
		// If the length is 2 or more characters, search
		if(this.value.length >= 2) {
            // Call the API search function
            table.search(this.value).draw();
        }
        // Ensure we clear the search if they backspace far enough
        if(this.value == "") {
            table.search("").draw();
        }
        return;
    });
  });
  </script>

@endsection
