
$(document).ready(function () {
    mchri.init();
});

var mchri = mchri || {};

mchri.init = function() {
    //bind button to save Form
    $('#budget_worksheet').on('click', crop.budget_worksheet);

    crop.uploadFile = function() {

        console.log("downloading file...");

        let formValues = {
            "download": true,
            "field" : 'budget_worksheet'
        };


        $.ajax({
            type : 'POST',
            data : formValues,
            processData: false,  // tell jQuery not to process the data
            contentType: false,  // tell jQuery not to set contentType
            success : function(data) {
                console.log(data);
                alert(data.msg);
                location.reload();
            }
        })
    };
}