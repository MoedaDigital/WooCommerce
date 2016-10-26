function cardtype_change() {
    var x = document.getElementById("cardtype").value;
    //document.getElementById("demo").innerHTML = "You selected: " + x;

    if (x == 'boleto') {
        jQuery("#lblCardType").val('Boleto');
        jQuery("#paymenttypecc").css("display", "none");
        jQuery("#paymenttypebb").css("display", "block");
        jQuery("#divCard").css("display", "none");
        var x = document.getElementById("paymenttypebb").value;
        jQuery("#paymenttypevalue").val(x);
        jQuery("#paymenttypecc").val('');
    }
    else {
        jQuery("#lblCardType").val('Credito');
        jQuery("#paymenttypecc").css("display", "block");
        jQuery("#paymenttypebb").css("display", "none");
        jQuery("#divCard").css("display", "block");
        var x = document.getElementById("paymenttypecc").value;
        jQuery("#paymenttypevalue").val(x);
    }
}

function paymenttypecc_change() {
    var x = document.getElementById("paymenttypecc").value;
    jQuery("#paymenttypevalue").val(x);
}

function validate_cvv(str) {
    if (jQuery("input#place_order").attr("disabled") != "disabled") {
        jQuery("input#place_order").attr("disabled", "disabled");
        jQuery("input#place_order").val("Please enter 3 or 4 numbers in the card security code field");
    }

    for (var i = 0; i < str.length; i++) {
        if (isNaN(parseInt(str.charAt(i), 10)) || i > 3) return;
    }

    if (i > 2) {
        jQuery("input#place_order").removeAttr("disabled");
        jQuery("input#place_order").val("Place order");
    }
}

function validaCartao() {
    var valid = true;
    var value = jQuery('#ccnum').val();

    if (/[^0-9-\s]+/.test(value)) return false;
    var nCheck = 0, nDigit = 0, bEven = false;
    value = value.replace(/\D/g, "");
    jQuery('#ccnum').val(value);
    for (var n = value.length - 1; n >= 0; n--) {
        var cDigit = value.charAt(n),
          nDigit = parseInt(cDigit, 10);

        if (bEven) {
            if ((nDigit *= 2) > 9) nDigit -= 9;
        }
        nCheck += nDigit;
        bEven = !bEven;
    }
    if ((nCheck % 10) != 0) {
        valid = false;
    }
    if (value.length < 9) { valid = false; }
    if (!valid) {
        jQuery('#ccnum').css("border-bottom", "2px solid red");
        valid = false;
    }
    else {
        jQuery('#ccnum').css("border-bottom", "1px solid rgba(255,255,255,0.15);");
    }

    validaTipo();
    return valid;
}

function validaTipo() {
    var number = jQuery('#ccnum').val();
    if (number != '') {
        var type = '';
        var re = {
            //electron: /^(4026|417500|4405|4508|4844|4913|4917)\d+$/,
            //maestro: /^(5018|5020|5038|5612|5893|6304|6759|6761|6762|6763|0604|6390)\d+$/,
            visa: /^4[0-9]{12}(?:[0-9]{3})?$/,
            mastercard: /^5[1-9][0-9]{14}$/,
            amex: /^3[47][0-9]{13}$/,
            diners: /^3(?:0[0-5]|[68][0-9])[0-9]{11}$/,
            discover: /^6(?:011|5[0-9]{2})[0-9]{12}$/,
            elo: /^(5041|4514|6362|5066|4011|4389|4576|5067|5090|6363)\d+$/
        };
        //if (re.electron.test(number)) {
        //    type = 'ELECTRON';
        //} else if (re.maestro.test(number)) {
        //    type = 'MAESTRO';
        //} else
        if (re.visa.test(number)) {
            type = 'VISA';
        } else if (re.mastercard.test(number)) {
            type = 'MASTERCARD';
        } else if (re.amex.test(number)) {
            type = 'AMEX';
        } else if (re.diners.test(number)) {
            type = 'DINERS';
        } else if (re.discover.test(number)) {
            type = 'DISCOVER';
        } else if (re.elo.test(number)) {
            type = 'ELO';
        } else {
            type = 'erro';
        }

        console.log(jQuery('#lblIcon').html() + type + '.png');
        jQuery('#lblCardType').val(type);
        jQuery('#imgBandeira').attr("src", (jQuery('#lblIcon').html() + 'images/' + type + '.png'));

        //if (type != $('#<%=cmbBandeira.ClientID %>').val()) {
        //    $('#<%=cmbBandeira.ClientID %>').css("border-bottom", "2px solid red");
        //}
        //else {
        //    $('#<%=cmbBandeira.ClientID %>').css("border-bottom", "1px solid rgba(255,255,255,0.15);");
        //}
    }
}

function abrirPopup(url) {
    window.open(url, 'Payment', 'menubar=1,resizable=1,scrollbars=yes, width=980,height=640');
}