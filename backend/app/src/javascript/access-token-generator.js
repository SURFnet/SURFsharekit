(function ($) {

    $('body').on('click', '.generate-code-button', function () {
        let fieldname = $(this).data('fieldname');
        $("input[name=" + fieldname + "]").val(generateUUID());
    });

})(jQuery);

//Source: https://stackoverflow.com/questions/105034/create-guid-uuid-in-javascript RFC4122 version 4 compliant solution
function generateUUID() { // Public Domain/MIfT
    let d = new Date().getTime();
    if (typeof performance !== 'undefined' && typeof performance.now === 'function'){
        d += performance.now(); //use high-precision timer if available
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        let r = (d + Math.random() * 16) % 16 | 0;
        d = Math.floor(d / 16);
        return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
}