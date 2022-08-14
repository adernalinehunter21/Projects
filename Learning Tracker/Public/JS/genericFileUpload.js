var variableToLoadTheResult = null;
var resultArrayIndex = null;
function initiateFileUploadModal(button_div_id, s3_details, internal_file_name_prefix, callerArrayToLoadTheResult, resultIndex=null, file_type = '*') {

    variableToLoadTheResult = callerArrayToLoadTheResult;
    resultArrayIndex = resultIndex;
    //Set div ID of the upload button for later use
    //It is used to update the button after upload is successfull
    $('#upload-btn-div-id').val(button_div_id);

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
                if(resultArrayIndex === null){
                    variableToLoadTheResult.push(
                            {
                                fileName: uploadedFile.name,
                                internalFileName: uploadedInternalFileName,
                                fileSize: uploadedFile.size,
                                fileType: uploadedFile.type
                            }
                    );
                }
                else{
                    variableToLoadTheResult[resultArrayIndex] = {
                        fileName: uploadedFile.name,
                        internalFileName: uploadedInternalFileName,
                        fileSize: uploadedFile.size,
                        fileType: uploadedFile.type
                    };
                }
                var upload_btn_div_id = $('#upload-btn-div-id').val();
                $('#' + upload_btn_div_id).html('<i class="fa fa-paperclip" aria-hidden="true"></i> ' + uploadedFile.name);
                $('#' + upload_btn_div_id).attr('title', "File is uploaded and ready for submission");

                $('#file-upload-modal').modal('hide');
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
