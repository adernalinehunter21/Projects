var variableToLoadTheResult = null;
var postUploadCallBackFunction = null;
var is_video_duration_required = null;
var video_duration = null;
function initiateFileUploadModal(s3_details, internal_file_name_prefix, callBackFunc = null, file_type = '*', video_duration_required = false) {

    postUploadCallBackFunction = callBackFunc;
    variableToLoadTheResult = null;
    is_video_duration_required = video_duration_required;

    //Set the action url to form in the upload modal (Defined in FacilitatorBase.html)
    var s3bucketUrl = '//' + s3_details['bucket'] + '.s3-' + '' + s3_details['region'] + '.amazonaws.com';
    $('#file-upload-form').attr('action', s3bucketUrl);

    //Set the internal file name
    $('#file-upload-form > input[name="key"]').val(internal_file_name_prefix + "" + Math.floor((Math.random() * 99) + 1));

    $('#file-upload-form > input[name="AWSAccessKeyId"]').val(s3_details['accesskey']);
    $('#file-upload-form > input[name="acl"]').val(s3_details['acl']);
    $('#file-upload-form > input[name="policy"]').val(s3_details['base64Policy']);
    $('#file-upload-form > input[name="signature"]').val(s3_details['signature']);
    if (file_type === "*") {
        $('#file-input').removeAttr('accept');
    } else {
        $('#file-input').attr('accept', file_type);
    }

    $('#file-upload-modal').modal('show');
}



$(document).ready(function () {
    $('.direct-upload').each(function () {
        var form = $(this);
        
        if(is_video_duration_required){
            
        }

        form.fileupload({
            url: form.attr('action'),
            type: 'POST',
            datatype: 'xml',
            add: function (event, data) {
                $('#file-input').hide();
                // Message on unLoad.
                window.onbeforeunload = function () {
                    return 'You have unsaved changes.';
                };

                // Submit
                data.submit();
            },
            send: function (e, data) {
                // onSend
            },
            progress: function (e, data) {
                $('.progress').show();
                // This is what makes everything really cool, thanks to that callback
                // you can now update the progress bar based on the upload progress.
                var percent = Math.round((data.loaded / data.total) * 100);
                $('.bar').css('width', percent + '%');
            },
            fail: function (e, data) {
                // Remove 'unsaved changes' message.
                window.onbeforeunload = null;
            },
            success: function (data) {
                // onSuccess
            },
            done: function (event, data) {
                window.onbeforeunload = null;
                
                var uploadedInternalFileName = $('#file-upload-form > input[name="key"]').val();
                var uploadedFile = data.files[0];
                
                variableToLoadTheResult = 
                    {
                        fileName: uploadedFile.name,
                        internalFileName: uploadedInternalFileName,
                        fileSize: uploadedFile.size,
                        fileType: uploadedFile.type
                    };
            
                $('#file-upload-modal').modal('hide');
                
                if(postUploadCallBackFunction !== null){
                    postUploadCallBackFunction(variableToLoadTheResult);
                }
            },
        });
    });

    $('#file-upload-modal').on('hidden.bs.modal', function () {
        $('.bar').css('width', '0');
        $('.progress').hide();
        $("#file-upload-form").trigger('reset');
        $('#file-input').show();
    });
});
