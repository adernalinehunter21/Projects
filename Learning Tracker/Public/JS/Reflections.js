function getReflection(reflectionId) {
    var reflectionTopic = document.getElementById("reflection-" + reflectionId).innerHTML;
    document.getElementById("reflectionTitle").innerHTML = reflectionTopic;

    $.post("/Reflection/get", {
        update: "Reflection",
        data: {
            reflectionId: reflectionId
        }
    },
            function (response) {
                response = JSON.parse(response);
                if (response.status == "Success") {
                    var reflectionModalBodyHtml = "";
                    var reflectionFooter = "";
                    if (response.reflectionDetails === null) {
                        response.reflectionDetails = "";
                    }
                    reflectionModalBodyHtml += response.reflectionDetails;
                    reflectionFooter += '<button class="btn btn-light" data-dismiss="modal" id = "btn-close" type="button">Close</button><button class="btn dasa-btn" type="submit" id = "reflectionModalSubmit" reflectionId="' + reflectionId + '" onclick="reflectionSubmitRequest()">Submit</button>';
                    document.getElementById("reflectionModalHeading").innerHTML = "Reflection";
                    quill.root.innerHTML = reflectionModalBodyHtml;
                    document.getElementById("reflection-footer").innerHTML = reflectionFooter;

                } else if (response.status == "Error") {
                    swal({
                        title: "Sorry!",
                        text: "Foorowing error happened: " + response.message,
                        icon: "error",
                    });
                } else {
                    swal({
                        title: "Sorry!",
                        text: "Received invalid response from server\nWe appologise and request you to report this to our technical team",
                        icon: "error",
                    });
                }
            });
}

function reflectionSubmitRequest() {

    var reflectionId = $("#reflectionModalSubmit").attr('reflectionId');
    var reflection = quill.root.innerHTML;
    $.post("/Reflection/update", {
        update: "submitReflection",
        data: {
            reflectionId: reflectionId,
            reflection: reflection
        }
    },
            function (response) {
                response = JSON.parse(response);
                if (response.status == "Success") {
                    var reflectionAnsweredStatus = response.answeredStatus;
                    reflectionTick = "";
                    swal({
                        title: "Done!",
                        text: "Saved the reflection",
                        icon: "success",
                    });

                    $('#reflectionModal').modal('hide');
                    $('body').removeClass().removeAttr('style');
                    $('.modal-backdrop').remove();
                    if (reflectionAnsweredStatus === "ANSWERED") {

                        $('#reflectionTickMark-' + reflectionId).removeAttr("hidden");
                    }
                } else if (response.status == "Error") {
                    swal({
                        title: "Sorry!",
                        text: "Foorowing error happened: " + response.message,
                        icon: "error",
                    });
                } else {
                    swal({
                        title: "Sorry!",
                        text: "Received invalid response from server\nWe appologise and request you to report this to our technical team",
                        icon: "error",
                    });
                }
            }
    );

}

$('#reflectionModal').on('hidden.bs.modal', function () {

    document.getElementById("reflectionTitle").innerHTML = "";
    quill.root.innerHTML = " ";


});
