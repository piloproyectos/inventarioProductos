<?php

require_once "database.php";

class Util {

    private $status;
    private $type;
    private $entity;
    private $content;
    private $count;
    private $error;
    private $other;

    const TYPE_OBJECT = 'OBJECT';
    const TYPE_BOOLEAN = 'BOOLEAN';
    const TYPE_ARRAY = 'ARRAY';
    const TYPE_STRING = 'STRING';
    const TYPE_JSON = 'JSON';
    const TYPE_ERROR = 'ERROR';
    const TYPE_LOG = 'LOG';
    const TYPE_HTML = 'HTML';
    const IS_ADMIN = '1';
    const IS_NOT_ADMIN = '0';
    const STATUS_ACTIVE = '1';
    const STATUS_INACTIVE = '0';
    const IS_ENTITY = '1';
    const IS_NOT_ENTITY = '0';
    const STATUS_REQUIRED = '1';
    const STATUS_NOTREQUIRED = '0';
    const MSJ_ERROR_SELECT = 'Error, no se pudo obtener registro.';
    const MSJ_ERROR_SELECTS = 'Error, no se pudo obtener registros.';
    const MSJ_ERROR_INSERT = 'Error, no se pudo insertar registro.';
    const MSJ_ERROR_UPDATE = 'Error, no se pudo actualizar registro.';
    const MSJ_ERROR_SESION = 'Error, ocurrio un error procesando sesión.';
    const MSJ_ERROR_DUPLICATE = 'Error, no se puede ingresar registro duplicado';

    public function connect() {
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAME'utf8'");
        $pdo->exec('SET CHARACTER SET utf8');
        return $pdo;
    }

    public function execute($stmt, $param = null) {
        if ($param != null) {
            for ($i = 1; $i <= count($param); $i++) {
                $stmt->bindParam($i, $param[($i - 1)]);
            }
        }
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Database::disconnect();
        return $result;
    }

    public function coalesce($data, $option) {
        if ($data == null) {
            return $option;
        }
        return $data;
    }

    public function interpolateQuery($query, $params = null) {
        $keys = array();
        $values = $params;
        # build a regular expression for each parameter
        if ($params != null) {
            foreach ($params as $key => $value) {
                if (is_string($key)) {
                    $keys[] = '/:' . $key . '/';
                } else {
                    $keys[] = '/[?]/';
                }
                if (is_array($value))
                    $values[$key] = implode(',', $value);

                if (is_null($value))
                    $values[$key] = 'NULL';
            }
            // Walk the array to see if we can add single-quotes to strings
            array_walk($values, function(&$v, $k) {
                if (!is_numeric($v) && $v != "NULL")
                    $v = "'" . $v . "'";
            });
            $query = preg_replace($keys, $values, $query, 1, $count);
        }
        $query = trim(preg_replace('/\s\s+/', ' ', $query));
        return $query;
    }

    /* =================================
     * ==================================
     * Funciones Controladoras de clase
     * ==================================
     * ==================================
     */

    /*
     * Get para la variables de Clase
     * Obtiene valores de $content
     */

    function get($value) {
        if (is_array($this->entity) && array_key_exists($value, $this->entity)) {
            return $this->entity[$value];
        } else {
            return $value . ' -> No es un argumento valido';
        }
    }

    /*
     * Set para las variables de Clase
     * Se guardan en $content
     */

    function set($parameter, $value) {
        if (is_array($this->entity)) {
            $this->entity = array_merge($this->entity, [$parameter => $value]);
        } else {
            $this->init();
            $this->entity = array(
                $parameter => $value
            );
        }
    }

    function init() {
        $this->status = 1;
        $this->type = self::TYPE_OBJECT;
        $this->count = 0;
        $this->error = 0;
        $this->other = null;
        $this->content = null;
        $this->entity = null;
    }

    function isEntity() {
        if (is_array($this->other) && array_key_exists('entity', $this->other)) {
            if ($this->other["entity"] == self::IS_ENTITY) {
                if (is_array($this->entity) && sizeof($this->entity)) {
                    return true;
                }
            }
        }
        return false;
    }

    function isContent() {
        if (is_array($this->content) && sizeof($this->content) > 0) {
            return true;
        }
        return false;
    }

    function response($result, $count, $other = null) {

        $status = 1;
        $type = self::TYPE_OBJECT;
        $entity = null;
        $content = $result;
        $error = null;
        if (is_array($other) && array_key_exists('entity', $other)) {
            if ($other["entity"] == self::IS_ENTITY && $count > 0) {
                $entity = $result[0];
                $content = null;
            }
        }

        $result = new Util();
        $result->setStatus($status);
        $result->setType($type);
        $result->setContent($content);
        $result->setEntity($entity);
        $result->setCount($count);
        $result->setError($error);
        $result->setOther($other);
        return $result;
    }

    function throwException($e, $message, $function, $other = null, $type_error = 100) {
        $status = 0;
        $type = self::TYPE_ERROR;
        $content = null;
        $entity = null;
        $count = 0;
        $message_errror = $e->getMessage();

        if (!App::getParam('debug_mode')) {
            $message_errror = 'NDM';
        }

        $duplicate1 = strpos($message_errror, '1061');
        $duplicate2 = strpos($message_errror, '1062');
        if ($duplicate1 || $duplicate2) {
            $type_error = 101;
            $message = self::MSJ_ERROR_DUPLICATE;
        }

        $error = array("message" => $message,
            "exception" => $message_errror,
            "type" => $type_error,
            "origen" => 300,
            "method" => $function,
            "line" => $e->getLine(),
            "code" => $e->getCode()
        );

        $result = new Util();
        $result->setStatus($status);
        $result->setType($type);
        $result->setContent($content);
        $result->setEntity($entity);
        $result->setCount($count);
        $result->setError($error);
        $result->setOther($other);
        return $result;
    }

    function throwError($message, $otherParam = null, $message_exception = null) {
        $status = 0;
        $type = self::TYPE_ERROR;
        $content = null;
        $entity = null;
        $count = 0;
        $message_errror = $message_exception;
        $type_error = 106;

        if (!App::getParam('debug_mode')) {
            $message_errror = 'NDM';
        }

        $error = array("message" => $message,
            "exception" => $message_errror,
            "type" => $type_error,
            "origen" => 300,
            "method" => null,
            "line" => null,
            "code" => null
        );

        $result = new Util();
        $result->setStatus($status);
        $result->setType($type);
        $result->setContent($content);
        $result->setEntity($entity);
        $result->setCount($count);
        $result->setError($error);
        $result->setOther($otherParam);
        return $result;
    }

    function sendQuery($request) {
        $query = $request['query'];
        $param = null;
        if (isset($request['parameters'])) {
            $param = $request['parameters'];
        }
        $entity = false;
        if (isset($request['entity'])) {
            $entity = $request['entity'];
        }
        $function = $request['function'];
        $args = $request['args'];
        $messageError = $request['messageError'];

        $iQuery = $this->interpolateQuery($query, $param);
        try {

            $pdo = $this->connect();
            $stmt = $pdo->prepare($query);
            $result = $this->execute($stmt, $param);
            $count = $stmt->rowCount();
            $lastInsertId = $pdo->lastInsertId();
            $other = array("params" => func_get_args(),
                "lastInsertId" => $lastInsertId,
                "query" => $iQuery,
                "entity" => $entity
            );
            $debugMode = App::getParam('debug_mode');
            if (!$debugMode) {
                $other = array("params" => '--',
                    "lastInsertId" => $lastInsertId,
                    "query" => '--',
                    "entity" => $entity
                );
            }
            return $this->response($result, $count, $other);
        } catch (Exception $e) {
            $message = $messageError;
            $other = array("params" => $args,
                "query" => $iQuery);
            $debugMode = App::getParam('debug_mode');
            if (!$debugMode) {
                $other = array("params" => '--',
                    "query" => '--'
                );
            }
            $function = $function;
            return $this->throwException($e, $message, $function, $other);
        }
    }

    /*
     * Example Call to log:
     * Return $this->toDie($query);
     * 
      $querySt=$this->interpolateQuery($query,$param);
      Return $this->toDie($querySt);
     */

    function toDie($content, $other = null) {
        $status = 0;
        $type = self::TYPE_LOG;
        $entity = null;
        $count = 0;
        $type_error = 102;

        $error = array("message" => $content,
            "exception" => null,
            "type" => $type_error,
            "origen" => 300,
            "method" => null,
            "line" => null,
            "code" => null
        );

        $result = new Util();
        $result->setStatus($status);
        $result->setType($type);
        $result->setContent($content);
        $result->setEntity($entity);
        $result->setCount($count);
        $result->setError($error);
        $result->setOther($other);
        return $result;
    }

    function getForain($entity1, $foranea, $entity2, $id) {
        $query = 'select *
                 from ' . $entity1 . ' a inner join ' . $entity2 . ' b on
                 a.' . $foranea . '=b.id where a.id=' . $id;
        $data = [
            'query' => $query,
            'function' => __FUNCTION__,
            'args' => func_get_args(),
            'entity' => true,
            'messageError' => self::MSJ_ERROR_SELECT,
        ];
        return $this->sendQuery($data);
    }

    function entity($table, $id) {
        $query = 'select *
                  from ' . $table . ' where id=' . $id;
        $data = [
            'query' => $query,
            'function' => __FUNCTION__,
            'args' => func_get_args(),
            'entity' => true,
            'messageError' => self::MSJ_ERROR_SELECT,
        ];
        return $this->sendQuery($data);
    }

    function content($table, $key, $value) {
        $query = 'select *
                  from ' . $table . ' where ' . $key . '="' . $value . '"';
        $data = [
            'query' => $query,
            'function' => __FUNCTION__,
            'args' => func_get_args(),
            'messageError' => self::MSJ_ERROR_SELECT,
        ];
        return $this->sendQuery($data);
    }

    function select($request, $table, $limit = 0) {

        $param = [];
        $where = '';
        $limitText = '';
        foreach ($request as $key => $value) {
            $condicion = '=';
            if (is_array($value)) {
                $condicion = $value[0];
                $value = $value[1];
            }
            if ($where != '') {
                $where.=' and ';
            }

            if ($value == null || $value == 'null') {
                $where .= $key . ' ' . $condicion . ' null ';
            } else if ($condicion == 'in') {
                $where .= $key . ' ' . $condicion . ' ' . $value;
            } else if ($condicion == 'like') {
                $where .= $key . ' ' . $condicion . ' "' . $value . '"';
            } else {
                $where .= $key . ' ' . $condicion . ' ?';
                array_push($param, $value);
            }
        }

        if ($limit != 0 && is_numeric($limit)) {
            $limitText = 'limit ' . $limit;
        }

        $query = 'select *
                  from ' . $table . '
                  where ' . $where . ' ' . $limitText . ';';

        $data = [
            'query' => $query,
            'parameters' => $param,
            'function' => __FUNCTION__,
            'args' => func_get_args(),
            'messageError' => self::MSJ_ERROR_SELECTS,
        ];
        return $this->sendQuery($data);
    }

    function updateWhere($request, $table) {

        $param = [];
        $where = '';
        $limitText = '';
        $set = '';

        foreach ($request as $key => $value) {
            $key = str_replace('@', '', $key);
            if (!is_array($value)) {
                if ($set != '') {
                    $set.=' , ';
                }
                if ($value == null || $value == 'null') {
                    $set .= $key . ' = null';
                } else {
                    $set .= $key . ' =  ?';
                    array_push($param, $value);
                }
            }
        }

        foreach ($request as $key => $value) {
            $key = str_replace('@', '', $key);
            $condicion = '=';
            if (is_array($value)) {
                if (count($value) == 2) {
                    $condicion = $value[0];
                    $value = $value[1];
                } else {
                    $value = $value[0];
                }
                if ($where != '') {
                    $where.=' and ';
                }

                if ($value == null || $value == 'null' || $condicion == 'in') {
                    $where .= $key . ' ' . $condicion . ' ' . $value;
                } else if ($condicion == 'like') {
                    $where .= $key . ' ' . $condicion . ' "' . $value . '"';
                } else {
                    $where .= $key . ' ' . $condicion . ' ?';
                    array_push($param, $value);
                }
            }
        }

        $query = 'update  ' . $table . '
                  set ' . $set . ' 
                  where ' . $where . ';';

        $data = [
            'query' => $query,
            'parameters' => $param,
            'function' => __FUNCTION__,
            'args' => func_get_args(),
            'messageError' => self::MSJ_ERROR_UPDATE,
        ];
        return $this->sendQuery($data);
    }

    function table($table) {
        $query = 'select *
                  from ' . $table;
        $data = [
            'query' => $query,
            'function' => __FUNCTION__,
            'args' => func_get_args(),
            'messageError' => self::MSJ_ERROR_SELECT,
        ];
        return $this->sendQuery($data);
    }

    function encerrarCommillas($valor) {
        return '"' . $valor . '"';
    }

    /**
     * Metodo que almacena en la base de datos
     */
    function crear($request, $table) {
        $key = implode(', ', array_keys($request));
        $param = [];
        $lisValues = '';
        foreach ($request as $value) {
            if ($lisValues != '') {
                $lisValues.=',';
            }
            if ($value == '' || $value == 'NULL' || $value == 'null') {
                $value = null;
            }
            $lisValues.='?';
            $param[] = $value;
        }
        $query = 'INSERT INTO ' . $table . ' (' . $key . ') VALUES (' . $lisValues . ')';
        $data = [
            'query' => $query,
            'parameters' => $param,
            'function' => __FUNCTION__,
            'args' => func_get_args(),
            'messageError' => self::MSJ_ERROR_INSERT,
        ];
        return $this->sendQuery($data);
    }

    /**
     * Metodo que actualiza un campo de la 
     * tabla en la base de datos
     */
    function actualizar($request, $table) {
        $value = $request['value'];
        if ($value == '' || $value == 'NULL' || $value == 'null') {
            $value = null;
        }

        $key = $request['key'];
        $query = 'update ' . $table . '  
                    set ' . $key . ' = ?
                    where id =?';
        $param = [
            $value,
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

    /**
     * Metodo que Elimina en la base de datos
     */
    function eliminar($id, $table) {
        $query = 'delete from ' . $table . ' where id =?';
        $param = [
            $id,
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

    /**
     * Metodo que Elimina en la base de datos
     */
    function eliminarKey($table, $key, $value) {
        $query = 'delete from ' . $table . ' where ' . $key . ' =?';
        $param = [
            $value,
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

    /*
     * Metodo que elimina con una condicion particular
     */

    function eliminarMultiple($table, $key, $value) {
        $query = 'delete from ' . $table . ' where ' . $key . ' =?';
        $param = [
            $value,
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

    function sendRequestApiServer($query, $param) {

        $url = getParamSesion('url_server');
        if ($url == '0') {
            throwError('No se encontró URL');
        }
        $url = $url . '/controller/Cocina.php';
        if ($param != null) {
            $param = json_encode($param);
        }

        $post = [
            'metodo' => 'requestPrintServer',
            'query' => $query,
            'param' => $param,
        ];
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return $this->throwException("Error", "CURL Error #:" . $err, 'No Aplica');
        } else {
            $responseText = stripslashes($response);
            $responseText = $this->textoSinEspeciales($responseText);
            $response = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $responseText), true);
            if (json_last_error() !== 0) {
                throwError('Error en la conexión con servidor', null, $responseText);
            }
            return $response;
        }
    }

    function textoSinEspeciales($responseText) {
        $responseText = str_replace('á', 'a', $responseText);
        $responseText = str_replace('é', 'e', $responseText);
        $responseText = str_replace('í', 'i', $responseText);
        $responseText = str_replace('ó', 'o', $responseText);
        $responseText = str_replace('ú', 'u', $responseText);
        $responseText = str_replace('ñ', 'n', $responseText);

        $responseText = str_replace('Á', 'A', $responseText);
        $responseText = str_replace('É', 'E', $responseText);
        $responseText = str_replace('Í', 'I', $responseText);
        $responseText = str_replace('Ó', 'O', $responseText);
        $responseText = str_replace('Ú', 'U', $responseText);
        $responseText = str_replace('Ñ', 'N', $responseText);

        return $responseText;
    }

    /*
     * ====================================
     * ====================================
     * Metodos Get and Set de la clase
     * ====================================
     * ====================================
     */

    function getStatus() {
        return $this->status;
    }

    function getType() {
        return $this->type;
    }

    function getContent() {
        return $this->content;
    }

    function getCount() {
        return $this->count;
    }

    function getError() {
        return $this->error;
    }

    function getOther() {
        return $this->other;
    }

    function setStatus($status) {
        $this->status = $status;
    }

    function setType($type) {
        $this->type = $type;
    }

    function setContent($content) {
        $this->content = $content;
    }

    function setCount($count) {
        $this->count = $count;
    }

    function setError($error) {
        $this->error = $error;
    }

    function setOther($other) {
        $this->other = $other;
    }

    function getEntity() {
        return $this->entity;
    }

    function setEntity($entity) {
        $this->entity = $entity;
    }

}

?>
