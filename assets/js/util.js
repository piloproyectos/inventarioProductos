var functionsInhabilited = [];
var iti_global;

function getParam(param) {
    url = document.URL;
    url = String(url.match(/\?+.+/));
    url = url.replace("?", "");
    url = url.split("&");
    x = 0;
    while (x < url.length)
    {
        p = url[x].split("=");
        if (p[0] == param)
        {
            return decodeURIComponent(p[1]);
        }
        x++;
    }
    return '';
}

function ajustarModales() {
    $(".modal").on("hidden.bs.modal", function () {
        $('head').append('<style type="text/css"> .modal-content {max-height: calc(95vh)\n\
                !important;overflow-y: auto !important;}</style>');
    });
    $(".modal").on("show.bs.modal", function () {
        $('head').append('<style type="text/css"> .modal-content {max-height: 100%\n\
                !important;overflow-y: hidden !important;}</style>');
    });
}
function mostrarBtnOpciones(id) {
    ocultarBtnOpciones();
    $("." + id + "-options").show();
    $("." + id + "-btns-containers").hide();
}
function ocultarBtnOpciones() {
    $(".buttons-container").show();
    $(".buttons-options").hide();
}

function validarReturn(global_return, my_return) {
    if (!isset(my_return)) {
        if (global_return === null) {
            console.log("error en validacion de return");
            return false;
        }
    }
    return true;
}

function getDate() {
    var date = new Date(),
            year = date.getFullYear(),
            month = (date.getMonth() + 1).toString(),
            formatedMonth = (month.length === 1) ? ("0" + month) : month,
            day = date.getDate().toString(),
            formatedDay = (day.length === 1) ? ("0" + day) : day,
            hour = date.getHours().toString(),
            formatedHour = (hour.length === 1) ? ("0" + hour) : hour,
            minute = date.getMinutes().toString(),
            formatedMinute = (minute.length === 1) ? ("0" + minute) : minute,
            second = date.getSeconds().toString(),
            formatedSecond = (second.length === 1) ? ("0" + second) : second;
    return formatedDay + "-" + formatedMonth + "-" + year + " " + formatedHour + ':' + formatedMinute + ':' + formatedSecond;
}

function initCron() {
    setInterval(crono, 400);
}

function crono() {
    var iniDate = $('.cron-div').attr("data-countdown");
    var start = moment.tz(iniDate, "America/Bogota");
    var end = moment.tz(new Date(), "America/Bogota");
    var duration = moment.duration(end.diff(start));
    var hh = duration.asHours();
    var mm = duration.minutes();
    var ss = duration.seconds();

    hh = Math.round(hh);

    if (ss < 10) {
        ss = "0" + ss;
    }
    if (mm < 10) {
        mm = "0" + mm;
    }
    if (hh < 10) {
        hh = "0" + hh;
    }
    var html = hh + " : " + mm + " : " + ss;
    $('.cron-div').html(html);
}

function sendRequest(controller, param, resultado) {
    inhabiliteRequest(param.function);
    if (resultado === undefined) {
        resultado = true;
    }
    if (getStorage('url_server') == '0') {
        param.url_server = globalUrlDefault;
    } else {
        param.url_server = getStorage('url_server');
    }
    url = "controller/apiController/" + controller + "Controller.php";
    var request = $.ajax({
        type: 'POST',
        url: url,
        data: param,
        dataType: 'json',
        beforeSend: function () {
            if (resultado) {
                result('Procesando, por favor espere..', 'info');
            }
        },
        error: function (request, satus, error) {
            if (resultado) {
                result('Ocurrio un error, por favor intente nuevamente!', 'error');
            }
            habiliteRequest(param.function);
            console.log('Ajax Error: Error procesando peticion en la funcion js:' + param['function'] + ' llamada a controller:' + controller + '->' + param['mtrx']);
            //console.log(data); 
            console.log("Status: " + satus + " Error: " + error);
            console.log(request);
        }
    });
    return request;
}

function responseValidator(response, data, continua, scrollTop) {
    if (continua === undefined) {
        continua = false;
    }
    if (scrollTop === undefined) {
        scrollTop = true;
    }
    var status;
    if (response.status == 1) {
        status = true;
    }
    else {
        console.log(response);
        result(response.error.message, 'error', scrollTop);
        status = false;
    }
    habiliteRequest(data.function);
    if (continua) {
        status = true;
    }
    return status;
}

function requestValidator(data) {
    if (!data.function) {
        console.log('No se encontro function en el request.');
        return false;
    }
    if (!data.mtrx) {
        console.log('No se encontro MtRx en el request.');
        return false;
    }
    if (searchStatusRequest(data.function)) {
        console.log("Procesando una petición de -> " + data.function + ", por favor espere para continuar.");
        //Retorna falso para que no vuelva a hacer petición
        return false;
    }
    inhabiliteRequest(data.function);
    return true;
}
function habiliteRequest(nameFunction) {
    removeItemFromArray(functionsInhabilited, nameFunction);
}

function inhabiliteRequest(nameFunction) {
    functionsInhabilited.push(nameFunction);
}

function searchStatusRequest(nameFunction) {
    if (functionsInhabilited.includes(nameFunction)) {
        return true;
    }
    return false;
}

function removeItemFromArray(array, item) {
    var allValidate = true;
    while (allValidate) {
        allValidate = false;
        var i = array.indexOf(item);
        if (i !== -1) {
            array.splice(i, 1);
            allValidate = true;
        }
    }
}

function result(msj, tipo, scrollTop) {
    if (scrollTop === undefined) {
        scrollTop = true;
    }
    if (tipo == 'error') {
        $('.resultado').hide();
        $('.resultado').attr("class", "resultado  bg-danger text-white alert");
        $('.resultado').html('<p>' + msj + '</p>');
        $('.resultado').stop(true).fadeIn("slow").delay(7000).fadeOut("slow");
    } else if (tipo == 'info') {
        $('.resultado').hide();
        $('.resultado').attr("class", "resultado  bg-info text-white alert");
        $('.resultado').html('<p>' + msj + '</p>');
        $('.resultado').stop(true).fadeIn("slow").delay(7000).fadeOut("slow");
    } else {
        $('.resultado').hide();
        $('.resultado').attr("class", "resultado  bg-success text-white alert");
        $('.resultado').html('<p>' + msj + '</p>');
        $('.resultado').stop(true).fadeIn("slow").delay(7000).fadeOut("slow");
    }
    if (scrollTop) {
        window.scroll(0, 0);
    }

}

function inputForm(name) {
    var inputReturn = $('input[name=' + name + ']').val();
    if (!isset(inputReturn)) {
        inputReturn = $('textarea[name=' + name + ']').val();
    }
    return inputReturn;
}
function setInputForm(name, value) {
    $('input[name=' + name + ']').val(value);
    $('textarea[name=' + name + ']').val(value);
}
function inputFormSelect(myClass) {
    return $('.' + myClass + ' option:selected').val();
}
function setInputFormSelect(myClass, value) {
    $('.' + myClass + ' option[value="' + value + '"]').prop('selected', true);
    var attr = $('.' + myClass).attr('multiple');
    if (isset(attr)) {
        $('.' + myClass).val(value).trigger("change");
        $('.form-validator').validator();
    }
}
function inputChecked(name) {
    return $('input[name=' + name + ']:checked').val();
}
function checkboxStatus(clase) {
    if ($('.' + clase)[0].checked) {
        return true;
    } else {
        return false;
    }
}
function openModal(modalId) {

}
function habilitarCheckbox() {
    $('.checkbox-flat').iCheck({checkboxClass: 'icheckbox_flat-green', radioClass: 'iradio_flat-green'});
}

function execute(name) {
    eval(name + "()");
}

function limpiarBuscador() {
    $(".search-input").val("");
    $(".field-search").show();
}
function setMultiselect(request, clase, max) {
    if (max == 'n') {
        max = 0;
    }
    var dataJson = JSON.parse(request);
    $('.' + clase).select2({
        data: dataJson,
        maximumSelectionLength: max,
        escapeMarkup: function (markup) {
            return markup;
        },
        templateResult: function (myData) {
            return myData.html;
        },
        templateSelection: function (myData) {
            return myData.text;
        }
    });
}
function getSelectMultiple(myClass) {
    var data = $('.' + myClass).val();
    if (isset(data) && data != null) {
        data = $('.' + myClass).val().join();
    } else {
        data = '';
    }
    return data;
}
function toSelectMultiple(content, myClass, key) {
    var dataArray = new Array();
    for (var i = 0; i < content.length; i++) {
        dataArray.push(content[i][key]);
    }
    $('.' + myClass).val(dataArray).trigger("change");
    $('.form-validator').validator();
}
function isset(value) {
    if (typeof value === typeof undefined || value === false) {
        return false;
    } else {
        return true;
    }
}
function getCart() {
    var cart_code = localStorage.getItem('cart_code');
    if (cart_code === null) {
        cart_code = 0;
    }
    return cart_code;
}
function setCart(cart_code) {
    localStorage.setItem('cart_code', cart_code);
}
function removeCart() {
    localStorage.removeItem("cart_code");
}
function ncf(nStr) {
    nStr += '';
    var x = nStr.split('.');
    var x1 = x[0];
    var x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + '.' + '$2');
    }
    var numeroConvert = x1 + x2;
    return numeroConvert;

}

function activeCarrusel() {
    $(".owl-carousel-2").each(function () {
        $(this).owlCarousel({
            loop: true,
            margin: 10,
            nav: true
        });
    });
}

function showCover(text) {
    if (text === undefined) {
        text = 'Cargando imagen...';
    }
    $('#cover-spin').fadeIn("slow");
    $('.text-cover').html(text);
}
function hideCover() {
    $('#cover-spin').fadeOut("slow");
    $('.text-cover').html('');
}

function nicescrollResize() {
    //$("html").getNiceScroll().resize();
}

function nicescrollInit() {
    $(document).on('mouseover', 'html', function () {
        $(this).getNiceScroll().resize();
    });
}

function scrolltToClass(claseDestino) {
    var dest = $("." + claseDestino).offset().top;
    $("html, body").animate({scrollTop: (dest)}, 600);
}

function loadVideoVimeoThumb() {
    $(".src-vimeo-thumb").each(function () {
        var video_id = $(this).attr('video-id');
        var video_div = $("[video-id=" + video_id + "]");
        video_div.html('<div class="video-vimeo-load horizon-title"><i class="fa fa-play-circle sun-title"></i></div>');
        if (isset(video_id) && video_id > 0) {
            $.ajax({
                type: 'GET',
                url: 'http://vimeo.com/api/v2/video/' + video_id + '.json',
                jsonp: 'callback',
                dataType: 'jsonp',
                success: function (data) {
                    var thumbnail_src = data[0].thumbnail_large;
                    video_div.html('<img src="' + thumbnail_src + '"/>');
                    console.log(thumbnail_src);
                }
            });
        }
    });
}

function getStorage(key) {
    var storageVal = localStorage.getItem(key);
    if (storageVal === null) {
        storageVal = 0;
    }
    return storageVal;
}
function setStorage(key, value) {
    localStorage.setItem(key, value);
}
function deleteStorage(key) {
    localStorage.removeItem(key);
}


/*
 * ======================================
 * ======================================
 * Inicia Document.ready
 * ======================================
 * ======================================
 */
$(document).ready(function () {
    /*
     * ======================================
     */
    // Checkboxes and radio
    /*
     $('.i-check, .i-radio').iCheck({
     checkboxClass: 'i-check',
     radioClass: 'i-radio'
     });
     */

    //para input tipo telefono
    var input = document.querySelector("#phone");
    if (input != null) {
        iti_global = window.intlTelInput(input, {
            autoFormat: false,
            autoHideDialCode: false,
            nationalMode: false,
            preferredCountries: ['co', 'ec', 'mx', 'pe', 'bo', 'cl', 'ar', 'us', 'py', 'es'],
            separateDialCode: true,
            utilsScript: "assets/js/all/phoneformat/js/utils.js",
        });
    }

    $(".action").on("click", function (e) {
        var myFunction = $(this).attr('function');
        if (typeof myFunction === typeof undefined || myFunction === false) {
            console.log("Function of action is not found.");
            e.preventDefault();
            return;
        }
        if ($(this).is('disabled') || $(this).hasClass('disabled')) {
            console.log('Action could not be executed because button is disabled');
        } else {
            console.log(myFunction);
            eval(myFunction + "()");
        }
    });

    //Se inicializa reproductor plyr cuando existe.
    if (($("#player").length > 0)) {
        new Plyr('#player');
    }
    if (($("#player2").length > 0)) {
        new Plyr('#player2');
    }
    if (($("#player3").length > 0)) {
        new Plyr('#player3');
    }

    activeCarrusel();
    //nicescrollInit();
    //loadVideoVimeoThumb();

});
