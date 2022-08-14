
function addExpandMessageCollapseIcon(rowDivId) {
    var session_id = $("#" + rowDivId).attr("session-id");
    var expandCollapseIconDiv = "message-expand-icon-" + session_id;
    var sessionDetailsDiv = "collapse-message" + session_id;

    if ($("#" + sessionDetailsDiv).hasClass("show")) {
        $("#" + expandCollapseIconDiv).addClass("fa fa-2x fa-angle-down");
    } else {
        $("#" + expandCollapseIconDiv).addClass("fa fa-2x fa-angle-right");
    }
}

function removeExpandMessageCollapseIcon(rowDivId) {
    var session_id = $("#" + rowDivId).attr("session-id");
    var expandCollapseIconDiv = "message-expand-icon-" + session_id;
    $("#" + expandCollapseIconDiv).removeAttr("class");
}

function messageHeadingRowClicked(rowDivId) {
    var session_id = $("#" + rowDivId).attr("session-id");
    var expandCollapseIconDiv = "message-expand-icon-" + session_id;
    var sessionDetailsDivId = "collapse-message" + session_id;

    if ($("#" + sessionDetailsDivId).hasClass("show")) {
        collapseSessionDetails(sessionDetailsDivId, rowDivId);
        $("#" + expandCollapseIconDiv).removeAttr("class").addClass("fa fa-2x fa-angle-right");
    } else {
        expandSessionDetails(sessionDetailsDivId, rowDivId)
        $("#" + expandCollapseIconDiv).removeAttr("class").addClass("fa fa-2x fa-angle-down");
    }
}

function collapseSessionDetails(sessionDetailsDivId, sessionHeadingRowDivId) {
    $("#" + sessionDetailsDivId).removeClass("show").addClass("hide");
    $("#" + sessionHeadingRowDivId).removeClass("expanded-session").addClass("collapsed-session");
    $("#" + sessionHeadingRowDivId).attr("title", "Click here to expand session details");
}

function expandSessionDetails(sessionDetailsDivId, sessionHeadingRowDivId) {
    $("#" + sessionDetailsDivId).removeClass("hide").addClass("show");
    $("#" + sessionHeadingRowDivId).removeClass("collapsed-session").addClass("expanded-session");
    $("#" + sessionHeadingRowDivId).attr("title", "Click here to collapse session details");
}

function facilitatormessageHeadingRowClicked(thread_id,message_id) {
       window.location.replace("/Message/view/" + thread_id);
}
function replyMessage() {
        $('.reply').removeClass("d-none").addClass("show");
}

function collapseReply() {
      $('.reply').removeClass("show").addClass("d-none");
  }

function sendReply() {
    var message_id = $('#message-id').attr('message_id');
    var message = quill.root.innerHTML;
    $.post("/Message/reply",
            {
                message_id: message_id,
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
                        $(".reply").addClass("d-none");
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

function FacilitatorComposeSection()
{
    window.location.replace("/Message/composeFacilitatorMessage");
}
function LearnerComposeSection()
{
    window.location.replace("/Message/composeLearnerMessage");
}


function UserDetailsOfProgram()
{   
    $('#hiddenSubject').removeClass('d-none');

    courseId = $('#programList').val();
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
                        $('#receipient-list').append("<option value= 'co_facilitators' >Co-Facilitators</option>");
                        }
                        facilitators.forEach((facilitator, i) => {
                            $('#receipient-list').append("<option  value= '" + facilitator.user_id + "' >" + facilitator.name + ' ' + facilitator.last_name + " </option>");
                        });
                    }

                    var participants = user_list.PARTICIPANT;
                    if (participants.length > 1){
                    $('#receipient-list').append("<option value= 'all_learners' >All Learners</option>");
                    }
                    participants.forEach((participant, i) => {
                        $('#receipient-list').append("<option value= '" + participant.user_id + "' >" + participant.name + ' ' + participant.last_name + "</option>");
                    });
                    $('#receipient-list').selectpicker();

                } else if (response.status == "Error") {
                    alert(response.message);
                } else {
                    alert("Sorry!\nAn error happened while trying to load Program list for the selected subject. Please try again")
                }
            }
    );

}

function sendFacilitatorMessage()
{
    var courseId = $('#programList').val();
    var to = $("#receipient-list").val();
    var subject = $("#Subject").val();
    var message = quill.root.innerHTML;
    $.post("/Message/newFacilitatorMessage",
            {
                to: to,
                subject: subject,
                message: message,
                courseId: courseId,
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
