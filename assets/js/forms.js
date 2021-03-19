/* GLOBALS */
$(document).ready(function () {

});

function hideAllDivs() {
    $('.div-config').hide();
    $('.div-impresoras').hide();
    $('.div-log').hide();
}

function divImpresoras() {
    hideAllDivs();
    $('.div-impresoras').show();
}

function divConfig() {
    hideAllDivs();
    $('.div-config').show();
}

function divLista() {
    hideAllDivs();
}

function divLog() {
    hideAllDivs();
    $('.div-log').show();
}


