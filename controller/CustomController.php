<?php

/*
 * Se incluye Sontroller
 */
require_once __DIR__ . "/Controller.php";

function registerLog($accion, $description, $estado = LOG_SUCCESS, $privado = '') {
    $ip = getIp();
    $url = null;
    $navegador = getNavegador();

    registroLogCompleto($accion, $description, $estado, $privado, $ip, $url, $navegador);
}

function registroLogCompleto($accion, $description, $estado, $privado, $ip, $url, $navegador) {
    $fecha = App::fecha('Y-m-d H:i:s');

    //Si no envian el id Usuario, en caso de acciones.                        
    $usuario = new User();
    $response = $usuario->getSesion();
    responseValidator($response);
    $user = $response;
    if ($user->isEntity()) {
        try {
            $idUsuario = $user->get('id');
            $usuario = $user->get('username');
        } catch (Exception $e) {
            $usuario = "No Login";
            $idUsuario = "-1";
        }
    } else {
        $usuario = "No Login";
        $idUsuario = "-1";
    }

    if ($estado == LOG_SUCCESS) {
        $estado = "Correcto";
    } else {
        $estado = "Error";
    }

    if (!strpos(strtoupper($description), "SOPORTE")) {
        $request = [
            'idUsuario' => $idUsuario,
            'usuario' => $usuario,
            'fecha' => $fecha,
            'ip' => $ip,
            'url' => $url,
            'navegador' => $navegador,
            'estado' => $estado,
            'accion' => $accion,
            'descripcion' => $description,
            'privado' => $privado,
        ];
        $usuario = new User();
        $response = $usuario->guardarRegistroLog($request);
        responseValidator($response);
    }
}

function getIp() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    if ($ip == '::1') {
        $ip = 'Local';
    }

    return $ip;
}

function getNavegador() {
    $platform = '';
    $bname = '';
    $u_agent = $_SERVER['HTTP_USER_AGENT'];

    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    } elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }
    if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
        $bname = 'Internet Explorer';
    } elseif (preg_match('/Firefox/i', $u_agent)) {
        $bname = 'Mozilla Firefox';
    } elseif (preg_match('/Chrome/i', $u_agent)) {
        $bname = 'Google Chrome';
    } elseif (preg_match('/Safari/i', $u_agent)) {
        $bname = 'Apple Safari';
    } elseif (preg_match('/Opera/i', $u_agent)) {
        $bname = 'Opera';
    } elseif (preg_match('/Netscape/i', $u_agent)) {
        $bname = 'Netscape';
    }

    $navegador = $platform . ' ' . $bname;
    return $navegador;
}

function orderType($idOrder) {
    if (strtoupper(substr($idOrder, 0, 1)) == 'C' || strtoupper(substr($idOrder, 0, 1)) == 'P') {
        return 1;
    } else {
        return 2;
    }
}

function google_money_convert($from, $to, $amount) {
    // Fetching JSON
    $req_url = 'https://prime.exchangerate-api.com/v5/4093f94e5b3d18891e9e20ba/latest/' . $from;
    $response_json = file_get_contents($req_url);
    // Continuing if we got a result
    if (false !== $response_json) {
        // Try/catch for json_decode operation
        try {
            // Decoding
            $response = json_decode($response_json);
            // Check for success
            if ('success' === $response->result) {
                // YOUR APPLICATION CODE HERE, e.g.
                $base_price = $amount; // Your price in USD
                $EUR_price = round(($base_price * $response->conversion_rates->$to), 2);
            }
            return $EUR_price;
        } catch (Exception $e) {
            throwError("Error convirtiendo divisa: " . $e->getMessage());
        }
    }
}

function autorize($request) {
    $usuario = new User();
    $response = $usuario->getSesion();
    responseValidator($response);
    //si no hay sesion abierta (getCount()==0) se inicializa Usuario para que 
    //pueda utilizar los metodos.
    if ($response->getCount() == 0) {
        $response = new User();
        $idUsuario = null;
    } else {
        $idUsuario = $response->get('id');
    }

    $responseValid = true;
    //Para cualquier otro rol diferente a PUBLIC
    //tiene encuenta la variabla $responseValid.
    if ($response->getCount() == 0) {
        $responseValid = false;
    }
    if (!is_array($response->getEntity())) {
        $responseValid = false;
    }
    if ($response->get('id') == null) {
        $responseValid = false;
    }

    foreach ($request as $rol) {
        //Si rol es publico continua con usuario
        if ($rol == ROL_PUBLIC) {
            return $response;
        }

        //Realiza validaciones que se guardaron en $responseValid
        if (!$responseValid) {
            break;
        }

        //Recorre cada rol para verificar si el usuario
        //lo tiene Asignado.
        switch ($rol) {
            case ROL_USUARIO:
                if ($response->getCount() >= 1) {
                    return $response;
                }
                break;
            case ROL_ADMIN:
                if ($response->get('is_admin')) {
                    return $response;
                }
                break;
            default:
                $message = MSJ_ERROR_PROCESS;
                throwError($message);
        }
    }
    $message = "No está autorizado para realizar esta acción";
    throwError($message);
}

function getEmailTemplate() {
    $template = file_get_contents(__DIR__ . "/../email/email_template.html");
    return $template;
}

function userAuth() {
    $usuario = new User();
    $response = $usuario->getSesion();
    responseValidator($response);
    $user = $response;
    if ($user->isEntity()) {
        try {
            return $user;
        } catch (Exception $e) {
            return 'No sesion';
        }
    } else {
        return 'No sesion';
    }
}

function refreshtUserSesion() {
    $user = userAuth();
    if ($user != 'No sesion') {
        $email = $user->get('email');
        $request = [
            'email' => $email
        ];
        $user = new User();
        $response = $user->validarUserEmail($request);
        responseValidator($response);
        if ($response->getCount() < 1) {
            return false;
        }

        $idUsuario = $response->get('id');
        $nombre = $response->get('name');
        $userName = $response->get('username');
        $isActive = $response->get('is_active');

        $date = App::fecha('Y-m-d H:i:s');

        //se guardan en sesion los privilegios del usuario
        $permisosUsuario = [
            'email' => $email,
            'fecha' => $date
        ];
        $privilegios = $user->permisosUsuario($permisosUsuario);
        responseValidator($privilegios);
        $userContent = $privilegios->getContent();
        $response->set('user_content', $userContent);

        $firstName = explode(" ", $nombre);
        $firstName = $firstName[0];
        $firstName = maxString($firstName, 15);
        $response->set('first_name', $firstName);
        //Se actualiza la sesion con el usuario indicado
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['usuario'] = $response;
        return true;
    } else {
        return false;
    }
}

function contentUser($idContenido) {
    $user = userAuth();
    if ($user != 'No sesion') {
        if ($user->get('is_admin') == '1') {
            //return true;
        }
        //El query que retorna esta en Model-User-permisosUsuario
        $privilegios = $user->get('user_content');
        foreach ($privilegios as $data) {
            if ($data['content_id'] == $idContenido) {
                return true;
            }
        }
        return false;
    } else {
        return false;
    }
}

function contentUserEmail($idContenido, $email) {
    $user = new User();
    $date = App::fecha('Y-m-d H:i:s');

    //se guardan en sesion los privilegios del usuario
    $permisosUsuario = [
        'email' => $email,
        'fecha' => $date
    ];
    $response = $user->permisosUsuario($permisosUsuario);
    responseValidator($response);
    $privilegios = $response->getContent();
    foreach ($privilegios as $data) {
        if ($data['content_id'] == $idContenido) {
            return true;
        }
    }
    return false;
}

function adminSidebar($option, $paramUrl) {
    $optionActive = [
        'class-active' => 'active',
        'class-show' => 'show'
    ];
    $optionInactive = [
        'class-active' => '',
        'class-show' => ''
    ];

    if ($paramUrl == $option) {
        return $optionActive;
    }

    $options = [
        'signals' => ['collapse01'],
        'payment-search' => ['collapse02'],
        'links' => ['collapse02'],
        'coupons' => ['collapse03'],
        'coupon-category' => ['collapse03'],
        'products' => ['collapse04'],
        'product-report' => ['collapse04'],
        'content' => ['collapse05'],
        'content-report' => ['collapse05'],
        'courses' => ['collapse06'],
        'dashboard-progress' => ['collapse06'],
    ];

    foreach ($options as $key => $value) {
        if ($paramUrl == $key) {
            foreach ($value as $opt) {
                if ($option == $opt) {
                    return $optionActive;
                }
            }
        }
    }
    return $optionInactive;
}

function statusSidebar($option, $class, $paramUrl) {
    echo adminSidebar($option, $paramUrl, $paramUrl)[$class];
}

function getUrlImageProfile($code) {
    $urlImageProfile = 'default.png';
    if (is_file(__DIR__ . '/../content/user/img/200x200/' . $code . '.jpg')) {
        $urlImageProfile = $code . '.jpg?v=' . rand(100, 900);
    }
    return $urlImageProfile;
}

function getUrlImageProduct($code, $min = false) {
    $urlImageProfile = 'default.jpg';
    if ($min) {
        if (is_file(__DIR__ . '/../content/product/img/800x600/' . $code . '.jpg')) {
            $urlImageProfile = $code . '.jpg';
        }
    } else {
        if (is_file(__DIR__ . '/../content/product/img/1600x1200/' . $code . '.jpg')) {
            $urlImageProfile = $code . '.jpg';
        }
    }

    return $urlImageProfile;
}

function dateSqlToHtml($date) {
    if ($date == '') {
        return '';
    }
    return date("Y-m-d\TH:i", strtotime($date));
}

function dateFormatBeautiful($date, $mesCorto = false, $semana = false, $seconds = true) {
    $dias = array("Domingo", "Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sábado");
    $meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
    $mesesCorto = array("Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic");
    if ($date == '') {
        return '';
    }
    $fecha = '';
    if ($semana) {
        $fecha.= $dias[date('w', strtotime($date))] . ', ';
    }

    $fecha.= date('d', strtotime($date));
    if ($mesCorto) {
        $fecha.= ' ' . $mesesCorto[date('n', strtotime($date)) - 1];
    } else {
        $fecha.= ' de ' . $meses[date('n', strtotime($date)) - 1];
    }
    if ($seconds) {
        $fecha.= ' ' . date('Y, h:i:s A', strtotime($date));
    } else {
        $fecha.= ' ' . date('Y, h:i A', strtotime($date));
    }

    return $fecha;
}

function dateOnlyFormatBeautiful($date, $mesCorto = false, $semana = false) {
    $dias = array("Domingo", "Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sábado");
    $meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
    $mesesCorto = array("Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic");
    if ($date == '') {
        return '';
    }
    $fecha = '';
    if ($semana) {
        $fecha.= $dias[date('w', strtotime($date))] . ', ';
    }

    $fecha.= date('d', strtotime($date));
    if ($mesCorto) {
        $fecha.= ' ' . $mesesCorto[date('n', strtotime($date)) - 1];
    } else {
        $fecha.= ' de ' . $meses[date('n', strtotime($date)) - 1];
    }

    $fecha.= ' ' . date('Y', strtotime($date));

    return $fecha;
}

function dateHtmlToSql($date) {
    if ($date == '') {
        return null;
    }
    return date("Y-m-d H:i:s", strtotime($date));
}

function getMsjEmpty() {
    $html = '<div class="text-left container">              
            <img class="img-result-empty" src="assets/img/content/empty-result.png" alt="sin resultados"/>
            <h3 class="my-3">No se encontró ningun resultado.</h3> 
            </div>
          </div>';
    return $html;
}

function getMsjEmptyTable() {
    $html = '<tr role="row">
                    <td>No se encontró ningun resultado</td> 
            </tr>';
    return $html;
}

function compressImage($source, $destination, $quality) {
    // Obtenemos la información de la imagen
    $imgInfo = getimagesize($source);
    $mime = $imgInfo['mime'];

    // Creamos una imagen
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            $image = imagecreatefromjpeg($source);
    }

    // Guardamos la imagen
    imagejpeg($image, $destination, $quality);

    // Devolvemos la imagen comprimida
    return $destination;
}

function enviarEmail($email, $asunto, $mensaje, $name = false) {
    //Se obtiene plantilla de email
    $templateEmail = getEmailTemplate();
    if ($name === false) {
        $templateEmail = str_replace('Hola %%userName%%:', '', $templateEmail);
    } else {
        $nombre = explode(" ", $name);
        $nombre = $nombre[0];
        $templateEmail = str_replace('%%userName%%', $nombre, $templateEmail);
    }
    $templateEmail = str_replace('%%message%%', $mensaje, $templateEmail);

    //Se envia correo      
    include __DIR__ . "/../include/sentEmail.php";
    //Definimos el tema del email
    //Y, ahora sí, definimos el destinatario (dirección y, opcionalmente, nombre)
    if (is_array($email)) {
        foreach ($email as $e) {
            $mail->AddAddress($e);
        }
    } else {
        $mail->AddAddress($email);
    }

    $mail->Subject = $asunto;
    $mail->Body = $templateEmail;
    $mail->IsHTML(true);
    $result = $mail->Send();

    if ($result) {
        return 'Exito';
    } else {
        $errorInfo = $mail->ErrorInfo;
        return 'Error: no se envio Mail: ' . $errorInfo;
    }
}

function enviarEmailParams($request) {

    $email = $request['email'];
    $name = $request['name'];
    $subject = $request['subject'];
    $textoEmail = $request['text_email'];
    $replace = $request['replace'];

    //Se remplaza texto de correo
    if ($replace != '' && $replace != null) {
        foreach ($replace as $key => $value) {
            $textoEmail = str_replace('{{' . $key . '}}', $value, $textoEmail);
        }
    }

    //Se obtiene plantilla de email
    $templateEmail = getEmailTemplate();
    if ($name == '' || $name == null) {
        $templateEmail = str_replace('Hola %%userName%%:', '', $templateEmail);
    } else {
        $nombre = explode(" ", $name);
        $nombre = $nombre[0];
        $templateEmail = str_replace('%%userName%%:', $nombre, $templateEmail);
    }
    $templateEmail = str_replace('%%message%%', $textoEmail, $templateEmail);

    //Se envia correo      
    include __DIR__ . "/../include/sentEmail.php";
    //Definimos el tema del email
    //Y, ahora sí, definimos el destinatario (dirección y, opcionalmente, nombre)
    if (is_array($email)) {
        foreach ($email as $e) {
            $mail->AddAddress($e);
        }
    } else {
        $mail->AddAddress($email);
    }

    $mail->Subject = $subject;
    $mail->Body = $templateEmail;
    $mail->IsHTML(true);
    $result = $mail->Send();

    if ($result) {
        return 'Exito';
    } else {
        $errorInfo = $mail->ErrorInfo;
        return 'Error: no se envio Mail: ' . $errorInfo;
    }
}

function generarExcel($content) {
    require(__DIR__ . '/../include/phpExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();

    $arrCol = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
        'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ',
        'CA', 'CB', 'CC', 'CD', 'CE', 'CF', 'CG', 'CH', 'CI', 'CJ', 'CK', 'CL', 'CM', 'CN', 'CO', 'CP', 'CQ', 'CR', 'CS', 'CT', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ',
        'DA', 'DB', 'DC', 'DD', 'DE', 'DF', 'DG', 'DH', 'DI', 'DJ', 'DK', 'DL', 'DM', 'DN', 'DO', 'DP', 'DQ', 'DR', 'DS', 'DT', 'DU', 'DV', 'DW', 'DX', 'DY', 'DZ'];

    $indexHead = 0;
    if (count($content) < 1) {
        throwError("No hay datos para exportar");
    }

    foreach ($content[0] as $key => $value) {
        if ($indexHead < 100) {
            $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue($arrCol[$indexHead] . '1', $key);
            $indexHead++;
        }
    }


    $objPHPExcel->getActiveSheet()->getStyle('A1:' . $arrCol[$indexHead] . '1')->getFont()->setBold(true);

    $row = 2;
    $col = 0;
    foreach ($content as $arrayFile) {
        foreach ($arrayFile as $value) {
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, (string) $value);
            $col++;
        }
        $col = 0;
        $row++;
    }

    for ($index = 0; $index < $indexHead; $index++) {
        $objPHPExcel->getActiveSheet()->getColumnDimension($arrCol[$index])->setAutoSize(true);
    }

    $objPHPExcel->setActiveSheetIndex(0);
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    /*
      $objWriter->save(__DIR__ . '/../content/report.xlsx');
     */
    ob_start();
    $objWriter->save("php://output");
    $xlsData = ob_get_contents();
    ob_end_clean();
    $response = array(
        'op' => 'ok',
        'file' => "data:application/vnd.ms-excel;base64," . base64_encode($xlsData)
    );
    $excel = $response;
    return $excel;
}

function addParamsEmail($params, $textoEmail) {
    foreach ($params as $key => $value) {
        $textoEmail = str_replace('{{' . $key . '}}', $value, $textoEmail);
    }
    return $textoEmail;
}

function logPayment($message, $reference = null, $log = true) {
    if ($log) {
        $file = fopen(__DIR__ . "/../log/payment/data_" . date("Y-m-d") . ".log", "a");
        fwrite($file, $message . "\n");
        fclose($file);
    }
    if ($reference != null) {
        //se guarda registro de log
        $fecha = App::fecha('Y-m-d H:i:s');
        $request = [
            'date' => $fecha,
            'description' => $message,
            'reference1' => $reference
        ];
        crear($request, 'log_payments');
    }
}

function logActivation($message, $email) {
    $fecha = App::fecha('Y-m-d H:i:s');
    $logMessage = $fecha . ' -- ' . $email . ' -- ' . $message . "\n";
    $file = fopen(__DIR__ . "/../log/activation/data_" . date("Y-m-d") . ".log", "a");
    fwrite($file, $logMessage);
    fclose($file);
}

function throwLogPayment($message, $reference = null, $log = true) {
    logPayment($message, $reference, $log);
    throwError($message);
}

function checkBootValidator($checkBoot) {
    if ($checkBoot) {
        throwError('Error, boot externo fue detectado');
    }
}

function isFilterDate($filter) {
    if (strtoupper(substr($filter, 0, 4)) == 'F|:|') {
        $paramsArray = getParamsFilterDate($filter);
        if (count($paramsArray) >= 3) {
            return true;
        }
    }
    return false;
}

function validarRangoFecha($fechaInicio, $fechaFin, $rango) {
    $start = strtotime($fechaInicio);
    $end = strtotime($fechaFin);
    $days_between = ceil(($end - $start) / 86400);
    if ($days_between > $rango || $days_between <= 0) {
        return false;
    }
    return true;
}

function getParamsFilterDate($filter) {
    $paramsArray = explode("|:|", $filter);
    return $paramsArray;
}

function getCodeCountry($nameCountry) {
    include __DIR__ . "/../include/countriesCode.php";
    $result = array_filter($worldInclude, function($country) use ($nameCountry) {
        return (strtoupper($country['name']) == strtoupper($nameCountry));
    });

    if (empty($result)) {
        return false;
    } else {
        return array_pop($result)['alpha2'];
    }
}

function monthToDate($mes, $subs = 0) {

    if (strlen($mes) != 7 || count(explode("-", $mes)) != 2) {
        $mes = '2000-01';
    }

    if ($subs != 0) {
        $mes = date('Y-m', strtotime("$mes - $subs month"));
    }

    $arrMes = explode("-", $mes);

    $aux = date('Y-m-d', strtotime("$mes + 1 month"));
    $diaFin = date('d', strtotime("$aux - 1 day"));
    $mesAnterior = date('Y-m', strtotime("$mes - 1 month"));

    $inicio = date('Y-m-01 00:00:01', strtotime($mes));
    $fin = date("Y-m-$diaFin 23:59:59", strtotime($mes));

    $soloAno = $arrMes[0];
    $soloMes = $arrMes[1];

    return [
        'inicio' => $inicio,
        'fin' => $fin,
        'mes' => $soloMes,
        'ano' => $soloAno,
        'dia_fin' => $diaFin,
        'mes_anterior' => $mesAnterior,
    ];
}

function obtenerTasa($a, $b) {
    if (is_numeric($a) && is_numeric($b) &&
            $a > 0) {
        $totalIncremento = round((($b / $a) - 1) * 100, 1);
        return $totalIncremento;
    }
    if ($a == 0) {
        return 100;
    }
    return 0;
}

function initGlobals() {
    $globals_variables = array();
    $content = table('config');
    $response = $content->getContent();
    foreach ($response as $config) {
        $globals_variables[$config['description']] = $config['value'];
    }
    return $globals_variables;
}

function getLastName($name) {
    $nameUserArray = explode(" ", $name);
    $countNames = count($nameUserArray);

    $firstName = $name;
    $lastName = '';

    if ($countNames == 3) {
        $firstName = $nameUserArray[0] . ' ' . $nameUserArray[1];
        $lastName = $nameUserArray[2];
    } else if ($countNames == 4) {
        $firstName = $nameUserArray[0] . ' ' . $nameUserArray[1];
        $lastName = $nameUserArray[2] . ' ' . $nameUserArray[3];
    } else if ($countNames == 5) {
        $firstName = $nameUserArray[0] . ' ' . $nameUserArray[1] . ' ' . $nameUserArray[2];
        $lastName = $nameUserArray[3] . ' ' . $nameUserArray[4];
    }
    $firstName = limpiarStringEspanol($firstName);
    $lastName = limpiarStringEspanol($lastName);

    if ($lastName == '') {
        $lastName = '-';
    }

    $nameResponse = [
        'firstName' => $firstName,
        'lastName' => $lastName
    ];

    return $nameResponse;
}

function secToFormat($seconds) {
    $hours = floor($seconds / 3600);
    $mins = floor($seconds / 60 % 60);
    $secs = floor($seconds % 60);
    $timeFormat = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
    return $timeFormat;
}

function sumTheTime($time1, $time2) {
    $times = array($time1, $time2);
    $seconds = 0;
    foreach ($times as $time) {
        list($hour, $minute, $second) = explode(':', $time);
        $seconds += $hour * 3600;
        $seconds += $minute * 60;
        $seconds += $second;
    }
    $hours = floor($seconds / 3600);
    $seconds -= $hours * 3600;
    $minutes = floor($seconds / 60);
    $seconds -= $minutes * 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

/*
 * METODOS PARA API PRINT SERVER
 */

function getParamSesion($keyParam) {
    try {
        if (!isset($_SESSION)) {
            session_start();
        }
        if (isset($_SESSION[$keyParam])) {
            return $_SESSION[$keyParam];
        }        
        return 0;
    } catch (Exception $e) {
        $message = 'Error creando sesion';
        $function = __FUNCTION__;
        throwException($e, $message, $function);
    }
}

function addParamSesion($keyParam, $valueParam) {
    try {
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION[$keyParam] = $valueParam;
    } catch (Exception $e) {
        $message = 'Error obteniendo sesion';
        $function = __FUNCTION__;
        throwException($e, $message, $function);
    }
}
