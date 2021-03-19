<?php

/*
 * incluye el modelo categoria, con los metodos y acceso 
 * a la base de datos
 */
require_once __DIR__ . "/../app/config.php";

        const TYPE_OBJECT = 'OBJECT';
        const TYPE_BOOLEAN = 'BOOLEAN';
        const TYPE_ARRAY = 'ARRAY';
        const TYPE_STRING = 'STRING';
        const TYPE_JSON = 'JSON';
        const TYPE_ERROR = 'ERROR_CTL';
        const TYPE_ERROR_MODEL = 'ERROR_MODEL';
        const TYPE_LOG = 'LOG_CTL';
        const TYPE_LOG_MODEL = 'ERROR_MODEL';
        const TYPE_HTML = 'HTML';

        const ROL_USUARIO = '5';
        const ROL_ADMIN = '6';
        const ROL_PUBLIC = '7';

        const IS_ADMIN = '1';
        const IS_NOT_ADMIN = '0';

        const STATUS_ACTIVE = '1';
        const STATUS_INACTIVE = '0';

        const IS_ENTITY = '1';
        const IS_NOT_ENTITY = '0';

        const REQUIRED = '1';
        const NOTREQUIRED = '0';

        const MAX_PAGINATION = '500';

        const LOG_ERROR = '0';
        const LOG_SUCCESS = '1';
        const MSJ_ERROR_PROCESS = 'Ocurrio un error realizando la operacion.';


/*
 * Valida la fecha que llega por parametro con la hora del servidor
 */

function checkFecha($dateLocal) {
    $accion = "Validación de Fecha";
    $fecha_actual = date_create(App::fecha('Y-m-d H:i:s'));
    $fecha_entrada = date_create($dateLocal);

    $fecha_actual_st = $fecha_actual->format('Y-m-d H:i:s');
    $fecha_entrada_st = $fecha_entrada->format('Y-m-d H:i:s');

    $interval = date_diff($fecha_actual, $fecha_entrada);
    $interval_st = $interval->format('%R%a dias, %H horas, %I minutos, %S segundos');
    $interval = $interval->format('%a%H%I');
    $msj = "Fecha actual Server: " . $fecha_actual_st . " ,
         Fecha Entrada (Hora Local): " . $fecha_entrada_st . " ,
         diferencia: " . $interval_st . " st: " . $interval;
    if ($interval < 10) {
        return true;
    } else {
        registerLog($accion, $msj, LOG_ERROR);
        throwError("Estado Actual: Error. " . $msj);
    }
}

function validarDateTime($date, $format = 'Y-m-d H:i:s') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function validarTime($time, $format = 'H:i:s') {
    $d = DateTime::createFromFormat($format, $time);
    return $d && $d->format($format) == $time;
}

function otherParams($otherData) {
    //Se remplaza clave para que no se devuleva a cliente
    if (isset($_POST['clave'])) {
        $_POST['clave'] = 'xxxx';
    }
    if (is_array($otherData)) {
        $other['received_get'] = $_GET;
        $other['received_post'] = $_POST;
    } else if ($otherData != null) {
        $other['other'] = $otherData;
        $other['received_get'] = $_GET;
        $other['received_post'] = $_POST;
    } else {
        $other = array(
            'received_get' => $_GET,
            'received_post' => $_POST
        );
    }
    return $other;
}

function getForain($entity1, $foranea, $entity2, $id) {
    $util = new Util();
    $response = $util->getForain($entity1, $foranea, $entity2, $id);
    responseValidator($response);
    return $response;
}

function addMultiple($table, $param1, $data, $key, $value, $delete = false) {
    if ($delete) {
        $util = new Util();
        $response = $util->eliminarMultiple($table, $key, $value);
        responseValidator($response);
    }
    if ($data != "") {
        $myArray = explode(',', $data);
        foreach ($myArray as $row) {
            $row = ltrim($row);
            $row = rtrim($row);
            $request = [
                $param1 => $row,
                $key => $value
            ];
            $response = crear($request, $table);
        }
    }
}

function entity($table, $id) {
    $util = new Util();
    $response = $util->entity($table, $id);
    responseValidator($response);
    if ($table == 'usuarios') {
        $response->set('clave', 'xxxx');
    }
    return $response;
}

function content($table, $key, $value) {
    $util = new Util();
    $response = $util->content($table, $key, $value);
    responseValidator($response);
    return $response;
}

function select($request, $table, $limit = 0) {
    $newRequest = $request;
    $util = new Util();
    //parametros ignorados
    unset($newRequest['metodo']);
    unset($newRequest['mtrx']);
    unset($newRequest['function']);

    //validacion de parametros
    foreach ($request as $key => $value) {
        if (is_array($value)) {
            if (count($value) != 2) {
                throwError("Select condicional,parametros no validos");
            }
            $condicion = $value[0];
            if (!($condicion == '=' || $condicion == '>' || $condicion == '<' ||
                    $condicion == '>=' || $condicion == '<=' || $condicion == '!=' ||
                    $condicion == 'is not' || $condicion == 'is' ||
                    $condicion == 'in' || $condicion == 'like')) {
                throwError("Select condicional,condicional: " . $condicion . " no válido");
            }
        }
    }
    $response = $util->select($request, $table, $limit);
    responseValidator($response);
    return $response;
}

function updateWhere($request, $table) {
    $newRequest = $request;
    $util = new Util();
    //parametros ignorados
    unset($newRequest['metodo']);
    unset($newRequest['mtrx']);
    unset($newRequest['function']);

    $checkData = false;
    $checkWhere = false;

    //validacion de parametros
    foreach ($request as $key => $value) {
        if (is_array($value)) {
            if (count($value) != 1 && count($value) != 2) {
                throwError("Update condicional,parametros no validos");
            }
            if (count($value) == 2) {
                $condicion = $value[0];
                if (!($condicion == '=' || $condicion == '>' || $condicion == '<' ||
                        $condicion == '>=' || $condicion == '<=' || $condicion == '!=' ||
                        $condicion == 'is not' || $condicion == 'is' ||
                        $condicion == 'in' || $condicion == 'like')) {
                    throwError("Update condicional,condicional: " . $condicion . " no válido");
                }
            }
            $checkWhere = true;
        } else {
            $checkData = true;
        }
    }

    if (!$checkData) {
        throwError("No se encontró datos para actualizar");
    }
    if (!$checkWhere) {
        throwError("No se encontró condiciones para actualizar");
    }
    $response = $util->updateWhere($request, $table);
    responseValidator($response);
    return $response;
}

function table($table) {
    $util = new Util();
    $response = $util->table($table);
    responseValidator($response);
    return $response;
}

function crear($request, $table, $paramsIgnore = null) {
    $newRequest = $request;
    $util = new Util();
    if ($paramsIgnore == null) {
        $paramsIgnore = [];
    }
    //parametros ignorados
    unset($newRequest['metodo']);
    unset($newRequest['mtrx']);
    unset($newRequest['function']);
    unset($newRequest['id']);
    foreach ($paramsIgnore as $value) {
        unset($newRequest[$value]);
    }
    $response = $util->crear($newRequest, $table);
    responseValidator($response);
    return $response;
}

function actualizar($request, $table, $paramsIgnore = null) {
    $util = new Util();
    if ($paramsIgnore == null) {
        $paramsIgnore = [];
    }
    array_push($paramsIgnore, 'id');
    array_push($paramsIgnore, 'metodo');
    array_push($paramsIgnore, 'mtrx');
    array_push($paramsIgnore, 'function');
    array_push($paramsIgnore, 'usuario');
    $id = $request['id'];
    foreach ($request as $key => $value) {
        if (key_exists($key, $request) && !in_array($key, $paramsIgnore)) {
            $req = [
                'key' => $key,
                'value' => $value,
                'id' => $id
            ];
            $response = $util->actualizar($req, $table);
            responseValidator($response);
        }
    }
}

function eliminar($id, $table) {
    $util = new Util();
    $entity = entity($table, $id);
    $response = $util->eliminar($id, $table);
    responseValidator($response);
    return $entity;
}

function eliminarLogico($id, $table) {
    $entity = entity($table, $id);
    $request = [
        'id' => $id,
        'id_estado' => 4
    ];
    actualizar($request, $table);
    return $entity;
}

function eliminarKey($table, $key, $value) {
    $util = new Util();
    $response = $util->eliminarKey($table, $key, $value);
    responseValidator($response);
}

function getMeses() {
    return array("Enero", "Feb", "Marzo", "Abril", "Mayo", "Jun", "Jul", "Ago", "Sept", "Oct", "Nov", "Dic");
}

//Valida los parametros recibidos de acurdo a las reglas
function validate($request, $rules) {

    foreach ($rules as $key => $value) {
        if (!isset($request[$key])) {
            throwError('El campo ' . $value[0] . ' es obligatorio.');
        }
        $restricciones = explode('|', $value[1]);
        foreach ($restricciones as $restriccion) {
            $duos = explode(':', $restriccion);
            if ($restriccion == 'required') {
                if (trim($request[$key]) == '' || $request[$key] == null) {
                    throwError('El campo ' . $value[0] . ' es obligatorio.');
                }
            } else if ($restriccion == 'email') {
                if (!filter_var($request[$key], FILTER_VALIDATE_EMAIL)) {
                    throwError('El campo ' . $value[0] . ' no es una dirección email valida.');
                }
            } else if ($restriccion == 'numeric') {
                if (!is_numeric($request[$key])) {
                    throwError('El campo ' . $value[0] . ' debe ser numerico.');
                }
            } else if ($restriccion == 'username') {
                if (!(preg_match('/^\w{5,}$/', $request[$key]))) {
                    throwError('El campo ' . $value[0] . ' no es valido.');
                }
            } else if ($restriccion == 'integer') {
                if (!(is_int($request[$key]) || ctype_digit($request[$key]))) {
                    throwError('El campo ' . $value[0] . ' debe ser un numero entero.');
                }
            }else if ($restriccion == 'duration') {
                if (strlen($request[$key])!=8 || count(explode(':',$request[$key]))!=3) {
                    throwError('El campo ' . $value[0] . ' debe ser tipo hh:mm:ss.');
                }
            } else if ($duos[0] == 'min') {
                $min = $duos[1];
                if (strlen($request[$key]) < $min) {
                    throwError('El campo ' . $value[0] . ' debe tener minimo ' . $min . ' caracteres.');
                }
            } else if ($duos[0] == 'max') {
                $max = $duos[1];
                if (strlen($request[$key]) > $max) {
                    throwError('El campo ' . $value[0] . ' debe tener maximo ' . $max . ' caracteres.');
                }
            } else {
                throwError('No se pudo evaluar la restricción.', 'restricción: ' . $restriccion);
            }
        }
    }
}

function abrirOptionSelect() {
    return '[';
}

function cerrarOptionSelect() {
    return ']';
}

function generarOptionSelect($request) {
    $id = $request['id'];
    $nombre = $request['nombre'];
    $keyWords = '';
    $html = '<div><b>' . $nombre . '</b></div>';
    $espacio = '&nbsp&nbsp&nbsp&nbsp';
    $contador = 0;
    foreach ($request as $key => $value) {
        if ($contador == 2) {
            $html .='<br>';
            $contador = 0;
        }
        $contador++;
        $keyWords .=$value . ' ';
        $html .= '<i class=\"fa fa-caret-right\"></i><b>' . $key . ' :</b> <i>' . $value . $espacio . '</i>';
    }
    $text = '<div style=\"display: inline\">' .
            '<span style=\"display:none\">' .
            $keyWords . '</span>' . $nombre . '</div>';
    $title = $nombre;
    //Se arma el JSON
    $data = '{
            "id":  "' . $id . '",
            "text": "' . $text . '",
            "html": "' . $html . '",
            "title": "' . $title . '"
          }';
    return $data;
}

function toBoolean($var) {
    if (!is_string($var))
        return (bool) $var;
    switch (strtolower($var)) {
        case '1':
        case 'true':
        case 'on':
        case 'yes':
        case 'y':
            return true;
        default:
            return false;
    }
}

function toNumber($var) {
    return number_format($var, 1, '.', '');
}

function ncf($nStr) {
    return number_format($nStr, 0, '.', '.');
}

function limpiarString($texto) {
    $textoLimpio = preg_replace('/[^a-zA-Z0-9\s]+/u', '', $texto);
    return $textoLimpio;
}

function limpiarStringEspanol($texto) {
    $textoLimpio = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+/u', '', $texto);
    return $textoLimpio;
}

function limpiarStringEspanolNumeros($texto) {
    $textoLimpio = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+/u', '', $texto);
    return $textoLimpio;
}

function limpiarStringEspacios($texto) {
    $textoLimpio = preg_replace('([^A-Za-z0-9])', '', $texto);
    return $textoLimpio;
}

function stringUsername($texto) {
    $textoLimpio = preg_replace('/[^a-zA-Z0-9_.]+/u', '', $texto);
    return $textoLimpio;
}

function stringUrl($texto) {
    //permite guion medio
    $texto = str_replace('-', ' ', $texto);
    $texto = str_replace('    ', ' ', $texto);
    $texto = str_replace('   ', ' ', $texto);
    $texto = str_replace('  ', ' ', $texto);
    $texto = str_replace(' ', '-', $texto);
    $textoLimpio = preg_replace('/[^a-zA-Z0-9-.]+/u', '', $texto);
    $textoLimpio = strtolower($textoLimpio);
    return $textoLimpio;
}

function maxString($texto, $cantidad) {
    if ($texto != null && $texto != '') {
        if (strlen($texto) > $cantidad) {
            $texto = my_mb_substr($texto, 0, $cantidad);
            $texto = $texto . '...';
            $texto = str_replace(' ...', '...', $texto);
        }
    }
    return $texto;
}

function maxWord($texto, $cantidad, $maxString = 0) {

    $textoConvert = '';
    $textoArray = explode(" ", $texto);
    $count = 1;

    foreach ($textoArray as $t) {
        if ($count <= $cantidad) {
            if ($count != 1) {
                $textoConvert.=' ';
            }
            $textoConvert.=$t;
            $count++;
        }
    }

    if ($maxString != 0) {
        $textoConvert = maxString($textoConvert, $maxString);
    }
    return $textoConvert;
}

function ucwordsUtf8($text) {
    return mb_convert_case(mb_strtolower($text, "UTF-8"), MB_CASE_TITLE, "UTF-8");
}

function my_mb_substr($string, $offset, $length) {
    $arr = preg_split("//u", $string);
    $slice = array_slice($arr, $offset + 1, $length);
    return implode("", $slice);
}
