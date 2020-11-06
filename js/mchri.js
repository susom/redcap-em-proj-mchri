
$(document).ready(function () {

    mchri.init();
});

var mchri = mchri || {};

mchri.init = function() {
    console.log("binding");

    $('#budget_worksheetx').on('click', 'button', function() {
        console.log("click on downloading file...");

        let edoc_id = $(this).data('id');

        console.log("edoc id " +  edoc_id);

        let formValues = {
            "download": true,
            "edoc_id" : $(this).data('id')
        };


        var jqxhr = $.ajax({
            method: 'POST',
            data: formValues,
            dataType: "json"
        })
            .done(function (data) {
                console.log("RESUMING====: ", data.result);
                console.log( "done: statusText:  " + jqxhr.statusText);
                console.log(data);
                if (data.result === 'success') {
                    console.log(data);
                    //alert(data.msg);
                    //location.reload();
                }
            })
            .fail(function (data) {
                console.log("DATA: ", data);
                alert("error: Unable to download". data);
                console.log( "error saving responses : statusText:  " + jqxhr.statusText);
                console.log( "error saving responses : status: " + jqxhr.status);
            });
    });

    $('#budge_worksheetx').each(function() {
        $(this).doStuff();
    });

    $('#budget_worksheetx').each(function(index) {
        $(this).on("click", function(){
            // For the boolean value
            var boolKey = $(this).data('selected');
            console.log("one"+boolKey);

            var boolKey = $(this).data('#budget_worksheet');
            console.log("twp"+boolKey);
            // For the mammal value
            var mammalKey = $(this).attr('id');

            console.log("two"+mammalKey);

            let edoc_id = $(this).data('id');
            console.log("thri"+edoc_id);
        });
    });

    //bind button to save Form
  //  $('#budget_worksheet').on('click', mchri.downloadFile);
//    console.log("binding 2");

    $('.btn-download').on('click', mchri.downloadFile);


    $('#chri_proposal').on('click', mchri.downloadFile);



}


mchri.downloadFile = function() {

    console.log("downloading file...");

    let edoc_id = $(this).data('id');

    console.log("edoc id " +  edoc_id);

    let formValues = {
        "download": true,
        "edoc_id" : $(this).data('id')
    };


    var jqxhr = $.ajax({
        method: 'POST',
        data: formValues,
        dataType: "json"
    })
        .done(function (data) {
            console.log("RESUMING====: ", data.result);
            console.log( "done: statusText:  " + jqxhr.statusText);
            console.log(data);
            if (data.result === 'success') {
                console.log(data);
                //alert(data.msg);
              //  location.reload();
            }
        })
        .fail(function (data) {
            console.log("DATA: ", data);
            alert("error: Unable to download". data);
            console.log( "error saving responses : statusText:  " + jqxhr.statusText);
            console.log( "error saving responses : status: " + jqxhr.status);
        });
};