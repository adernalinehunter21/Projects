quill = null;
function getReflection(reflectionId,userId) {
    console.log(reflectionId,userId);

    var reflectionTopic = document.getElementById("reflection-"+reflectionId).innerHTML;
    document.getElementById("reflectionTitle").innerHTML = reflectionTopic;
    console.log(reflectionTopic);
    var courseId = $('#programList').val();
    $.post("/Reflection/getReflectionDetails", {

            reflectionId: reflectionId,
            user_id: userId,
            courseId: courseId,

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
            reflectionFooter += '<button class="btn btn-light" data-dismiss="modal" id = "btn-close" type="button">Close</button>';
            document.getElementById("reflectionModalHeading").innerHTML = "Reflection";
            if (quill === null) {
                quill = startQuill('reflectionModalBody');
            }
            quill.root.innerHTML = reflectionModalBodyHtml;
            document.getElementById("reflection-footer").innerHTML = reflectionFooter;

        } else if (response.status == "Error") {
            alert("Sorry!<br>Foorowing error happened: " + response.message);
        } else {
            alert("Soory! <br>Received invalid response from server<br>We appologise and request you to report this to our technical team");
        }
    });
}


$('#reflectionModal').on('hidden.bs.modal', function () {

    document.getElementById("reflectionTitle").innerHTML = "";
    quill.root.innerHTML = " ";


});
