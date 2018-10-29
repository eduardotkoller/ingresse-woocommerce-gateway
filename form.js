function getCardBrand(value) {
    var match = null;
    var card;
    var cards = {
        elo: '',
        visa: /^4[0-9]{12}(?:[0-9]{3})?$/, // length 16, prefix 4, dashes optional
        amex: /^3[47][0-9]{13}$/, // length 15, prefix 34 or 37
        diners: /^3(?:0[0-5]|[68][0-9])[0-9]{11}$/, // length 14, prefix 30, 36, or 38
        master: '',
    };

    // Elo
    cards.elo += '^(4011(78|79)|43(1274|8935)|45(1416|7393|763(1|2))|50(4175|6699|67[0-7][0-9]|9000)';
    cards.elo += '|50(9[0-9][0-9][0-9])|627780|63(6297|6368)|650(03([^4])|04([0-9])|05(0|1)|05([7-9])';
    cards.elo += '|06([0-9])|07([0-9])|08([0-9])|4([0-3][0-9]|8[5-9]|9[0-9])|5([0-9][0-9]|3[0-8])';
    cards.elo += '|9([0-6][0-9]|7[0-8])|7([0-2][0-9])|541|700|720|727|901)|65165([2-9])|6516([6-7][0-9])';
    cards.elo += '|65500([0-9])|6550([0-5][0-9])|655021|65505([6-7])|6516([8-9][0-9])|65170([0-4]))';

    cards.elo = new RegExp(cards.elo);

    // length 16, prefix 51-55, dashes optional
    // new range from 222100 to 272099
    cards.master += '^5[1-5][0-9]{14}$';
    cards.master += '|^26[0-9]{4}|^25[0-9]{4}|^24[0-9]{4}|^23[0-9]{4}|^228[0-9]{3}';
    cards.master += '|^227[0-9]{3}|^226[0-9]{3}|^225[0-9]{3}|^224[0-9]{3}|^223[0-9]{3}';
    cards.master += '|^2228[0-9]{2}|^2227[0-9]{2}|^2226[0-9]{2}|^2225[0-9]{2}|^2224[0-9]{2}';
    cards.master += '|^2223[0-9]{2}|^2222[0-9]{2}|^22218[0-9]|^22217[0-9]|^22216[0-9]|^22215[0-9]';
    cards.master += '|^22214[0-9]|^22213[0-9]|^22212[0-9]|^22211[0-9]|^22210[0-9]|^22219[0-9]|^22298[0-9]';
    cards.master += '|^22297[0-9]|^22296[0-9]|^22295[0-9]|^22294[0-9]|^22293[0-9]|^22292[0-9]|^22291[0-9]';
    cards.master += '|^22290[0-9]|^22299[0-9]|^2298[0-9]{2}|^2297[0-9]{2}|^2296[0-9]{2}|^2295[0-9]{2}';
    cards.master += '|^2294[0-9]{2}|^2293[0-9]{2}|^2292[0-9]{2}|^2291[0-9]{2}|^22908[0-9]|^22907[0-9]';
    cards.master += '|^22906[0-9]|^22905[0-9]|^22904[0-9]|^22903[0-9]|^22902[0-9]|^22901[0-9]|^22900[0-9]';
    cards.master += '|^22909[0-9]|^22998[0-9]|^22997[0-9]|^22996[0-9]|^22995[0-9]|^22994[0-9]|^22993[0-9]';
    cards.master += '|^22992[0-9]|^22991[0-9]|^22990[0-9]|^22999[0-9]|^271[0-9]{3}|^2708[0-9]{2}|^2707[0-9]{2}';
    cards.master += '|^2706[0-9]{2}|^2705[0-9]{2}|^2704[0-9]{2}|^2703[0-9]{2}|^2702[0-9]{2}|^2701[0-9]{2}';
    cards.master += '|^27008[0-9]|^27007[0-9]|^27006[0-9]|^27005[0-9]|^27004[0-9]|^27003[0-9]|^27002[0-9]';
    cards.master += '|^27001[0-9]|^27000[0-9]|^27009[0-9]|^27098[0-9]|^27097[0-9]|^27096[0-9]|^27095[0-9]';
    cards.master += '|^27094[0-9]|^27093[0-9]|^27092[0-9]|^27091[0-9]|^27090[0-9]|^27099[0-9]|^27208[0-9]';
    cards.master += '|^27207[0-9]|^27206[0-9]|^27205[0-9]|^27204[0-9]|^27203[0-9]|^27202[0-9]|^27201[0-9]';
    cards.master += '|^27200[0-9]|^27209[0-9]';

    cards.master = new RegExp(cards.master);

    // Test model value with cards regex
    for (card in cards) {
        if (cards[card].test(value.replace(/\D/g, ''))) {
            match = card;
            break;
        }
    }

    return match;
};

jQuery(function(){
    function initCardJs(){
        var card = new Card({
            // a selector or DOM element for the form where users will
            // be entering their information
            form: document.querySelector('form.checkout'), // *required*
            // a selector or DOM element for the container
            // where you want the card to appear
            container: '.card-wrapper', // *required*

            formSelectors: {
                numberInput: 'input#ingresse_ccNo', // optional — default input[name="number"]
                expiryInput: 'input#ingresse_expdate', // optional — default input[name="expiry"]
                cvcInput: 'input#ingresse_cvv', // optional — default input[name="cvc"]
                nameInput: 'input#ingresse_ccName' // optional - defaults input[name="name"]
            },

            formatting: true, // optional - default true

            // Strings for translation - optional
            messages: {
                validDate: 'valido\naté', // optional - default 'valid\nthru'
                monthYear: 'mm/aa', // optional - default 'month/year'
            },

            // Default placeholders for rendered fields - optional
            placeholders: {
                number: '•••• •••• •••• ••••',
                name: 'Nome no cartão',
                expiry: 'MM/AA',
                cvc: '•••'
            },

            // if true, will log helpful messages for setting up Card
            debug: false // optional - default false
        });
        jQuery('input#ingresse_ccNo').on('change', function(e){
            var value = e.target.value;
            var brand = getCardBrand(value);
            jQuery('#ingresse_ccBrand').detach();
            if(!brand) brand = 'notvalid';
            jQuery(e.target).after('<input type="hidden" id="ingresse_ccBrand" name="ingresse_ccBrand" value="'+brand+'" />');
        });
        if(jQuery('input#ingresse_ccNo').val()!='') {
            var value = jQuery('input#ingresse_ccNo').val();
            var brand = getCardBrand(value);
            jQuery('#ingresse_ccBrand').detach();
            if(!brand) brand = 'notvalid';
            jQuery('input#ingresse_ccNo').after('<input type="hidden" id="ingresse_ccBrand" name="ingresse_ccBrand" value="'+brand+'" />');
        }
        var evt = document.createEvent('HTMLEvents');
        evt.initEvent('keyup', false, true);
        document.getElementById('ingresse_ccNo').dispatchEvent(evt);
        document.getElementById('ingresse_ccName').dispatchEvent(evt);
        document.getElementById('ingresse_expdate').dispatchEvent(evt);
        document.getElementById('ingresse_cvv').dispatchEvent(evt);
    }
    jQuery(document.body).on('updated_checkout', initCardJs);
    jQuery('#payment_method_ingresse_cc').on('change', initCardJs);
});
