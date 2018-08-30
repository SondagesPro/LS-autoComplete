function setAutoCompleteCode(sgq,options) {
    if(!$("input#answer"+sgq).length) {
        return;
    }
    jQuery('<input/>', {
        id: 'autocomplete'+sgq,
        type: 'text',
        size : $('#answer'+sgq).attr('size'),
        value : $('#answer'+sgq).val(),
        name : 'autocomplete'+sgq,
    }).attr('class','text-autocomplete '+$('#answer'+sgq).attr('class')).insertBefore('#answer'+sgq);
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
        onSearchStart : function () {
            if(options.asDropDown) {
                $(this).prop("readonly",true);
            }
        },
        onSearchComplete :  function (query, suggestions) {
            if(options.asDropDown) {
                $(this).prop("readonly",false);
            }
        }
    });

    if(options.asDropDown) {
        $('#autocomplete'+sgq).on("keyup keydown",function(e) {
            var code = e.keyCode || e.which;
            if (code != '9' && code != '13' && code != '9') {
                e.preventDefault();
            }
        });
    }
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
    //~ if(options.asDropDown) {
        //~ $('#autocomplete'+sgq).on("keypress keyup",function(e) {
            //~ var code = e.keyCode || e.which;
            //~ if (code != '9') {
                //~ e.preventDefault();
            //~ }
        //~ });
    //~ }
}
