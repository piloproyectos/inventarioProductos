<?php

/*
 * incluye el modelo con los metodos y acceso 
 * a la base de datos
 */
require_once __DIR__ .'/../CustomController.php';
require_once __DIR__ ."/../../model/Product.php";
/**
 * Recibe por POST el metodo segun
 * el proceso que vaya a realizar
 */
if (isset($_POST["mtrx"])) {
    $metodo = $_POST["mtrx"];
    $metodo = strtolower($metodo);
    if ($metodo == 'crear' || $metodo == 'actualizar' || $metodo == 'eliminar' ||
            $metodo == 'entity' || $metodo == 'content' || $metodo == 'init' ||
            $metodo == 'autorize') {
        throwError('No está autorizado para ejecutar esta opción');
    }
}
//si no recibe metodo
else {
    throwError('Error, no existe dirección');
}

function init($request = null, $rules = null) {
    if ($rules != null) {
        validate($request, $rules);
    }
    $GLOBALS['FUNCTION_PARAM'] = $request;
    if (isset($request['url_server'])) {
        addParamSesion('url_server', $request['url_server']);
    }
}

//Verifica los parametros que se envian por GET O POST
function request($parameter = null, $required = REQUIRED) {
    $data = $GLOBALS['FUNCTION_PARAM'];
    if ($parameter == null) {
        return $data;
    }
    //Verifica los parametros;
    if (isset($data[$parameter])) {
        return $data[$parameter];
    }
    if ($required == REQUIRED) {
        throwError("No se encontró parametro " . $parameter);
    } else {
        return null;
    }
}

function responseValidator($response) {
    $status = $response->getStatus();
    if (!$status) {

        $status = 0;
        $content = null;
        $count = 0;
        $error = $response->getError();
        $other = $response->getOther();

        $type = 'ERROR_CONTROLLER';
        if ($response->getError()['origen'] == '300') {
            $type = TYPE_ERROR_MODEL;
            if ($error['type'] == '102') {
                $type = TYPE_LOG_MODEL;
                $content = $error['message'];
                $error['message'] = 'This is LOG, view Content';
            }
        }

        $response = array(
            "status" => $status,
            "type" => $type,
            "content" => $content,
            "count" => $count,
            "error" => $error,
            "other" => $other
        );
        die(json_encode($response, JSON_UNESCAPED_UNICODE));
    }
}

function returnResponse($content = null, $type = TYPE_BOOLEAN, $otherParam = null) {
    $entity = null;
    $count = 0;
    if (is_object($content)) {
        $entity = $content->getEntity();
        $count = $content->getCount();
        $content = $content->getContent();
    }
    $other = otherParams($otherParam);
    $status = 1;
    $response = array(
        "status" => $status,
        "type" => $type,
        "content" => $content,
        "entity" => $entity,
        "count" => $count,
        "error" => null,
        "other" => $other
    );
    $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    die($jsonResponse);
}

function toDie($content_error, $otherParam = null) {

    //print_r($content_error);
    //die();

    if (is_object($content_error)) {
        $content_error = (array) $content_error;
    }
    $type_error = 103;
    $other = otherParams($otherParam);
    $status = 0;
    $type = TYPE_LOG;
    $content = $content_error;
    $count = 0;
    $message_exception = null;

    $error = array("message" => 'This is LOG, view Content',
        "exception" => $message_exception,
        "type" => $type_error,
        "origen" => 200,
        "method" => null,
        "line" => null,
        "code" => null
    );

    $response = array(
        "status" => $status,
        "type" => $type,
        "content" => $content,
        "count" => $count,
        "error" => $error,
        "other" => $other
    );
    $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
    $jsonResponse = str_replace('\u0000', ' ', $jsonResponse);
    die($jsonResponse);
}

function throwError($message, $otherParam = null, $message_exception = null, $origen = 200) {

    $other = otherParams($otherParam);
    $status = 0;
    $type_error = 104;
    $type = TYPE_ERROR;
    $content = null;
    $count = 0;

    $error = array("message" => $message,
        "exception" => $message_exception,
        "type" => $type_error,
        "origen" => $origen,
        "method" => null,
        "line" => null,
        "code" => null
    );

    $response = array(
        "status" => $status,
        "type" => $type,
        "content" => $content,
        "count" => $count,
        "error" => $error,
        "other" => $other
    );
    die(json_encode($response, JSON_UNESCAPED_UNICODE));
}

function throwException($e, $message, $function, $otherParam = null, $type_error = 100) {

    $other = otherParams($otherParam);
    $status = 0;

    $type = TYPE_ERROR;
    $content = null;
    $count = 0;
    $message_errror = $e->getMessage();

    $error = array("messaje" => $message,
        "exception" => $message_errror,
        "type" => $type_error,
        "origen" => 200,
        "method" => $function,
        "line" => $e->getLine(),
        "code" => $e->getCode()
    );

    $response = array(
        "status" => $status,
        "type" => $type,
        "content" => $content,
        "count" => $count,
        "error" => $error,
        "other" => $other
    );
    die(json_encode($response, JSON_UNESCAPED_UNICODE));
}


//Ejectua el metodo
$metodo($_POST);
