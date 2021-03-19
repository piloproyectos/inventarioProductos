/* GLOBALS */
$(document).ready(function () {
    listaProductos();
    listaProductosSelect();
});


function listaProductos() {
    var data = {
        mtrx: "listaProductos",
        function: "listaProductos",
    };
    /*===Validation Request======*/
    if (!requestValidator(data))
        return;
    /*---------------------------*/
    sendRequest("Product", data, false).success(function (response) {
        /*=====Validation Initial=============*/
        if (!responseValidator(response, data))
            return;
        /* ===================
         * Fuction request Success results
         * ===================*/
        $('.listProducts').html(response.content);

    });
}

function listaProductosSelect() {
    var data = {
        mtrx: "listaProductosSelect",
        function: "listaProductosSelect",
    };
    /*===Validation Request======*/
    if (!requestValidator(data))
        return;
    /*---------------------------*/
    sendRequest("Product", data, false).success(function (response) {
        /*=====Validation Initial=============*/
        if (!responseValidator(response, data))
            return;
        /* ===================
         * Fuction request Success results
         * ===================*/
        $('.productSelect').html(response.content);

    });
}

function deleteModal(id) {
    $('#idProductDelete').val(id);
    $('#deleteModal').modal('show');
}

function saleModal() {
    $('#saleModal').modal('show');
    setInputForm('cantidad', '1');
}

function editModal(id,nombre,referencia,precio,peso,categoria,stock) {
    setInputForm('nombre', nombre);
    setInputForm('referencia', referencia);
    setInputForm('precio', precio);
    setInputForm('peso', peso);
    setInputForm('categoria', categoria);
    setInputForm('stock', stock);
    
    $('#idProductEdit').val(id);
    $('#editModal').modal('show');
}

function createModal() {
    limpiarModal();
    $('#idProductEdit').val(0);
    $('#editModal').modal('show');
}

function limpiarModal() {
    $('#idProductEdit').val(0);
    setInputForm('nombre', '');
    setInputForm('referencia', '');
    setInputForm('precio', '');
    setInputForm('peso', '');
    setInputForm('categoria', '');
    setInputForm('stock', '');
}

function confirmDelete() {
    var idProductDelete = $('#idProductDelete').val();
    var data = {
        id: idProductDelete,
        mtrx: "eliminarProducto",
        function: "eliminarProducto",
    };
    /*===Validation Request======*/
    if (!requestValidator(data))
        return;
    /*---------------------------*/
    sendRequest("Product", data, false).success(function (response) {
        /*=====Validation Initial=============*/
        $('#idProductDelete').val(0);
        $('#deleteModal').modal('hide');
        if (!responseValidator(response, data))
            return;
        /* ===================
         * Fuction request Success results
         * ===================*/
        result('Se elimino producto con exito');
        listaProductos();
        listaProductosSelect();

    });
}

function confirmEdit() {
    var idProductEdit = $('#idProductEdit').val();
    var data = {
        id: idProductEdit,
        nombre: inputForm('nombre'),
        referencia: inputForm('referencia'),
        precio: inputForm('precio'),
        peso: inputForm('peso'),
        categoria: inputForm('categoria'),
        stock: inputForm('stock'),
        mtrx: "editarProducto",
        function: "editarProducto",
    };
    /*===Validation Request======*/
    if (!requestValidator(data))
        return;
    /*---------------------------*/
    sendRequest("Product", data, false).success(function (response) {
        /*=====Validation Initial=============*/
        $('#idProductEdit').val(0);
        $('#editModal').modal('hide');
        if (!responseValidator(response, data))
            return;
        /* ===================
         * Fuction request Success results
         * ===================*/
        result('Se realizó acción con exito');
        listaProductos();
        listaProductosSelect();
    });
}



function confirmSale() {
    var data = {
        id: inputFormSelect('productSelect'),
        cantidad: inputForm('cantidad'),
        mtrx: "venderProducto",
        function: "venderProducto",
    };
    /*===Validation Request======*/
    if (!requestValidator(data))
        return;
    /*---------------------------*/
    sendRequest("Product", data, false).success(function (response) {
        /*=====Validation Initial=============*/
        $('#saleModal').modal('hide');
        if (!responseValidator(response, data))
            return;
        /* ===================
         * Fuction request Success results
         * ===================*/
        result('Se realizó acción con exito');
        listaProductos();
        listaProductosSelect();
    });
}
