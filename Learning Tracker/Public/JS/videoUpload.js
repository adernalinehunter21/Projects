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
                $('#uploadedFileName').val(uploadedFileName);
                var uploadedFileType = uploadedFile.type;
                $('#uploadedFileType').val(uploadedFileType);
                var uploadedFileSize = uploadedFile.size;
                $('#uploadedFileSize').val(uploadedFileSize);
                // Fill the name field with the file's name. 
                $("#videoId").val($("#video-upload-modal").attr("videoId"));
                $("#filePurpose").val("videoSubmission");

                var videoUploadTickMark = "";
                videoUploadTickMark += '<i class="icon fa fa-check on-submission-tickmark mr-1"></i>Video';
                document.getElementById("videoSession").innerHTML = videoUploadTickMark;
                $('#video-upload-modal').modal('hide');
            },
        });
    });

    $('#video-upload-modal').on('hidden.bs.modal', function () {
        $('.bar').css('width', '0');

    });
});

function getInternalFileName() {
    var videoId = $('#videoSession').attr("sessionId");
    $("#video-upload-modal").attr("videoId", videoId);
    var internalFileName = $('#videoSession').attr("internalFileName");
    $("[name=key]").val(internalFileName);
    $('#internalFileName').val(internalFileName);
}


$(document).ready(function () {
    $('.thumbnail-direct-upload').each(function () {
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
                var thumbnailUploadedFileName = uploadedFile.name;
                $('#thumbnailUploadedFileName').val(thumbnailUploadedFileName);
                var thumbnailUploadedFileType = uploadedFile.type;
                $('#thumbnailUploadedFileType').val(thumbnailUploadedFileType);
                var thumbnailUploadedFileSize = uploadedFile.size;
                $('#thumbnailUploadedFileSize').val(thumbnailUploadedFileSize);
                // Fill the name field with the file's name. 
                $("#thumbnailId").val($("#thumbnail-upload-modal").attr("thumbnailId"));
                $("#thumbnailFilePurpose").val("thumbnailSubmission");

                var thumbnailUploadTickMark = "";
                thumbnailUploadTickMark += '<i class="icon fa fa-check on-submission-tickmark mr-1"></i>Thumbnail';
                document.getElementById("thumbnailSession").innerHTML = thumbnailUploadTickMark;
                $('#thumbnail-upload-modal').modal('hide');
            },
        });
    });

    $('#thumbnail-upload-modal').on('hidden.bs.modal', function () {
        $('.bar').css('width', '0');
    });

});

function getThumbnailInternalFileName() {
    var thumbnailId = $('#thumbnailSession').attr("sessionId");
    $("#thumbnail-upload-modal").attr("thumbnailId", thumbnailId);
    var thumbnailinternalFileName = $('#thumbnailSession').attr("internalFileName");
    $("[name=key]").val(thumbnailinternalFileName);
    $('#thumbnailinternalFileName').val(thumbnailinternalFileName);
}

$(document).ready(function () {
    $('.transcript-direct-upload').each(function () {
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
                var transcriptUploadedFileName = uploadedFile.name;
                $('#transcriptUploadedFileName').val(transcriptUploadedFileName);
                var transcriptUploadedFileType = uploadedFile.type;
                $('#transcriptUploadedFileType').val(transcriptUploadedFileType);
                var transcriptUploadedFileSize = uploadedFile.size;
                $('#transcriptUploadedFileSize').val(transcriptUploadedFileSize);
                // Fill the name field with the file's name. 
                $("#transcriptId").val($("#transcript-upload-modal").attr("transcriptId"));
                $("#transcriptFilePurpose").val("transcriptSubmission");
                var transcriptUploadTickMark = "";
                transcriptUploadTickMark += '<i class="icon fa fa-check on-submission-tickmark mr-1"></i>Transcript';
                document.getElementById("transcriptSession").innerHTML = transcriptUploadTickMark;
                $('#transcript-upload-modal').modal('hide');
            },
        });
    });

    $('#transcript-upload-modal').on('hidden.bs.modal', function () {
        $('.bar').css('width', '0');
    });
});

function getTranscriptInternalFileName() {
    var transcriptId = $('#transcriptSession').attr("sessionId");
    $("#transcript-upload-modal").attr("transcriptId", transcriptId);
    var transcriptinternalFileName = $('#transcriptSession').attr("internalFileName");
    $("[name=key]").val(transcriptinternalFileName);
    $('#transcriptinternalFileName').val(transcriptinternalFileName);
}