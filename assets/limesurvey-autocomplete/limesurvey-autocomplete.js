function setAutoCompleteCode(sgq,options) {
    if(!$('input#answer'+sgq).length) {
        return;
    }
    jQuery('<input/>', {
        id: 'autocomplete'+sgq,
        type: 'text',
        size : $('#answer'+sgq).attr('size'),
        value : $('#answer'+sgq).val(),
        name : 'autocomplete'+sgq,
        onkeyup : '' // Disable default em action
    }).attr('class','text-autocomplete '+$('#answer'+sgq).attr('class')).insertBefore('#answer'+sgq);
    $('#answer'+sgq).data('filtered',$('#filter'+sgq).text());
    $('#answer'+sgq).hide();

    /* Set the current value if needed */
    if(options.replaceValue) {
        $('#autocomplete'+sgq).val(options.replaceValue);
    }

    /* Launch autocomplete to the new input */
    $('#autocomplete'+sgq).devbridgeAutocomplete({
        serviceUrl: options.serviceUrl,
        autoSelectFirst:true,
        noCache : options.useCache && $('#filter'+sgq).text() != $('#filter'+sgq).html(),
        minChars : options.minChar,
        ajaxSettings:{
            beforeSend : function(jqXHR, settings) {
                settings.url += "&filter="+$('#filter'+sgq).text();
            }
        },
        onSelect: function (suggestion) {
            if(options.oneColumn > 0) {
                $('#answer'+sgq).val(suggestion.value).trigger("keyup");
            } else {
                $('#answer'+sgq).val(suggestion.data).trigger("keyup");
            }
            $('#answer'+sgq).data('filtered',$("#filter"+sgq).text());
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

    /* Action if filter are update : only for 3.X version */
    $('#filter'+sgq).on("html:updated",function() {
        if($('#answer'+sgq).data('filtered') != $(this).text()) {
            $('#autocomplete'+sgq).devbridgeAutocomplete().clear();
            $('#autocomplete'+sgq).val(""); // clear didn't really clear
            $('#answer'+sgq).data('filtered',$(this).text());
            $('#answer'+sgq).val("").trigger("keyup"); // Can have issue with multiple action in EM (example : relevance + filter)
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
