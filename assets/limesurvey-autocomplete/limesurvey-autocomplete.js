/**
 * This file is part of autocomplete (plugin for LimeSurvey)
 * @version 1.2.2
 */
function setAutoCompleteCode(elementid,options) {
    if(!$('input#'+elementid).length) {
        return;
    }
    var sgq = elementid.replace("answer","");
    jQuery('<input/>', {
        id: 'autocomplete'+sgq,
        type: 'text',
        size : $('#'+elementid).attr('size'),
        value : $('#'+elementid).val(),
        name : 'autocomplete'+sgq,
        onkeyup : '' // Disable default em action
    }).attr('class','text-autocomplete '+$('#answer'+sgq).attr('class')).insertBefore('#answer'+sgq);
    if(options.filterBy) {
        $('#answer'+sgq).data('filtered',$('#'+options.filterBy).text());
    } else {
        $('#answer'+sgq).data('filtered',"");
    }
    $('#answer'+sgq).hide();

    /* Set the current value if needed */
    if(options.replaceValue) {
        $('#autocomplete'+sgq).val(options.replaceValue);
    }
    /* Add the placeholder */
    if(options.placeholder) {
        $('#autocomplete'+sgq).attr("placeholder",options.placeholder);
    }
    /* Launch autocomplete to the new input */
    $('#autocomplete'+sgq).devbridgeAutocomplete({
        serviceUrl: options.serviceUrl,
        autoSelectFirst:true,
        noCache : options.useCache && options.filterBy && $('#'+options.filterBy).text() != $('#'+options.filterBy).html(),
        minChars : options.minChar,
        ajaxSettings:{
            beforeSend : function(jqXHR, settings) {
                if(options.filterBy) {
                    settings.url += "&filter="+$('#'+options.filterBy).text();
                }
            }
        },
        onSelect: function (suggestion) {
            var $control = $("#answer"+sgq);
            $control.val( options.oneColumn > 0 ? suggestion.value : suggestion.data);
            $control.data('filtered',$("#"+options.filterBy).text());

            setAutoComplete_Dependants(suggestion, options);

            $control.trigger("keyup");
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

    if(options.filterBy) {
        /* Action if filter are update : only for 3.X version */
        $('#'+options.filterBy).on("html:updated",function(e) {
            if($('#answer'+sgq).data('filtered') != $(this).text()) {
                $('#autocomplete'+sgq).devbridgeAutocomplete().clear();
                $('#autocomplete'+sgq).val(""); // clear didn't really clear
                $('#answer'+sgq).data('filtered',$(this).text());
                $('#answer'+sgq).val("").trigger("keyup"); // Can have issue with multiple action in EM (example : relevance + filter)
            }
        });
    }
    if(options.asDropDown) {
        $('#autocomplete'+sgq).on("keyup keydown",function(e) {
            var code = e.keyCode || e.which;
            if (code != '9' && code != '13' && code != '9') {
                e.preventDefault();
            }
        });
    }
    if(!options.asDropDown) {
        $('#autocomplete'+sgq).on("keyup",function(e) {
            $('#answer'+sgq).val($('#autocomplete'+sgq).val()).trigger("keyup");
        });
    }
}

function setAutoCompleteCodeWholeQuestion(qid,options) {
    if(!$('#question'+qid).length) {
        return;
    }
    $('#question'+qid+" .ls-answers .answer-item.text-item input:text").each(function() {
        setAutoCompleteCode($(this).attr('id'),options);
    });

}

function setAutoComplete_Dependants(suggestion, options) {
    console.log(suggestion, options);

    // Get depSGQs
    var depSGQs = options.depSGQs;
    if(!depSGQs || depSGQs.length == 0) return;

    // Set dependents
    Object.keys(depSGQs).forEach(function(sgq,index)
    {
        // key: the name of the object key
        // index: the ordinal position of the key within the object

        var col = depSGQs[sgq];
        $('#answer'+sgq).val(suggestion.line[col-1]);
    });
}
