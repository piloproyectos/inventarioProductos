<?php

/**
 * Clase Cart
 * @author David
 */
require_once "Util.php";

class Product extends Util {

    /**
     * Metodo constructor de la clase
     */
    function __construct($id = null) {
        $this->init();
        $this->setEntity([
            'id' => $id,
        ]);
    }

    function getProducts() {
        $query = 'select *
                 from
                 productos;';
        $data = [
            'query' => $query,
            'function' => __FUNCTION__,
            'args' => func_get_args(),
            'messageError' => self::MSJ_ERROR_SELECTS,
        ];
        return $this->sendQuery($data);
    }
    
    function deleteProduct($request) {
        $query = 'delete from productos where id=?';
        $param = [
            $request['id']
        ];
        $data = [
            'query' => $query,
            'parameters' => $param,
            'function' => __FUNCTION__,
            'args' => func_get_args(),
            'messageError' => self::MSJ_ERROR_UPDATE,
        ];
        return $this->sendQuery($data);
    }
    
    function crearProducto($request) {
        $query = 'INSERT INTO productos(nombre, referencia, precio, peso, categoria,
                 stock, fecha_creacion, fecha_ult_venta) VALUES 
                 (?,?,?,?,?,?,?,?)';
        $param = [
            $request['nombre'],
            $request['referencia'],
            $request['precio'],
            $request['peso'],
            $request['categoria'],
            $request['stock'],
            $request['fecha_creacion'],
            null,
        ];
        $data = [
            'query' => $query,
            'parameters' => $param,
            'function' => __FUNCTION__,
            'args' => func_get_args(),
            'messageError' => self::MSJ_ERROR_INSERT,
        ];
        return $this->sendQuery($data);
    }
    
    function actualizarProducto($request) {
        $query = 'UPDATE productos SET nombre=?,referencia=?,
                precio=?,peso=?,categoria=?,stock=?
                WHERE id=?';
        $param = [
            $request['nombre'],
            $request['referencia'],
            $request['precio'],
            $request['peso'],
            $request['categoria'],
            $request['stock'],
            $request['id']
        ];
        $data = [
            'query' => $query,
            'parameters' => $param,
            'function' => __FUNCTION__,
            'args' => func_get_args(),
            'messageError' => self::MSJ_ERROR_INSERT,
        ];
        return $this->sendQuery($data);
    }
    
    function actualizarProductoVenta($request) {
        $query = 'UPDATE productos SET stock=?,fecha_ult_venta=?
                WHERE id=?';
        $param = [
            $request['stock'],
            $request['fecha_ult_venta'],
            $request['id']
        ];
        $data = [
            'query' => $query,
            'parameters' => $param,
            'function' => __FUNCTION__,
            'args' => func_get_args(),
            'messageError' => self::MSJ_ERROR_INSERT,
        ];
        return $this->sendQuery($data);
    }

}
