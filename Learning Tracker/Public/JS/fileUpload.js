$(document).ready(function () {
    $('.direct-upload').each(function () {
        var form = $(this);

        form.fileupload({
            url: form.attr('action'),
            type: 'POST',
            datatype: 'xml',
            add: function (event, data) {

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
                var uploadedFile = data.files[0];
                var uploadedFileName = uploadedFile.name;
                $('#fileUploadButton').hide();
                $('.progress').hide();
                $('#uploadedFileName').val(uploadedFileName);
                $('#uploadedFileItem').text(uploadedFileName);
                $('#uploadedFileNameVisible').attr("hidden", false);

                var uploadedFileType = uploadedFile.type;
                $('#uploadedFileType').val(uploadedFileType);
                var uploadedFileSize = uploadedFile.size;
                $('#uploadedFileSize').val(uploadedFileSize);
                // Fill the name field with the file's name.
                $("#assignmentId").val($("#assignment-file-upload-modal").attr("assignmentId"));
                $("#filePurpose").val("assignmentSubmission");

            },
        });
    });

    jQuery('#fileUploadForm').ajaxForm(
            {
                beforeSend: function () {
                    $('#wait').show();
                },
                complete: function (response) {
                    var responseObj = JSON.parse(response.responseText);
                    $('#wait').hide();
                    $('#assignment-submit-button').prop("disabled", true);
                    if (responseObj.status == "Success") {
//                    alert("Done!\nFile has been uploaded");
                        $('#result').html("<p>Your assignment updated successfully</p>");
//                    location.reload();
                    } else {
                        $('#result').html("<p>Update failed with fillowing error. Please close, try again</p><p>Error: " + responseObj.message + "</p>");
                    }
                }
            }
    );

    $('#assignment-file-upload-modal').on('hidden.bs.modal', function () {
        $('.bar').css('width', '0');
        $("#fileUploadForm").trigger('reset');
        $('#result').html("");
        $('#fileUploadButton').show();
        $('.progress').hide();
        $('#uploadedFileNameVisible').attr("hidden", true);
        $('#team').attr("hidden", true);
        if ($('#assignment-submit-button').is(':disabled')) {
            $('#assignment-submit-button').prop("disabled", false);
            location.reload(true);
        }
    })

    $(".assignment-upload-button").on('click', function () {
        var assisgnmentId = $(this).attr("assignmentId");
        $("#assignment-file-upload-modal").attr("assignmentId", assisgnmentId);
         var internalFileName = $(this).attr("internalFileName");
        $("[name=key]").val(internalFileName);
        $('#internalFileName').val(internalFileName);
        var assignment_type = $(this).attr("assignment_type");
        $('#assignmentType').val(assignment_type);

    });


});
