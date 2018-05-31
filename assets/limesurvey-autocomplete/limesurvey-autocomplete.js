function setAutoCompleteCode(sgq,options) {
    if(!$("input#answer"+sgq).length) {
        return;
    }
    /* cache only if filter is not on same page */
    var disableCache = true;
    if($("#filter"+sgq).text() == $("#filter"+sgq).html()) {
        disableCache = false;
    }
    jQuery('<input/>', {
        id: 'autocomplete'+sgq,
        type: 'text',
        size : $('#answer'+sgq).attr('size'),
        value : $('#answer'+sgq).val(),
        name : 'autocomplete'+sgq
    }).attr('class','text-autocomplete '+$('#answer'+sgq).attr('class')).insertBefore('#answer'+sgq);
    //~ console.log($('#answer'+sgq).val());
    $('#answer'+sgq).hide();
    
    if(options.replaceValue) {
        $('#autocomplete'+sgq).val(options.replaceValue);
    }
    $('#autocomplete'+sgq).devbridgeAutocomplete({
        serviceUrl: options.serviceUrl,
        autoSelectFirst:true,
        minChars : options.minChar,
        noCache : $("#filter"+sgq).text() != $("#filter"+sgq).html(),
        ajaxSettings:{
            beforeSend : function(jqXHR, settings) {
                settings.url += "&filter="+$("#filter"+sgq).text();
            }
        },
        onSelect: function (suggestion) {
            $("#answer"+sgq).val(suggestion.data).trigger("keyup");
        },
        onInvalidateSelection: function () {
            $("#autocomplete"+sgq).val("");
            $("#answer"+sgq).val("").trigger("keyup");
        } 
    });
    $('#autocomplete'+sgq).on("blur",function() {
        if(!$("#answer"+sgq).val()) {
            $("#autocomplete"+sgq).val("").trigger("keyup");
        }
    });
}

function setAutoCompleteText(sgq,options) {
    if(!$("input#answer"+sgq).length) {
        return;
    }
    $('#answer'+sgq).devbridgeAutocomplete({
        serviceUrl: options.serviceUrl,
        autoSelectFirst:true,
        minChars : options.minChar,
        noCache : $("#filter"+sgq).text() != $("#filter"+sgq).html(),
        ajaxSettings:{
            beforeSend : function(jqXHR, settings) {
                settings.url += "&filter="+$("#filter"+sgq).text();
            }
        },
        onSelect: function (suggestion) {
            $("#answer"+sgq).trigger("keyup");
        },
    });
}
