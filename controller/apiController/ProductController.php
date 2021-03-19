<?php

require_once __DIR__ . "/ApiController.php";

/*
 * ==================================
 * Inicio Metodos 
 * ==================================
 */

function listaProductos($request) {

    $dataClass = new Product();
    $response = $dataClass->getProducts();
    responseValidator($response);
    $fullData = $response->getContent();

    $html = '';

    foreach ($fullData as $product) {
        $paramsProduct = '\'' . $product['id'] . '\',\'' . $product['nombre'] . '\',\'' . $product['referencia'] . '\',\'' . $product['precio'] . '\',\'' . $product['peso'] . '\',\'' . $product['categoria'] . '\',\'' . $product['stock'] . '\'';
        $actions = '<div class="actions-btn">
                    <button class="btn btn btn-outline-success mb-2 mr-1" onclick="editModal(' . $paramsProduct . ')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn btn-outline-danger mb-2" onclick="deleteModal(' . $product['id'] . ')"><i class="fas fa-times"></i></button>
                 </div>';
        $fechaCreacion = $product['fecha_creacion'];
        $fechaVenta = $product['fecha_ult_venta'];

        $fechaVenta = $fechaVenta == '' ? 'Nunca' : dateFormatBeautiful($fechaVenta, true, false, false);
        $fechaCreacion = $fechaCreacion == '' ? 'Nunca' : dateFormatBeautiful($fechaCreacion, true, false, false);

        $html.='<tr>
                    <th>' . $product['id'] . '</th>
                    <td>' . $product['nombre'] . '</td>
                    <td>' . $product['referencia'] . '</td>
                    <td>' . $product['precio'] . '</td>
                    <td>' . $product['peso'] . '</td>
                    <td>' . $product['categoria'] . '</td>
                    <td>' . $product['stock'] . '</td>
                    <td>' . $fechaCreacion . '</td>
                    <td>' . $fechaVenta . '</td>
                    <td>' . $actions . '</td>
                  </tr>
                  <tr>';
    }

    returnResponse($html);
}

function editarProducto($request) {

    //validaciones
    $rules = [
        'nombre' => ['nombre', 'required'],
        'referencia' => ['referencia', 'required'],
        'precio' => ['precio', 'required|numeric'],
        'peso' => ['peso', 'required|numeric'],
        'categoria' => ['categoria', 'required'],
        'stock' => ['stock', 'required|numeric'],
    ];
    validate($request, $rules);

    $idProducto = $request['id'];
    $fecha = $date = App::fecha('Y-m-d H:i:s');

    $dataClass = new Product();
    $request = [
        'id' => $idProducto,
        'nombre' => $request['nombre'],
        'referencia' => $request['referencia'],
        'precio' => $request['precio'],
        'peso' => $request['peso'],
        'categoria' => $request['categoria'],
        'stock' => $request['stock'],
        'fecha_creacion' => $fecha
    ];
    if ($idProducto == '0') {
        $response = $dataClass->crearProducto($request);
    } else {
        $response = $dataClass->actualizarProducto($request);
    }
    responseValidator($response);
    returnResponse();
}

function venderProducto($request) {

    //validaciones
    $rules = [
        'id' => ['Producto', 'required'],
        'cantidad' => ['cantidad', 'required|numeric']
    ];
    validate($request, $rules);
    $idProducto = $request['id'];

    $idProducto = $request['id'];
    $cantidad = $request['cantidad'];
    $fecha = $date = App::fecha('Y-m-d H:i:s');

    $response = content('productos', 'id', $idProducto);
    if ($response->getCount() <= 0) {
        throwError('No se encontro producto seleccionado');
    }

    $contentProduct = $response->getContent()[0];

    $cantidadActual = $contentProduct['stock'];

    $nuevoStock = $cantidadActual - $cantidad;

    if ($nuevoStock < 0) {
        throwError('No hay productos en Stock para realizar la venta');
    }

    $dataClass = new Product();
    $request = [
        'id' => $idProducto,
        'stock' => $nuevoStock,
        'fecha_ult_venta' => $fecha
    ];
    $response = $dataClass->actualizarProductoVenta($request);
    responseValidator($response);
    returnResponse();
}

function eliminarProducto($request) {

    $dataClass = new Product();
    $request = [
        'id' => $request['id']
    ];
    $response = $dataClass->deleteProduct($request);
    responseValidator($response);
    returnResponse();
}

function listaProductosSelect($request) {
    try {
        $dataClass = new Product();
        $response = $dataClass->getProducts();
        responseValidator($response);
        $content = $response->getContent();
        $select = '';
        foreach ($content as $value) {
            $select.='<option value="' . $value['id'] . '">' . $value['nombre'] . '</option>';
        }
        returnResponse($select);
    } catch (Exception $e) {
        $message = MSJ_ERROR_PROCESS;
        $other = array("params" => func_get_args());
        $function = __FUNCTION__;
        return throwException($e, $message, $function, $other);
    }
}
