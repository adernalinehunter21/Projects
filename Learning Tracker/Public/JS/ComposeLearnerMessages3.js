    var courseId = $('#course_id').attr('course_id');
$.post("/Course/usersOfTheCourse",
        {
            courseId: courseId
        },
        function (response) {
            response = JSON.parse(response);
            if (response.status == "Success") {
                var user_list = response.user_list;
                if (typeof (user_list.FACILITATOR) != 'undefined') {
                    var facilitators = user_list.FACILITATOR;
                    if (facilitators.length > 1){
                      $('#list').append("<option value= 'all_facilitators' >All Facilitators</option>");
                    }

                    facilitators.forEach((facilitator, i) => {
                      $('#list').append("<option value= '" + facilitator.user_id + "' >" + facilitator.name + ' ' + facilitator.last_name + "</option>");
                    });
                }

                var participants = user_list.PARTICIPANT;
                if (participants.length > 1){
                  $('#list').append("<option value= 'all_learners' >All Learners</option>");
                }
                participants.forEach((participant, i) => {
                    $('#list').append("<option value= '" + participant.user_id + "' >" + participant.name + ' ' + participant.last_name + "</option>");
                });
                $('#list').selectpicker('refresh');
            } else if (response.status == "Error") {
                alert(response.message);
            } else {
                alert("Sorry!\nAn error happened while trying to load Program list for the selected subject. Please try again")
            }
        }
);



function sendLearnerMessage()
{
    var to = $("#list").val();
    var subject = $("#subject").val();
    var message = quill.root.innerHTML;
    $.post("/Message/newLearnerMessage",
            {
                to: to,
                subject: subject,
                message: message,
                attachments : attachedFiles

            },
            function (response) {
                response = JSON.parse(response);
                if (response.status == "Success") {
                    swal({
                        title: "Done!",
                        text: "Your message has been logged and notified to receipients successfully",
                        icon: "success",
                    }).then(()=>{
                        window.location.replace("/Message/new");
                    });
                } else if (response.status == "Error") {
                    swal({
                        title: "Sorry!",
                        text: "Following error happened: " + response.message,
                        icon: "error",
                    });
                }
            });

}
function attachmentUploadComplete(uploadedFile){
    attachedFiles.push(uploadedFile);
    listAttachedFiles();

}

function listAttachedFiles(){
    uploadedFileList = document.getElementById('uploaded-file-list');
    uploadedFileList.innerHTML = "";
    attachedFiles.forEach(function(attachedFile, index){
        fileItem = document.createElement('li');
        uploadedFileList.appendChild(fileItem);
        fileItem.innerHTML = attachedFile.fileName  + '&nbsp' +'<i class="fa fa-times remove-attachment"  title = "Remove" onclick = "removeReference('+ index +')" aria-hidden="true"></i>';
    });

}

function removeReference(index){
    var attachedFile = attachedFiles[index];
    var internal_name  = attachedFile['internalFileName'];
    var file_name = attachedFile['fileName'];
    var uploaded_purpose = "MESSAGE_ATTACHMENT"
    $.post("/File/removeUnassociated",
    {
        internal_name: internal_name,
        file_name: file_name,
        uploaded_purpose: uploaded_purpose
        
    },
    function (response) {
        response = JSON.parse(response);
        if (response.status == "Success") {
            attachedFiles.splice(index, 1);
            listAttachedFiles();                       

        } else if (response.status == "Error") {
            swal({
                title: "Sorry!",
                text: "Couldn't remove attachment" ,
                icon: "error",
            });
        }
    });

}