/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function clearContentDiv(){
    $('#submittedAssignmentsSection').empty();
}

function loadModuleAndSubjectSubmittedAssignments(){
    var courseId = $('#programList').val();
    var subjectId = $('#subjectList').val();

    $.post("/Assignment/loadModuleAndSubjectSubmittedAssignments",{
        courseId: courseId,
        subjectId:subjectId
    },
    function(response) {

            $('#submittedAssignmentsSection').empty();

            response = JSON.parse(response);
            if (response['session_wise_assignment_data'].length == 0){
                $('#submittedAssignmentsSection').append(
                    '<div class="setup-card-1 maincontent" style="padding: 0px;text-align:center;">'+
                        '<section class="main-div">'+
                            '<h6>Soory! There are no Submitted Assignments.</h6>'+
                        '</section>'+
                    '</div>'
                );
            }

            if (response.status == "Success") {
                var participants_details = response.participants_details;
                var sessions = response.session_wise_assignment_data;
                if (sessions.length > 0) {

                    sessions.forEach(function(oneSession) {

                        var submittedParticipants = oneSession.participants;
                        if (submittedParticipants.length > 0) {

                            var sessionSectionDivId = "PROGRAM";
                            $('#submittedAssignmentsSection').append('<div id="' + sessionSectionDivId + '"></div>');


                            var sessionCardHtml = "";
                            submittedParticipants.forEach(function(oneParticipant) {
                                var participantAssignmentCardsHtml = "";
                                var submittedAssignments = oneParticipant.assignments;
                                submittedAssignments.forEach(function(assignment) {
                                    assignmentId = assignment.assignmentId;
                                    var submitted_files = assignment.submitted_files;
                                    var submittedFilesHtml = "";
                                    submitted_files.forEach(function(oneFile) {
                                        var fileName = oneFile.file_name;
                                        var filePath = oneFile.file_path;
                                        submittedFilesHtml += '<div class="Submitted-Documents" onclick="startDownload(\'' + filePath + '\')" data-toggle="tooltip" title="click to download the uploaded file">' +
                                            '  <i class="fa fa-download submitted-assignment-download-icon"></i>' +
                                            '  <span>' + fileName + '</span>' +
                                            '</div>';
                                    });
                                    var icon = 'fa-user';
                                    var toolTip = 'Submitted as an individual';
                                    if (assignment.submission_type == "TEAM") {
                                        icon = 'fa-users';
                                        toolTip = "In collaboration with";
                                        (assignment.otherTeamMembers).forEach(function(member) {
                                            toolTip += "\n" + member;
                                        });
                                    }

                                    icon_tick = ''
                                    if(assignment.submission_status === "ACCEPTED"){
                                        for(i=0; i < assignment.no_of_reviews-1; i++){
                                            icon_tick += '<i class="fas fa-check" style="color:#d76f5f;"></i>';
                                        }
                                        icon_tick += '<i class="fas fa-check" style="color:#60c8d7!important;"></i>';
                                    }
                                    else{
                                        for(i=0; i < assignment.no_of_reviews; i++){
                                            icon_tick += '<i class="fas fa-check" style="color:#d76f5f;"></i>';
                                        }
                                    }


                                    if(assignment.submission_status === "ACCEPTED"){
                                        reviewButton = '<button id="reviewButton'+assignment.assignmentId+'" submissionId = "'+assignment.submission_id+'" no_of_reviews="'+assignment.no_of_reviews+'" submission_status ="'+assignment.submission_status+'" review_max_score="'+assignment.review_max_score+'" class="btn btn-bg-lightgray flat-btn mt-3 ml-3 reviewButton"  data-toggle="modal" data-target="#assignment-file-upload-modal"   onclick="reviewAssignment('+ assignment.assignmentId +')">View</button>'
                                    }
                                    else{
                                        reviewButton ='<button id="reviewButton'+assignment.assignmentId+'" submissionId = "'+assignment.submission_id+'" no_of_reviews="'+assignment.no_of_reviews+'" submission_status ="'+assignment.submission_status+'" review_max_score="'+assignment.review_max_score+'" class="btn btn-bg-lightgray flat-btn mt-3 ml-3 reviewButton"  data-toggle="modal" data-target="#assignment-file-upload-modal"   onclick="reviewAssignment('+ assignment.assignmentId +')">Review</button>'
                                    }

                                        var oneAssignmentCard = '<div class="col-md-4 col-lg-4 small-size-col-margin mb-1">' +
                                            '  <div class="card border-light h-100 card-padding-dash shadow">' +
                                            '      <div class="card-header">' +
                                            '          <div class="row">' +
                                            '              <div class="col">' +
                                            '                  <p class="reviewTick" id="review_tick'+assignment.assignmentId+'">'+icon_tick+'</p>' +
                                            '              </div>' +
                                            '              <div class="col col-2">' +
                                            '                  <i class="fa ' + icon + ' assignment-upload-status-icon" data-toggle="tooltip" style="" title="' + toolTip + '"></i>' +
                                            '              </div>' +
                                            '          </div>' +
                                            '          <div class="row">' +
                                            '              <div class="col">' +
                                            '                  <p class="card-title">' + assignment.assignment_name + '</p>' +
                                            '              </div>' +
                                            '          </div>' +
                                            '      </div>' +

                                            '      <div class="card-body submission-card-body" id="submittedAssignmentBody'+assignment.assignmentId+'" thread_id="'+assignment.assignmentMessageThreadId+'" learnersUserId = "'+assignment.learnersUserId+'">' +
                                            assignment.submitted_description  +
                                            '      </div>' +

                                            '      <div class="card-footer">' +
                                            '      <div class="row">' +
                                            '              <div class="col-8">' +
                                                            submittedFilesHtml +
                                            '              </div>' +
                                            '              <div class="col-4">' +
                                                            reviewButton +
                                            '              </div>' +
                                            '      </div>' +
                                            '      </div>' +
                                            '  </div>' +
                                            '</div>';
                                    participantAssignmentCardsHtml += oneAssignmentCard;

                                });

                                var participantPhotoDiv = '';
                                participants_details.forEach(function(p) {
                                    if (p.id == oneParticipant.id) {
                                        if (p.profile_pic_binary != null) {
                                            participantPhotoDiv = '<img src="' + p.profile_pic_binary + '" width="100" alt="" class="img-fluid rounded-circle">';
                                        } else {
                                            participantPhotoDiv = '<img src="https://learning-tracker-public-files.s3-ap-southeast-1.amazonaws.com/general/avatar.jpg" width="100" alt="" class="img-fluid rounded-circle">';
                                        }

                                    }
                                });

                                var participantCardHtml = '<div m-1>' +
                                    '  <div class="row no-gutters participant-row">' +
                                    '      <div class="col col-2">' +
                                    '          <div class="participant-avatar-container">' +
                                    '              <div style="display: block">' +
                                    participantPhotoDiv +
                                    '              <p>' + oneParticipant.name + '</p>' +
                                    '              </div>' +
                                    '          </div>' +
                                    '      </div>' +
                                    '      <div class="col col-10">' +
                                    '          <div class="row no-gutters" id="assignmentCards">' +
                                    participantAssignmentCardsHtml +
                                    '          </div>' +
                                    '      </div>' +
                                    '  </div>' +
                                    '</div>';

                                sessionCardHtml += participantCardHtml;
                            });

                            var sessionCardDivId = "session-card-" + oneSession.session_id;
                            var sessionHtml = '<div class="session-card-heading">' +
                                'PROGRAM '+
                                '</div>' +
                                '<div id="' + sessionCardDivId + '" class="card session-card">' +
                                sessionCardHtml +
                                '</div>';
                            document.getElementById(sessionSectionDivId).innerHTML = sessionHtml;


                        }

                    });

                }

            } else if (response.status == "Error") {
                alert("Sorry!\nFoorowing error happened while loading submitted assignments: " + response.message);
            } else {
                alert("Soory!\nReceived invalid response from server\nWe appologise and request you to report this to our technical team");
            }



            loadSubmittedAssignments();
    });

}


function loadSubmittedAssignments() {
    var courseId = $('#programList').val();
    var subjectId = $('#subjectList').val();
    $.post("/Assignment/getSubmittedAssignments", {
            courseId: courseId,
            subjectId:subjectId
        },
        function(response) {

            response = JSON.parse(response);
            if (response['session_wise_assignment_data'].length == 0){
                $('#submittedAssignmentsSection').append(
                    '<div class="setup-card-1 maincontent" style="padding: 0px;text-align:center;">'+
                        '<section class="main-div">'+
                            '<h6>Soory! There are no Submitted Assignments.</h6>'+
                        '</section>'+
                    '</div>'
                );
            }

            if (response.status == "Success") {
                var participants_details = response.participants_details;
                var sessions = response.session_wise_assignment_data;
                if (sessions.length > 0) {

                    sessions.forEach(function(oneSession) {

                        var submittedParticipants = oneSession.participants;
                        if (submittedParticipants.length > 0) {

                            var sessionSectionDivId = "session-" + oneSession.session_id;
                            $('#submittedAssignmentsSection').append('<div id="' + sessionSectionDivId + '"></div>');


                            var sessionCardHtml = "";
                            submittedParticipants.forEach(function(oneParticipant) {
                                var participantAssignmentCardsHtml = "";
                                var submittedAssignments = oneParticipant.assignments;
                                submittedAssignments.forEach(function(assignment) {
                                    assignmentId = assignment.assignmentId;
                                    var submitted_files = assignment.submitted_files;
                                    var submittedFilesHtml = "";
                                    submitted_files.forEach(function(oneFile) {
                                        var fileName = oneFile.file_name;
                                        var filePath = oneFile.file_path;
                                        submittedFilesHtml += '<div class="Submitted-Documents" onclick="startDownload(\'' + filePath + '\')" data-toggle="tooltip" title="click to download the uploaded file">' +
                                            '  <i class="fa fa-download submitted-assignment-download-icon"></i>' +
                                            '  <span>' + fileName + '</span>' +
                                            '</div>';
                                    });
                                    var icon = 'fa-user';
                                    var toolTip = 'Submitted as an individual';
                                    if (assignment.submission_type == "TEAM") {
                                        icon = 'fa-users';
                                        toolTip = "In collaboration with";
                                        (assignment.otherTeamMembers).forEach(function(member) {
                                            toolTip += "\n" + member;
                                        });
                                    }

                                    icon_tick = ''
                                    if(assignment.submission_status === "ACCEPTED"){
                                        for(i=0; i < assignment.no_of_reviews-1; i++){
                                            icon_tick += '<i class="fas fa-check" style="color:#d76f5f;"></i>';
                                        }
                                        icon_tick += '<i class="fas fa-check" style="color:#60c8d7!important;"></i>';
                                    }
                                    else{
                                        for(i=0; i < assignment.no_of_reviews; i++){
                                            icon_tick += '<i class="fas fa-check" style="color:#d76f5f;"></i>';
                                        }
                                    }

                                    if(assignment.submission_status === "ACCEPTED"){
                                        reviewButton = '<button id="reviewButton'+assignment.assignmentId+'" submissionId = "'+assignment.submission_id+'" no_of_reviews="'+assignment.no_of_reviews+'" submission_status ="'+assignment.submission_status+'" review_max_score="'+assignment.review_max_score+'" class="btn btn-bg-lightgray flat-btn mt-3 ml-3 reviewButton"  data-toggle="modal" data-target="#assignment-file-upload-modal"   onclick="reviewAssignment('+ assignment.assignmentId +')">View</button>'
                                    }
                                    else{
                                        reviewButton ='<button id="reviewButton'+assignment.assignmentId+'" submissionId = "'+assignment.submission_id+'" no_of_reviews="'+assignment.no_of_reviews+'" submission_status ="'+assignment.submission_status+'" review_max_score="'+assignment.review_max_score+'" class="btn btn-bg-lightgray flat-btn mt-3 ml-3 reviewButton"  data-toggle="modal" data-target="#assignment-file-upload-modal"   onclick="reviewAssignment('+ assignment.assignmentId +')">Review</button>'
                                    }

                                    var oneAssignmentCard = '<div class="col-md-4 col-lg-4 small-size-col-margin mb-1">' +
                                        '  <div class="card border-light h-100 card-padding-dash shadow">' +
                                        '      <div class="card-header">' +
                                        '          <div class="row">' +
                                        '              <div class="col">' +
                                        '                  <p class="reviewTick" id="review_tick'+assignment.assignmentId+'">'+icon_tick+'</p>' +
                                        '              </div>' +
                                        '              <div class="col col-1">' +
                                        '                  <i class="fa ' + icon + ' assignment-upload-status-icon" data-toggle="tooltip" title="' + toolTip + '"></i>' +
                                        '              </div>' +
                                        '          </div>' +
                                        '          <div class="row">' +
                                        '              <div class="col">' +
                                        '                  <p class="card-title">' + assignment.assignment_name + '</p>' +
                                        '              </div>' +
                                        '          </div>' +
                                        '      </div>' +
                                        '      <div class="card-body" id="submittedAssignmentBody'+assignment.assignmentId+'" thread_id="'+assignment.assignmentMessageThreadId+'" learnersUserId = "'+assignment.learnersUserId+'" >' +
                                        assignment.submitted_description  +
                                        '      </div>' +
                                        '      <div class="card-footer">' +
                                        '      <div class="row">' +
                                        '              <div class="col-8">' +
                                                        submittedFilesHtml +
                                        '              </div>' +
                                        '              <div class="col-4">' +
                                                        reviewButton +
                                        '              </div>' +
                                        '      </div>' +
                                        '      </div>' +
                                        '  </div>' +
                                        '</div>';
                                    participantAssignmentCardsHtml += oneAssignmentCard;
                                });

                                var participantPhotoDiv = '';
                                participants_details.forEach(function(p) {
                                    if (p.id == oneParticipant.id) {
                                        if (p.profile_pic_binary != null) {
                                            participantPhotoDiv = '<img src="' + p.profile_pic_binary + '" width="100" alt="" class="img-fluid rounded-circle">';
                                        } else {
                                            participantPhotoDiv = '<img src="https://learning-tracker-public-files.s3-ap-southeast-1.amazonaws.com/general/avatar.jpg" width="100" alt="" class="img-fluid rounded-circle">';
                                        }

                                    }
                                });

                                var participantCardHtml = '<div m-1>' +
                                    '  <div class="row no-gutters participant-row">' +
                                    '      <div class="col col-2">' +
                                    '          <div class="participant-avatar-container">' +
                                    '              <div style="display: block">' +
                                    participantPhotoDiv +
                                    '              <p>' + oneParticipant.name + '</p>' +
                                    '              </div>' +
                                    '          </div>' +
                                    '      </div>' +
                                    '      <div class="col col-10">' +
                                    '          <div class="row no-gutters">' +
                                    participantAssignmentCardsHtml +
                                    '          </div>' +
                                    '      </div>' +
                                    '  </div>' +
                                    '</div>';

                                sessionCardHtml += participantCardHtml;
                            });

                            var sessionCardDivId = "session-card-" + oneSession.session_id;
                            var sessionHtml = '<div class="session-card-heading">' +
                                'Session ' + oneSession.session_index + ': ' + oneSession.session_name +
                                '</div>' +
                                '<div id="' + sessionCardDivId + '" class="card session-card">' +
                                sessionCardHtml +
                                '</div>';
                            document.getElementById(sessionSectionDivId).innerHTML = sessionHtml;

                        }

                    });

                }



            } else if (response.status == "Error") {
                alert("Sorry!\nFoorowing error happened while loading submitted assignments: " + response.message);
            } else {
                alert("Soory!\nReceived invalid response from server\nWe appologise and request you to report this to our technical team");
            }
        }
    );
}



function startDownload(url) {
    window.location.assign(url);
}


var resourceData = null; //default there no resource data
var s3Details4fileUpload;
var resourceFilePrefix;
var resourceThumbnailPrefix;
var uploadedReferenceFile = [];

function loadSubjectAssignments() {
    var subjectId = $('#subjectList').val();
    if(subjectId !== ""){
        $.post("/Assignment/assignmentsOfTheSubject", {
                subjectId: subjectId
            },
            function(response) {
                resetUploadOption();
                uploadedReferenceFile = [];
                quill = startQuill('description');
                $('#upload-section').removeClass('d-none');
                $('#resource-for option:selected').removeAttr('selected');
                $("#resource-for").val("Subject");
                selectedResourceFor();

                var response = JSON.parse(response);
                resourceData = response['resourceData'];
                s3Details4fileUpload = response['s3Details4fileUpload'];
                resourceFilePrefix = response['resourceFilePrefix'];

                subject_assignments = response['subject_assignments'];
                var formattedData = formatData(subject_assignments);
                displayResorces(formattedData);
            }
        );
    }

}

function formatData(data) {
    var values = {};
    data.forEach(d => {
        var group;
        var groupIndex;

        var oneValue = {
            id: d.id,
            name: d.name,
            description: d.description,
            module_index: d.module_index,
            module_name: d.module_name,
            topic_module_index: d.topic_module_index,
            topic_module_name: d.topic_module_name,
            reference: d.reference,
        };
        if (d.associated_to == "ENTIRE_SUBJECT") {
            group = 'Entire Subject';
            groupIndex = 0;
        } else if (d.associated_to == "MODULE") {
            group = 'Module ' + d.module_index + ": " + d.module_name;
            groupIndex = parseInt(d.module_index) + 1;
        } else if (d.associated_to == "TOPIC") {
            group = 'Module ' + d.topic_module_index + ': ' + d.topic_module_name;
            groupIndex = parseInt(d.topic_module_index) + 1;

        } else {
            group = 'WTF';
            groupIndex = 2000;
        }
        if (typeof values[groupIndex] === 'undefined') {
            values[groupIndex] = {
                group: group,
                resources: []
            };
        }

        values[groupIndex]['resources'].push(oneValue);
    })
    return values;
}

function displayResorces(data) {
    $('#submittedAssignmentsSection').html("");
    for (var groupId in data) {
        var resources = data[groupId].resources;
        var assignment_html = "";
        resources.forEach(oneAssignment => {

            var reference_div = "";

            oneAssignment.reference.forEach(function(oneReference, reference_index) {
                reference_div +=
                    '                    <div class="file-name"><i class="far fa-file mr-1"></i>' +
                    '                          <a role="presentation" href="' + oneReference.document_link + '" target="blank">' + oneReference.document_name + '</a>' +
                    '                      </div>';

            });

            resources.forEach(oneAssignment=>{
                if ((oneAssignment.reference).length > 0) {
                    assignmentFooter = '      <div class="card-footer" style="background-color:rgba(0,0,0,.03)!important;">' +
                        '          <div class="row no-gutters">' +
                        '              <div  class="col ">' +
                        '                  <div class="card-footer-title text-left">Reference Documents</div>' +
                        reference_div +
                        '                   </div>' +
                        '          </div>' +
                        '      </div>'
                } else {
                    assignmentFooter = ""
                }

            });

            var oneAssignmentCard = '<div class="col-sm-12 col-md-6 col-lg-6 text-left" style="margin-bottom:10px;">' +
                '  <div class="card h-100 Asssignment-card box-shadow">' +
                '      <div class="card-header"  style="background-color:rgba(0,0,0,.03)!important;">' +
                '          <div class="row no-gutters">' +
                '              <div class="col-11">' +
                '                  <p class="card-title" style="font-size:15px;" id="'+oneAssignment.id+'">' + oneAssignment.name + '</p>' +
                '              </div>' +
                '              <div class="col-1">' +
                '                  <i class="fas fa-trash" style="margin-left:20px;" id="trash' + oneAssignment.id + '" onclick= "deleteAssignment(' + oneAssignment.id + ')"></i>' +
                '              </div>' +
                '          </div>' +
                '      </div>' +
                '      <div class="card-body card-text assignment-paragraph">' +
                '           <div>' +
                '              <p>' + oneAssignment.description + '</p>' +
                '            </div>' +
                '      </div>' +
                assignmentFooter +
                '  </div>' +
                '</div>';
            assignment_html += oneAssignmentCard;
        });

        var sectionDivId = "section-" + groupId;

        $('#submittedAssignmentsSection').append('<div id="' + sectionDivId + '" ><div class="row no-gutters resource-content-row"></div></div>');

        var sectionCardDivId = "section-card-" + groupId;
        var sessionHtml = '<div class="session-card-heading">' +
            data[groupId].group +
            '</div>' +
            '<div id="' + sectionCardDivId + '" class="row session-card" style="margin-left:0px;background:#fff">' +
            assignment_html +
            ' </div>';

        document.getElementById(sectionDivId).innerHTML = sessionHtml;
    }
}

i = 0;

function addReference() {
    section = document.getElementById('addReferenceBox');

    section_card = document.createElement('div');
    section_card.setAttribute("id", "addReference" + i);
    section_card.setAttribute("reference_index", i);
    section_card.setAttribute('class', 'row no-gutters mt-4 card reference-card');
    section.appendChild(section_card);

    first_row = document.createElement('div');
    first_row.setAttribute('class', 'row no-gutters p-3 flex-column-reverse flex-md-row');
    section_card.appendChild(first_row);

    schedules_col = document.createElement('div');
    schedules_col.setAttribute('class', 'col-xs-12 col-sm-11');
    first_row.appendChild(schedules_col);

    delete_icon_col = document.createElement('div');
    delete_icon_col.setAttribute('class', 'col-xs-12 col-sm-1');
    first_row.appendChild(delete_icon_col);

    delete_icon = document.createElement('i');
    delete_icon.setAttribute('class', 'far fa-trash-alt delete-icon');
    delete_icon.setAttribute('onclick', 'deleteReference(' + i + ')');
    delete_icon.setAttribute('style', 'float: right');
    delete_icon_col.appendChild(delete_icon);

    schedule_row = document.createElement('div');
    schedule_row.setAttribute('class', 'row no-gutters');
    schedules_col.appendChild(schedule_row);

    date_col = document.createElement('div');
    date_col.setAttribute('class', 'col-sm-6 col-md-3 px-1');
    schedule_row.appendChild(date_col);

    label_for_name = document.createElement('label');
    label_for_name.setAttribute('for', 'resource-name' + i);
    label_for_name.innerHTML = "Reference Name";
    date_col.appendChild(label_for_name);

    reference_name = document.createElement('input');
    reference_name.setAttribute('id', 'resource-name' + i);
    reference_name.setAttribute('type', 'text');
    reference_name.setAttribute('class', 'form-control-sm general-select-in-body select-border');
    date_col.appendChild(reference_name);

    reference_type_col = document.createElement('div');
    reference_type_col.setAttribute('class', 'col-sm-6 col-md-3 px-1');
    schedule_row.appendChild(reference_type_col);

    label_for_Type = document.createElement('label');
    label_for_Type.setAttribute('for', 'resource-form' + i);
    label_for_Type.innerHTML = "Type";
    reference_type_col.appendChild(label_for_Type);

    type_dropdown = document.createElement('select');
    type_dropdown.setAttribute('class', 'form-control-sm general-select-in-body select-border');
    type_dropdown.setAttribute('id', 'resource-form' + i);
    type_dropdown.setAttribute('onchange', "selectedResourceForm(" + i + ")");
    type_1 = document.createElement('option');
    type_1.setAttribute('value', 'file-upload');
    type_1.innerHTML = "upload The File";
    type_2 = document.createElement('option');
    type_2.setAttribute('value', 'external-link');
    type_2.innerHTML = "Enter External link";
    type_dropdown.appendChild(type_1);
    type_dropdown.appendChild(type_2);
    reference_type_col.appendChild(type_dropdown);

    resource_file_col = document.createElement('div');
    resource_file_col.setAttribute('class', "col-sm-4 col-md-3 px-1");
    resource_file_col.setAttribute('id', "resource-file-col" + i);
    schedule_row.appendChild(resource_file_col);

    label_for_resource_file = document.createElement('label');
    label_for_resource_file.setAttribute('for', 'resource-file' + i);
    label_for_resource_file.innerHTML = "File";
    resource_file_col.appendChild(label_for_resource_file);

    upload_button = document.createElement('button');
    upload_button.setAttribute('id', "resource-file" + i);
    upload_button.setAttribute('class', "btn border rounded file-upload-btn");
    var resource_file = 'resource-file' + i;
    upload_button.setAttribute('onclick', "initiateFileUploadModal(\'" + resource_file + "\',s3Details4fileUpload,resourceFilePrefix,uploadedReferenceFile," + i + ")");
    button_text = document.createElement('i');
    button_text.setAttribute('class', 'fas fa-upload');
    button_text.setAttribute('style', 'margin-left:5px;');
    button_text.setAttribute('aria-hidden', 'true');
    upload_button.innerHTML = 'Upload';
    upload_button.appendChild(button_text);
    resource_file_col.appendChild(upload_button);

    resource_link_col = document.createElement('div');
    resource_link_col.setAttribute('class', "col-sm-4 col-md-3 px-1 d-none");
    resource_link_col.setAttribute('id', "resource-link-col" + i);
    schedule_row.appendChild(resource_link_col);

    label_for_resource_link = document.createElement('label');
    label_for_resource_link.setAttribute('for', 'resource-link' + i);
    label_for_resource_link.innerHTML = "Link";
    resource_link_col.appendChild(label_for_resource_link);

    link_name = document.createElement('input');
    link_name.setAttribute('id', 'resource-link' + i);
    link_name.setAttribute('type', 'url');
    link_name.setAttribute('class', 'form-control-sm general-select-in-body select-border');
    resource_link_col.appendChild(link_name);

    //Second row

    second_row = document.createElement('div');
    second_row.setAttribute('class', 'row no-gutters p-3 flex-column-reverse flex-md-row');
    section_card.appendChild(second_row);

    schedules_col = document.createElement('div');
    schedules_col.setAttribute('class', 'col-xs-12 col-sm-11');
    second_row.appendChild(schedules_col);

    schedule_row = document.createElement('div');
    schedule_row.setAttribute('class', 'row no-gutters');
    schedules_col.appendChild(schedule_row);

    category_col = document.createElement('div');
    category_col.setAttribute('class', 'col-sm-6 col-md-3 px-1');
    schedule_row.appendChild(category_col);

    label_for_category = document.createElement('label');
    label_for_category.setAttribute('for', 'resource-name' + i);
    label_for_category.innerHTML = "Reference Category";
    category_col.appendChild(label_for_category);

    category_dropdown = document.createElement('select');
    category_dropdown.setAttribute('class', 'form-control-sm general-select-in-body select-border');
    category_dropdown.setAttribute('id', 'resource-type' + i);
    category_dropdown.setAttribute('onchange', "selectedResourceType(" + i + ")");
    resourceTypeList.forEach(d => {
        category_1 = document.createElement('option');
        category_1.setAttribute('value', d.type);
        category_1.innerHTML = d.type;
        category_dropdown.appendChild(category_1);
    })
    category_1 = document.createElement('option');
    category_1.setAttribute('value', 'addNew' + i);
    category_1.innerHTML = "Add New";
    category_dropdown.appendChild(category_1);
    category_col.appendChild(category_dropdown);

    //New category

    addNew_col = document.createElement('div');
    addNew_col.setAttribute('class', 'col-sm-6 col-md-3 px-1 d-none');
    addNew_col.setAttribute('id', 'resource-type-new-col' + i);
    schedule_row.appendChild(addNew_col);

    label_for_newCategory = document.createElement('label');
    label_for_newCategory.setAttribute('for', 'resource-type-new' + i);
    label_for_newCategory.innerHTML = "New Category";
    addNew_col.appendChild(label_for_newCategory);

    category_name = document.createElement('input');
    category_name.setAttribute('id', "resource-type-new" + i);
    category_name.setAttribute('type', 'text');
    category_name.setAttribute('class', 'form-control-sm general-select-in-body select-border');
    addNew_col.appendChild(category_name);


    //IconPicker

    icon_col = document.createElement('div');
    icon_col.setAttribute('class', 'col-sm-6 col-md-3 px-1');
    icon_col.setAttribute('id', 'thumnail-icon-col' + i);
    schedule_row.appendChild(icon_col);

    label_for_icon = document.createElement('label');
    label_for_icon.setAttribute('for', 'GetIconPicker' + i);
    label_for_icon.innerHTML = "Icon";
    icon_col.appendChild(label_for_icon);

    icon_button = document.createElement('button');
    icon_button.setAttribute('id', "GetIconPicker" + i);
    icon_button.setAttribute('type', "button");
    icon_button.setAttribute('data-iconpicker-input', 'input#IconInput' + i);
    icon_button.setAttribute('data-iconpicker-preview', 'i#IconPreview' + i);
    icon_button.setAttribute('class', "btn border rounded file-upload-btn");
    icon_span = document.createElement('span');
    icon_span.setAttribute('class', 'icon-picker-btn-content');
    icon_i_element = document.createElement('i');
    icon_i_element.setAttribute('id', 'IconPreview' + i);
    icon_label = document.createElement('label');
    icon_label.setAttribute('id', 'icon-pick-btn-text' + i);
    icon_label.innerHTML = 'Pick';

    icon_span.appendChild(icon_i_element);
    icon_span.appendChild(icon_label);
    icon_button.appendChild(icon_span);

    icon_text = document.createElement('input');
    icon_text.setAttribute('class', 'd-none');
    icon_text.setAttribute('type', 'text');
    icon_text.setAttribute('id', 'IconInput' + i);
    icon_text.setAttribute('name', 'Icon');
    icon_text.setAttribute('required', "");
    icon_text.setAttribute('autocomplete', 'off');
    icon_text.setAttribute('spellcheck', 'false');

    icon_col.appendChild(icon_button);
    icon_col.appendChild(icon_text);

    IconPicker.Init({
        // Required: You have to set the path of IconPicker JSON file to "jsonUrl" option. e.g. '/content/plugins/IconPicker/dist/iconpicker-1.5.0.json'
        jsonUrl: '../../json/iconpicker-1.5.0.json',
        // Optional: Change the buttons or search placeholder text according to the language.
        searchPlaceholder: 'Search Icon',
        showAllButton: 'Show All',
        cancelButton: 'Cancel',
        noResultsFound: 'No results found.', // v1.5.0 and the next versions
        borderRadius: '20px', // v1.5.0 and the next versions
    });

    IconPicker.Run('#GetIconPicker' + i, function(argument) {
        $('#icon-pick-btn-text' + i).html("");
    });

    i += 1;

}



//new section


function selectedResourceFor() {
    var courseId = $('#programList').val();
    var resource_for = $('#resource-for').val();
    if (resource_for === "Session") {
        $('#select-module-dropdown').addClass('d-none');
        $('#select-topic-dropdown').addClass('d-none');
        $('#select-session-dropdown').removeClass('d-none');
        $('#select-session').html("<option>Loading...</option>");

        loadSessionsDropdown(courseId);
    } else if (resource_for === "Module") {
        var subjectId = $('#subjectList').val();
        $('#select-session-dropdown').addClass('d-none');
        $('#select-topic-dropdown').addClass('d-none');
        $('#select-module-dropdown').removeClass('d-none');

        $('#select-module').html("<option>Loading...</option>");

        loadModulesDropdown(subjectId);
    } else if (resource_for === "Topic") {
        var subjectId = $('#subjectList').val();
        $('#select-session-dropdown').addClass('d-none');
        $('#select-module-dropdown').removeClass('d-none');
        $('#select-topic-dropdown').removeClass('d-none');

        $('#select-module').html("<option>Loading...</option>");

        loadModulesDropdown(subjectId)
    } else { //Program
        $('#select-module').html("");
        $('#select-topic').html("");
        $('#select-session').html("");
        $('#select-module-dropdown').addClass('d-none');
        $('#select-topic-dropdown').addClass('d-none');
        $('#select-session-dropdown').addClass('d-none');
    }
}

function loadModulesDropdown(subjectId) {
    $.post("/Module/getModulesOfTheSubject", {
            data: {
                subject_id: subjectId
            }
        },
        function(response) {
            var modules = JSON.parse(response);
            var modulesHtml = "";
            var firstModuleId = null;
            modules.forEach(function(module) {
                if (firstModuleId === null) {
                    firstModuleId = module.module_id;
                }
                modulesHtml += '<option value="' + module.module_id + '">' + module.module_index + '. ' + module.module_name + '</option>';
            });
            $('#select-module').html(modulesHtml);
            loadTopicsDropdown(firstModuleId);
        }
    );
}

function selectedModule() {
    var resource_for = $('#resource-for').val();
    if (resource_for === "Topic") {
        var moduleId = $('#select-module').val();
        loadTopicsDropdown(moduleId);
    }
}

function loadTopicsDropdown(moduleId) {
    $('#select-topic').html("<option>Loading...</option>");
    $.post("/Topic/getTopicsOfTheModule", {
            data: {
                module_id: moduleId
            }
        },
        function(response) {
            var topics = JSON.parse(response);
            var topicsHtml = "";
            topics.forEach(function(topic) {
                topicsHtml += '<option value="' + topic.id + '">' + topic.order + '. ' + topic.name + '</option>';
            });
            $('#select-topic').html(topicsHtml);
        }
    );
}

function selectedResourceForm(i) {

    var formOfTheResource = $('#resource-form' + i).val();
    if (formOfTheResource === "file-upload") {
        $('#resource-link-col' + i).addClass('d-none');
        $('#resource-file-col' + i).removeClass('d-none');
    } else if (formOfTheResource === "external-link") {
        $('#resource-file-col' + i).addClass('d-none');
        $('#resource-link-col' + i).removeClass('d-none');
        $('#resource-link' + i).val("");
    }

}

function submitNewAssignment() {
    result = validateFields();
    if (result.status == "Error") {
        swal({
            title: "Sorry!",
            text: result.message,
            icon: "error",
        });
    } else {

        data = result.data;
        if(data.send_notification === true){
        var message_to_user = "Once submitted, Assignment will be created and all learners of the course will be notified";
        }
        if(data.send_notification === false){
            message_to_user = "Once submitted, the learners will not be notified of the new Assignment.\n\nYou will have to notify the learners later.";
        }

        swal({
            title: "Are you sure?",
            text: message_to_user,
            icon: "info",
            buttons: true,
            dangerMode: true,
        })
        .then((update)=>{
            if(update){
                $.post("/Assignment/add", {
                        data: result.data,
                        notify_user_flag: data.send_notification
                    },
                    function(response) {
                        response = JSON.parse(response);
                        if (response.status === "Success") {
                            swal({
                                title: "Done!",
                                text: "Assignment Created successfully",
                                icon: "success",
                            });
                            resetUploadOption();
                            loadSubjectAssignments();
                            quill.setContents([]);
                        } else {
                            swal({
                                title: "Sorry!",
                                text: response.message,
                                icon: "error",
                            });
                        }
                    }
                );
            }
        })


    }
}

function getAssignmentFor() {
    var resource_for = $('#resource-for').val();
    var possibleValues = ['Subject', 'Module', 'Topic'];
    if (!possibleValues.includes(resource_for)) {
        return {
            status: "Error",
            message: "Invalid Resource for selection"
        };
    }
    // var association = {
    //     associated_to: resource_for
    // };
    if (resource_for === "Subject") {
        return {
            status: "Success",
            associated_to: resource_for,
            associated_module_id: null,
            associated_topic_id: null
        };
    } else if (resource_for === "Module") {
        var module_id = $('#select-module').val();
        if (isNumeric(module_id)) {
            // association['associated_module_id'] = module_id;
            return {
                status: "Success",
                associated_to: resource_for,
                associated_module_id: module_id,
                associated_topic_id: null
            };
        } else {
            return {
                status: "Error",
                message: "Invalid Module"
            };
        }
    } else {
        var module_id = $('#select-module').val();
        if (!isNumeric(module_id)) {
            return {
                status: "Error",
                message: "Invalid Module"
            };
        }
        var topic_id = $('#select-topic').val();
        if (isNumeric(topic_id)) {
            // association['associated_module_id'] = module_id;
            // association['associated_topic_id'] = topic_id;
            return {
                status: "Success",
                associated_to: resource_for,
                associated_module_id: module_id,
                associated_topic_id: topic_id
            };
        } else {
            return {
                status: "Error",
                message: "Invalid Topic"
            };
        }
    }
}

function isNumeric(value) {
    if (typeof value === "number") {
        return true;
    } else if (typeof value === "string") {
        if (parseInt(value) > 0) {
            return true;
        }
    }
    return false;
}

function getResourceDetails() {
    reference_details = [];
    error_flag = false;
    error = null;
    $('#addReferenceBox').children('.row').each(function(j, row_div) {
        reference_index = $(row_div).attr('reference_index');
        var referenceName = $('#resource-name' + reference_index).val();
        if (referenceName === "") {
            error_flag = true;
            error = {
                reference_index: reference_index,
                param: "Name"
            };
            return;
        }
        var type = $('#resource-type' + reference_index).val();
        if (type === "addNew" + reference_index) {
            type = $('#resource-type-new' + reference_index).val();
        }
        if (typeof type !== "string") {
            error_flag = true;
            error = {
                reference_index: reference_index,
                param: "Not a String"
            };
            return;
        }
        type = type.trim();
        if (type === "") {
            error_flag = true;
            error = {
                reference_index: reference_index,
                param: "Invalid"
            };
            return;
        }

        var icon = $('#IconInput' + reference_index).val();
        icon = icon.trim();
        if (icon === "" || icon === "Pick") {
            error_flag = true;
            error = {
                reference_index: reference_index,
                param: "Choose icon"
            };
            return;
        }
        var formOfTheResource = $('#resource-form' + reference_index).val();
        if (formOfTheResource === "file-upload") {
            if (uploadedReferenceFile.length <= 0) {
                error_flag = true;
                error = {
                    reference_index: reference_index,
                    param: "File"
                };
                return;
            }
            reference_details[j] = {

                name: referenceName,
                form: "File",
                category: type,
                icon: icon,
                uploadedFileDetails: uploadedReferenceFile[reference_index]
                // uploadedReferenceFiles: uploadedReferenceFile

            };
        } else if (formOfTheResource === "external-link") {
            var resourceLink = $('#resource-link' + reference_index).val();
            if (typeof resourceLink !== "string") {
                error_flag = true;
                error = {
                    reference_index: reference_index,
                    param: "Link"
                };
                return;
            }
            resourceLink = resourceLink.trim();
            if (resourceLink === "") {
                error_flag = true;
                error = {
                    reference_index: reference_index,
                    param: "Link"
                };
                return;
            }
            reference_details[j] = {

                name: referenceName,
                form: "Link",
                category: type,
                icon: icon,
                resourceLink: resourceLink

            };
        } else {
            error_flag = true;
            error = {
                reference_index: reference_index,
                param: "Type"
            };
            return;
        }

    });
    if (error_flag) {
        return {
            status: 'Error',
            error: 'Invalid ' + error.param
        };
    }
    return {
        status: "Success",
        value: reference_details
    }


}

function resetUploadOption() {

    $('#resource-name').val("");
    $('#assignment-name').val("");
    $('#description').val("");
    uploadedReferenceFile = [];
    for (x = 0; x < i; x++) {
        $('#addReference' + x).remove();
        $('#IconInput' + x).val("");
        $('#GetIconPicker' + x).html('<span class="icon-picker-btn-content"><i id="IconPreview' + x + '"></i><label id="icon-pick-btn-text' + x + '">Pick</label></span>');

    }
    i = 0;
    $('#resource-for').val("Subject");
    $('#resource-type-new').val("");
    $('#resource-form').val("file-upload");
    $('#resource-file').html('<i class="fa fa-upload" aria-hidden="true"></i> Upload');
    $('#resource-link').val("");
    $('#submission_max_score').val("");
    $('#review_max_score').val("");
    $('input[name="inlineRadioOptions"]').prop('checked', false);
    $('#resource-submit-btn').removeAttr('disabled');
    $('.ql-toolbar').remove();
}

function availableTo() {
    var available_to = $('input[name="inlineRadioOptions"]:checked').val();
    if (available_to == 1) {
        return {
            status: "Success",
            value: "ALL_FACILITATORS"
        };
    } else if (available_to == 2) {
        return {
            status: "Success",
            value: "SELF_ONLY"
        };
    } else {
        return {
            status: "Error",
            message: "Select one of the option given to you for making it available to Other facilitator"
        };
    }
}

function submissionMaxScore() {
    var submission_max_score = $('#submission_max_score').val();
    if ($.isNumeric(submission_max_score)) {
        return {
            status: "Success",
            value: submission_max_score
        };

    } else {
        return {
            status: "Error",
            message: "Points for submission allocated should be a digit!"
        };
    }

}

function reviewMaxScore() {
    var review_max_score = $('#review_max_score').val();
    if ($.isNumeric(review_max_score)) {
        return {
            status: "Success",
            value: review_max_score
        };

    } else {
        return {
            status: "Error",
            message: "Points for review allocated should be a digit!"
        };
    }

}

function deleteAssignment(assignmentId) {

    swal({
            title: "Are you sure?",
            text: "Once deleted, you will not be able to recover this Assignment!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })

        .then((willDelete) => {
            if (willDelete) {
                $.post("/Assignment/deleteAssignment", {
                        assignmentId: assignmentId,
                    },
                    function(response) {
                        response = JSON.parse(response);
                        if (response.status === "Success") {
                            loadSubjectAssignments();
                            swal({
                                title: "Done!",
                                text: "Assignment has been deleted successfully",
                                icon: "success",
                            });
                        } else {
                            swal({
                                title: "Sorry!",
                                text: Assignment.error,
                                icon: "error",
                            });
                        }
                    }
                )

            }
        });
}

function startQuill(notes_div_id) {
    // specify the fonts you would
    var fonts = ['Sans Serif', 'Serif', 'Sans', 'Arial Black', 'Courier New', 'Arial', 'Courier', 'Impact', 'Lucida Grande', 'Times', 'Tahoma', 'Verdana'];
    // generate code friendly names
    function getFontName(font) {
        return font.toLowerCase().replace(/\s/g, "-");
    }
    var fontNames = fonts.map(font => getFontName(font));
    // add fonts to style
    var fontStyles = "";
    fonts.forEach(function (font) {
        var fontName = getFontName(font);
        fontStyles += ".ql-snow .ql-picker.ql-font .ql-picker-label[data-value=" + fontName + "]::before, .ql-snow .ql-picker.ql-font .ql-picker-item[data-value=" + fontName + "]::before {" +
                "content: '" + font + "';" +
                "font-family: '" + font + "', sans-serif;" +
                "}" +
                ".ql-font-" + fontName + "{" +
                " font-family: '" + font + "', sans-serif;" +
                "}";
    });
    var node = document.createElement('style');
    node.innerHTML = fontStyles;
    document.body.appendChild(node);

    var toolbarOptions = [
        [{'header': [1, 2, 3, 4, 5, 6]}],
        [{'size': ['small', false, 'large', 'huge']}], // custom dropdown
        [{'font': fontNames}],
        ['bold', 'italic', 'underline', 'strike'], // toggled buttons
        ['blockquote', 'code-block', 'link'],
        [{'list': 'ordered'}, {'list': 'bullet'}],
        [{'indent': '-1'}, {'indent': '+1'}], // outdent/indent
        [{'color': []}, {'background': []}], // dropdown with defaults from theme
        [{'align': []}],
        [{'direction': 'rtl'}], // text direction
        ['clean']                                         // remove formatting button
    ];

    var quill = new Quill('#' + notes_div_id, {
        modules: {
            toolbar: toolbarOptions
        },
        theme: 'snow'
    });
    return quill;
}

function deleteReference(ReferenceIndex) {
    $('#addReference' + ReferenceIndex).remove();
    if (uploadedReferenceFile != null) {
        uploadedReferenceFile.splice(ReferenceIndex, 1);
    }

}

function validateFields() {
    var subject_id = $('#subjectList').val();
    var description = quill.root.innerHTML ;
    var name = $('#assignment-name').val().trim();
    if (name === "") {
        return {
            status: "Error",
            message: "Please enter the Name!"
        };
    } else if (description === "") {
        return {
            status: "Error",
            message: "Please enter the Description!"
        };
    }

    var response = getAssignmentFor();
    if (response.status === "Error") {
        return response;
    }

    var associated_to = response.associated_to;
    var associated_module_id = response.associated_module_id;
    var associated_topic_id = response.associated_topic_id;

    response = getResourceDetails();
    if (response.status === "Error") {
        return response;
    }

    var resource_details = response.value;
    response = availableTo();
    if (response.status === "Error") {
        return response;
    }

    var available_to = response.value;
    response = submissionMaxScore();
    if (response.status === "Error") {
        return response;
    }

    var submission_max_score = response.value;

    response = reviewMaxScore();
    if (response.status === "Error") {
        return response;
    }

    var review_max_score = response.value;
    var checkbox_value = $('#send-notification-checkbox').is(':checked');
    return {
        status: "Success",
        data: {
            subject_id: subject_id,
            name: name,
            description: description,
            associated_to: associated_to,
            associated_module_id: associated_module_id,
            associated_topic_id: associated_topic_id,
            resource_details: resource_details,
            available_to: available_to,
            submission_max_score: submission_max_score,
            review_max_score: review_max_score,
            send_notification: checkbox_value
        }

    }
}

function selectedResourceType(i) {
    var selectedType = $('#resource-type' + i).val();
    if (selectedType === "addNew" + i) {
        $('#resource-type-new-col' + i).removeClass('d-none');
    } else {
        $('#resource-type-new' + i).val("");
        $('#resource-type-new-col' + i).addClass('d-none');
    }
}


function createSubmissionModal(assignmentId,resubmit = null){

    modalBody = document.getElementById('submission-modal-body');
    modalBody.innerHTML = "";

    if(resubmit == null){
        submissionTypeDiv = document.createElement('div');
        submissionTypeDiv.setAttribute('class','input-group mb-3');
        modalBody.appendChild(submissionTypeDiv) ;

        inputGroupPrependDiv = document.createElement('div');
        inputGroupPrependDiv.setAttribute('class','input-group-prepend');
        submissionTypeDiv.appendChild(inputGroupPrependDiv)

        submissionTypeLabel = document.createElement('label');
        submissionTypeLabel.setAttribute('class','input-group-text');
        submissionTypeLabel.setAttribute('for','submissionTypesDropdown'+assignmentId);
        submissionTypeLabel.innerHTML = "Submission Type:";
        inputGroupPrependDiv.appendChild(submissionTypeLabel);

        submissionTypesDropdown = document.createElement('select');
        submissionTypesDropdown.setAttribute('id','submissionTypesDropdown'+assignmentId);
        submissionTypesDropdown.setAttribute('class','assignment-self-team custom-select');
        submissionTypesDropdown.setAttribute('style','height: 36px;background-color: #fff!important;');
        submissionTypesDropdown.setAttribute('onchange','changeOfSubmissionTypesListener('+assignmentId+')');
        submissionTypeDiv.appendChild(submissionTypesDropdown);

        teamMemberDiv = document.createElement('div');
        teamMemberDiv.setAttribute('id','team');
        teamMemberDiv.setAttribute('class','hidden');
        modalBody.appendChild(teamMemberDiv);


        selectOption1 = document.createElement('option');
        selectOption1.setAttribute('value','self');
        selectOption1.setAttribute('selected','selected');
        selectOption1.innerHTML = 'Self';
        submissionTypesDropdown.appendChild(selectOption1);

        selectOption2 = document.createElement('option');
        selectOption2.setAttribute('value','team');
        selectOption2.innerHTML = 'Team';
        submissionTypesDropdown.appendChild(selectOption2);
    }

    rowDiv = document.createElement('div');
    rowDiv.setAttribute('class','row no-gutters mt-4 mb-4');
    rowDiv.setAttribute('id','secondRow');
    modalBody.appendChild(rowDiv);

    colDiv = document.createElement('div');
    colDiv.setAttribute('class','col');
    rowDiv.appendChild(colDiv);

    descriptionLabel = document.createElement('label');
    descriptionLabel.setAttribute('for','submissionDescription'+assignmentId);
    descriptionLabel.innerHTML = "Submission";
    colDiv.appendChild(descriptionLabel);

    descriptionDiv = document.createElement('div');
    descriptionDiv.setAttribute('id','submissionDescription'+assignmentId);
    descriptionDiv.setAttribute('style','height:auto;');
    colDiv.appendChild(descriptionDiv);

    fileUploadRowDiv = document.createElement('div');
    fileUploadRowDiv.setAttribute('class','row no-gutters');
    fileUploadRowDiv.setAttribute('hidden','hidden');
    fileUploadRowDiv.setAttribute('id','fileUploadButton');
    modalBody.appendChild(fileUploadRowDiv);

    fileUpload = document.createElement('div');
    fileUpload.setAttribute('id','message-attachments');
    fileUploadRowDiv.append(fileUpload);

    ulList = document.createElement('ul');
    ulList.setAttribute('id','uploaded-file-list');
    ulList.setAttribute('class','pl-4');
    fileUpload.appendChild(ulList);

    uploadButtonDiv = document.createElement('div');
    uploadButtonDiv.setAttribute('class','row no-gutters');
    uploadButtonDiv.setAttribute('id','fileUploadButton');
    modalBody.appendChild(uploadButtonDiv);

    uploadButtonLabel = document.createElement('label');
    uploadButtonLabel.setAttribute('for','assignmentUpload')
    uploadButtonDiv.appendChild(uploadButtonLabel);

    uploadButton = document.createElement('button');
    uploadButton.setAttribute('id','assignmentUpload');
    uploadButton.setAttribute('class','btn border rounded file-upload-btn');
    uploadButton.setAttribute('onclick','initiateFileUploadModal(s3_details_4fileUpload,attachment_File_Prefix,attachmentUploadComplete)');
    uploadButton.innerHTML = 'Upload';
    uploadButtonDiv.appendChild(uploadButton);

    uploadIcon = document.createElement('i');
    uploadIcon.setAttribute('class','fas fa-upload');
    uploadIcon.setAttribute('style','margin-left:5px;');
    uploadIcon.setAttribute('aria-hidden','true');
    uploadButton.appendChild(uploadIcon);

    modalFooter = document.getElementById('submission-modal-footer');
    modalFooter.innerHTML = "";
    footerRowDiv = document.createElement('div');
    footerRowDiv.setAttribute('class','row');
    modalFooter.appendChild(footerRowDiv);

    footerColDiv = document.createElement('div');
    footerColDiv.setAttribute('class','col');
    footerRowDiv.appendChild(footerColDiv);

    saveButton = document.createElement('button');
    saveButton.setAttribute('id','assignment-submit-button');
    saveButton.setAttribute('class','btn btn-bg-lightgray flat-btn mt-3');
    saveButton.setAttribute('onclick','saveAssignment('+assignmentId+')');
    saveButton.setAttribute('type','submit');
    footerColDiv.appendChild(saveButton);

    saveIcon = document.createElement('i');
    saveIcon.setAttribute('class','fas fa-check');
    saveButton.appendChild(saveIcon);

    saveTextDiv = document.createElement('div');
    saveTextDiv.setAttribute('class','button-label');
    saveTextDiv.innerHTML = 'save';
    saveButton.appendChild(saveTextDiv);

    submissionQuill = null;
    submissionQuill = startQuill('submissionDescription'+assignmentId);

    $('#assignment-submit-button').attr('assignmentId',assignmentId);
}


// ===========================================================

submission={};
attachedFiles = [];

function saveAssignment(assignmentId){
    teamMembers=[];
    teamMemberNames="";
    var submissionType = $('#submissionTypesDropdown'+assignmentId).val();
    if (submissionType == "team"){
        $("input:checkbox[name='teamMembers[]']:checked").each(function(){
            let user_id =$(this).val();
            teamMembers.push(user_id);
            teamMemberNames += "\n"+$('#teamMember-label'+user_id).html();
        });
    }
    var description = submissionQuill.root.innerHTML ;
    if (description === "<p><br></p>") {
        swal({
            title: "Sorry!",
            text: 'Write Something under Submission',
            icon: "error",
        });
    }

    submission['assignmentId'] = assignmentId;
    submission['submissionType'] = submissionType;
    submission['teamMembers'] = teamMembers;
    submission['description'] = description;
    submission['attachedFiles'] = attachedFiles;
    $.post("/Assignment/saveAssignment", {
                submission: submission
        },
        function(response){
            response = JSON.parse(response);
            if (response.status === "Success") {
                swal({
                    title: "Done!",
                    text: "Submission has been done successfully",
                    icon: "success",
                }).then(()=>{
                    icon = document.createElement('i');
                    if(submissionType == 'self'){
                        icon.setAttribute('class','far fa-user assignment-upload-status-icon');
                        icon.setAttribute('data-toggle','tooltip');
                        icon.setAttribute('title','Submitted as an individual');
                    }
                    else{
                        icon.setAttribute('class','fas fa-users assignment-upload-status-icon');
                        icon.setAttribute('data-toggle','tooltip');
                        icon.setAttribute('title','Submitted in collaboration with '+teamMemberNames);
                    }
                    icon_col = document.getElementById('submission-status-icon-'+assignmentId);
                    icon_col.appendChild(icon);

                    buttonDiv = document.getElementById('button-div-'+assignmentId);
                    document.getElementById('submitButton'+assignmentId).style.display  = "none";;


                    view_and_resubmit = document.createElement('button');
                    view_and_resubmit.setAttribute('id','reSubmitButton'+assignmentId);
                    view_and_resubmit.setAttribute('class',"btn btn-bg-lightgray flat-btn ml-1 assignment-upload-button float-right mt-2");
                    view_and_resubmit.setAttribute('assignmentId',assignmentId);
                    view_and_resubmit.setAttribute('data-target',"#assignment-file-upload-modal");
                    view_and_resubmit.setAttribute('data-toggle',"modal");
                    view_and_resubmit.setAttribute('type',"submit");
                    view_and_resubmit.setAttribute('onclick',"resubmission("+assignmentId+")");

                    buttonDiv.appendChild(view_and_resubmit);

                    button_icon = document.createElement('i');
                    button_icon.setAttribute('class','fas fa-check');
                    view_and_resubmit.appendChild(button_icon);

                    button_text_div = document.createElement('div');
                    button_text_div.setAttribute('id','submit'+assignmentId);
                    button_text_div.setAttribute('class','button-label');
                    button_text_div.innerHTML = "View and Resubmit";
                    button_icon.appendChild(button_text_div);

                });
                clearSubmissionModal(assignmentId);
            } else {
                swal({
                    title: "Sorry!",
                    text: 'Submission has been failed',
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
    $('#fileUploadButton').removeAttr('hidden');
    uploadedFileList = document.getElementById('uploaded-file-list');
    uploadedFileList.innerHTML = "";
    attachedFiles.forEach(function(attachedFile, index){
        fileItem = document.createElement('li');
        uploadedFileList.appendChild(fileItem);
        fileItem.innerHTML = attachedFile.fileName  + '&nbsp' +'<i class="fa fa-times remove-attachment"  title = "Remove" onclick = "removeReference('+ index +')" aria-hidden="true"></i>';
    });

}

function removeReference(index){
    attachedFiles.splice(index,1);
    listAttachedFiles();
}

function clearSubmissionModal(assignmentId){
    $('#submissionTypesDropdown'+assignmentId).val('self');
    $('#submissionTypesDropdown'+assignmentId).trigger('onchange');
    submissionQuill.root.innerHTML = "";
    attachedFiles=[];
    uploadedFileList = document.getElementById('uploaded-file-list');
    uploadedFileList.innerHTML = "";
    $('#assignment-file-upload-modal').modal('hide');
    $('.modal-backdrop').remove();
}


function changeOfSubmissionTypesListener(assignmentId) {
    var selectedValue = $("#submissionTypesDropdown"+assignmentId+" option:selected").val();
    if (selectedValue == "self") {
        teamDiv = document.getElementById('team');
        teamDiv.innerHTML = "";
        teamDiv.classList.add("hidden");

    } else if (selectedValue == "team") {

        teamDiv = document.getElementById('team');
        teamDiv.innerHTML = "";


        cardDiv = document.createElement('div');
        cardDiv.setAttribute('class','card mb-3 mt-2 ml-2 mr-2');
        teamDiv.appendChild(cardDiv);

        cardBodyDiv = document.createElement('div');
        cardBodyDiv.setAttribute('class','card-body');
        cardDiv.appendChild(cardBodyDiv);

        teamLabel = document.createElement('label');
        teamLabel.innerHTML = "Select your Team Members:";
        cardBodyDiv.appendChild(teamLabel);

        team_members.forEach(team_member=>{

            teamMemberNameDiv = document.createElement('div');
            cardBodyDiv.appendChild(teamMemberNameDiv);

            teamName = document.createElement('input');
            teamName.setAttribute('type','checkbox');
            teamName.setAttribute('id','teamMember'+team_member['id']);
            teamName.setAttribute('class','mr-2 ml-2 mt-1 mb-2');
            teamName.setAttribute('name','teamMembers[]');
            teamName.setAttribute('value',team_member['id']);
            teamMemberNameDiv.appendChild(teamName);

            nameLabel = document.createElement('label');
            nameLabel.setAttribute('for','teamMember'+team_member['id']);
            nameLabel.setAttribute('id','teamMember-label'+team_member['id']);
            nameLabel.innerHTML = team_member['name'];
            teamMemberNameDiv.appendChild(nameLabel);

        });

        teamDiv.classList.remove("hidden");
    }
}

function resubmission(assignmentId){
    $('#submission-modal-footer').hide();
    modalBody = document.getElementById('submission-modal-body');
    modalBody.innerHTML = "";

    var submissionStatus = $('#submitButton'+assignmentId).attr('submissionStatus');
    if(submissionStatus != "ACCEPTED"){
        RowDiv = document.createElement('div');
        RowDiv.setAttribute('class','row');
        modalBody.appendChild(RowDiv);

        ColDiv = document.createElement('div');
        ColDiv.setAttribute('class','col');
        RowDiv.appendChild(ColDiv);

        resubmitButton = document.createElement('button');
        resubmitButton.setAttribute('id','assignment-resubmit-button'+assignmentId);
        resubmitButton.setAttribute('class','btn btn-bg-lightgray flat-btn mt-3');
        resubmitButton.setAttribute('onclick','reSubmitDiv('+assignmentId+')');
        resubmitButton.setAttribute('type','submit');
        ColDiv.appendChild(resubmitButton);

        resubmitIcon = document.createElement('i');
        resubmitIcon.setAttribute('class','fas fa-chevron-right');
        resubmitIcon.setAttribute('id','leftArrowIcon');
        resubmitButton.appendChild(resubmitIcon);

        resubmitTextDiv = document.createElement('div');
        resubmitTextDiv.setAttribute('class','button-label');
        resubmitTextDiv.innerHTML = 'Resubmit';
        resubmitButton.appendChild(resubmitTextDiv);

        resubmitFormDiv = document.createElement('div');
        resubmitFormDiv.setAttribute('id','resubmitDiv');
        resubmitFormDiv.setAttribute('class','card row no-gutters mt-4 mb-4');
        resubmitFormDiv.setAttribute('hidden','hidden');
        modalBody.appendChild(resubmitFormDiv);

    }

    //Ajax for the message_details

    $.post("/Assignment/retriveMessagesOfAssignment", {
                assignmentId: assignmentId
        },
        function(response){
            response = JSON.parse(response);
            response.forEach((message_detail) => {

                messageIdDiv = document.createElement('div');
                messageIdDiv.setAttribute('id','message-id');
                messageIdDiv.setAttribute('message_id',message_detail['message_id']);
                modalBody.appendChild(messageIdDiv);


                if(message_detail['sender_type'] == "self"){
                    messageDiv = document.createElement('div');
                    messageDiv.setAttribute('id','sender-message');
                    messageDiv.setAttribute('class','message-participant');
                    modalBody.appendChild(messageDiv);
                }

                else{
                    messageDiv = document.createElement('div');
                    messageDiv.setAttribute('id','other-message');
                    messageDiv.setAttribute('class','message-participant');
                    modalBody.appendChild(messageDiv);
                }

                paddingDiv = document.createElement('div');
                paddingDiv.setAttribute('class','px-3');
                messageDiv.appendChild(paddingDiv);

                rowDiv = document.createElement('div');
                rowDiv.setAttribute('class','row message-grey');
                paddingDiv.appendChild(rowDiv);

                if(message_detail['sender_type'] == "other"){
                    picDiv = document.createElement('div');
                    picDiv.setAttribute('class','col-md-6 col-lg-6 pic-div');
                    rowDiv.appendChild(picDiv);

                    senderDiv = document.createElement('div');
                    senderDiv.setAttribute('class','align-text-left') ;
                    senderDiv.innerHTML = message_detail['sender'];
                    picDiv.appendChild(senderDiv);

                    sentTimeDiv = document.createElement('div');
                    sentTimeDiv.setAttribute('class','col-md-6 col-lg-6 align-text-right');
                    sentTimeDiv.innerHTML = message_detail['sent_time'];
                    rowDiv.appendChild(sentTimeDiv);
                }

                else{
                    sentTimeDiv = document.createElement('div');
                    sentTimeDiv.setAttribute('class','col-md-6 col-lg-6 align-text-left');
                    sentTimeDiv.innerHTML = message_detail['sent_time'];
                    rowDiv.appendChild(sentTimeDiv);

                    picDiv = document.createElement('div');
                    picDiv.setAttribute('class','col-md-6 col-lg-6 pic-div');
                    rowDiv.appendChild(picDiv);

                    senderDiv = document.createElement('div');
                    senderDiv.setAttribute('class','align-text-right') ;
                    senderDiv.innerHTML = message_detail['sender'];
                    picDiv.appendChild(senderDiv);
                }

                messageBodyRowDiv = document.createElement('div');
                messageBodyRowDiv.setAttribute('class','row mb-2');
                paddingDiv.appendChild(messageBodyRowDiv);

                messageBodyDiv = document.createElement('div');
                messageBodyDiv.setAttribute('class','col-xs-12  messages-body-facilitator align-middle');
                messageBodyDiv.innerHTML = message_detail['message_body'];
                messageBodyRowDiv.appendChild(messageBodyDiv);

                if(message_detail['attachments'] != null){
                    message_detail['attachments'].forEach((attachment)=>{
                        attachmentSpan = document.createElement('span');
                        attachmentSpan.setAttribute('class','attachments p-2 m-1 mb-2');
                        paddingDiv.appendChild(attachmentSpan);

                        anchorTag = document.createElement('a');
                        anchorTag.setAttribute('href',attachment['file_path']);
                        anchorTag.innerHTML = attachment['file_name']+'&nbsp';
                        attachmentSpan.appendChild(anchorTag);
                    });
                }

            });

    });

}


function reSubmitDiv(assignmentId){
    $('#resubmitDiv').toggle();

    $('#leftArrowIcon').toggleClass("fas fa-chevron-right fas fa-chevron-down");

    $('#resubmitDiv').removeAttr('hidden');
    resubmitDiv = document.getElementById('resubmitDiv');
    resubmitDiv.innerHTML = "";

    colDiv = document.createElement('div');
    colDiv.setAttribute('class','card-body');
    resubmitDiv.appendChild(colDiv);

    descriptionLabel = document.createElement('label');
    descriptionLabel.setAttribute('for','submissionDescription'+assignmentId);
    descriptionLabel.innerHTML = "Submission";
    colDiv.appendChild(descriptionLabel);

    descriptionDiv = document.createElement('div');
    descriptionDiv.setAttribute('id','submissionDescription'+assignmentId);
    colDiv.appendChild(descriptionDiv);

    fileUploadRowDiv = document.createElement('div');
    fileUploadRowDiv.setAttribute('class','row no-gutters');
    fileUploadRowDiv.setAttribute('hidden','hidden');
    fileUploadRowDiv.setAttribute('id','fileUploadButton');
    colDiv.appendChild(fileUploadRowDiv);

    fileUpload = document.createElement('div');
    fileUpload.setAttribute('id','message-attachments');
    fileUploadRowDiv.append(fileUpload);

    ulList = document.createElement('ul');
    ulList.setAttribute('id','uploaded-file-list');
    ulList.setAttribute('class','pl-4');
    fileUpload.appendChild(ulList);

    uploadButtonDiv = document.createElement('div');
    uploadButtonDiv.setAttribute('class','row no-gutters mt-4');
    uploadButtonDiv.setAttribute('id','fileUploadButton');
    colDiv.appendChild(uploadButtonDiv);

    uploadButtonLabel = document.createElement('label');
    uploadButtonLabel.setAttribute('for','assignmentUpload')
    uploadButtonDiv.appendChild(uploadButtonLabel);

    uploadButton = document.createElement('button');
    uploadButton.setAttribute('id','assignmentUpload');
    uploadButton.setAttribute('class','btn border rounded file-upload-btn');
    uploadButton.setAttribute('onclick','initiateFileUploadModal(s3_details_4fileUpload,attachment_File_Prefix,attachmentUploadComplete)');
    uploadButton.innerHTML = 'Upload';
    uploadButtonDiv.appendChild(uploadButton);

    uploadIcon = document.createElement('i');
    uploadIcon.setAttribute('class','fas fa-upload');
    uploadIcon.setAttribute('style','margin-left:5px;');
    uploadIcon.setAttribute('aria-hidden','true');
    uploadButton.appendChild(uploadIcon);


    footerRowDiv = document.createElement('div');
    footerRowDiv.setAttribute('class','row');
    colDiv.appendChild(footerRowDiv);

    footerColDiv = document.createElement('div');
    footerColDiv.setAttribute('class','col');
    footerRowDiv.appendChild(footerColDiv);

    saveButton = document.createElement('button');
    saveButton.setAttribute('id','assignment-submit-button');
    saveButton.setAttribute('class','btn btn-bg-lightgray flat-btn mt-3');
    saveButton.setAttribute('onclick','resubmitAssignment('+assignmentId+')');
    saveButton.setAttribute('type','submit');
    footerColDiv.appendChild(saveButton);

    saveIcon = document.createElement('i');
    saveIcon.setAttribute('class','fas fa-check');
    saveButton.appendChild(saveIcon);

    saveTextDiv = document.createElement('div');
    saveTextDiv.setAttribute('class','button-label');
    saveTextDiv.innerHTML = 'save';
    saveButton.appendChild(saveTextDiv);

    submissionQuill = null;
    submissionQuill = startQuill('submissionDescription'+assignmentId);

}

function resubmitAssignment(assignmentId){
    var description = submissionQuill.root.innerHTML ;
    if (description === "<p><br></p>") {
        swal({
            title: "Sorry!",
            text: 'Write Something under Submission',
            icon: "error",
        });
    }

    submission['assignmentId'] = assignmentId;
    submission['description'] = description;
    submission['attachedFiles'] = attachedFiles;

    var message_id = $('#message-id').attr('message_id');
    $.post("/Assignment/resubmitAssignment", {
                message_id: message_id,
                submission: submission,
                assignmentId:assignmentId
        },
        function(response){
            response = JSON.parse(response);
            if (response.status === "Success") {
                swal({
                    title: "Done!",
                    text: "Submission has been done successfully",
                    icon: "success",
                }).then(()=>{
                    $('#assignment-file-upload-modal').modal('hide');
                    $('.modal-backdrop').remove();
                });
                clearSubmissionModal(assignmentId);
            } else {
                swal({
                    title: "Sorry!",
                    text: 'Submission has been failed',
                    icon: "error",
                });
            }
    });

}

function reviewAssignment(assignmentId){
    $('#submission-modal-footer').hide();
    modalBody = document.getElementById('submission-modal-body');
    modalBody.innerHTML = "";

    RowDiv = document.createElement('div');
    RowDiv.setAttribute('class','row');
    modalBody.appendChild(RowDiv);

    ColDiv = document.createElement('div');
    ColDiv.setAttribute('class','col');
    RowDiv.appendChild(ColDiv);

    resubmitButton = document.createElement('button');
    resubmitButton.setAttribute('id','assignment-review-button'+assignmentId);
    resubmitButton.setAttribute('class','btn btn-bg-lightgray flat-btn mt-3 ml-3');
    resubmitButton.setAttribute('onclick','reviewDiv('+assignmentId+')');
    resubmitButton.setAttribute('type','submit');
    ColDiv.appendChild(resubmitButton);

    resubmitIcon = document.createElement('i');
    resubmitIcon.setAttribute('class','fas fa-chevron-right');
    resubmitIcon.setAttribute('id','leftArrowIcon');
    resubmitButton.appendChild(resubmitIcon);

    resubmitTextDiv = document.createElement('div');
    resubmitTextDiv.setAttribute('class','button-label');
    resubmitTextDiv.innerHTML = 'Review';
    resubmitButton.appendChild(resubmitTextDiv);

    resubmitFormDiv = document.createElement('div');
    resubmitFormDiv.setAttribute('id','resubmitDiv');
    resubmitFormDiv.setAttribute('class','card row no-gutters mt-4 mb-4');
    resubmitFormDiv.setAttribute('hidden','hidden');
    modalBody.appendChild(resubmitFormDiv);

    var submission_status = $('#reviewButton'+assignmentId).attr('submission_status');
    var status = $('#reviewButton'+assignmentId).attr('status');
    if(submission_status === "ACCEPTED" || status === "ACCEPTED"){
        $('#assignment-review-button'+assignmentId).hide();
        $('#reviewButton'+assignmentId).html('View');
    }

    //Ajax for the message_details
    var learnersUserId = $('#submittedAssignmentBody'+assignmentId).attr('learnersUserId');
    $.post("/Assignment/retriveMessagesOfAssignment", {
                assignmentId: assignmentId,
                learnersUserId: learnersUserId
        },
        function(response){
            response = JSON.parse(response);
            response.forEach((message_detail) => {

                messageIdDiv = document.createElement('div');
                messageIdDiv.setAttribute('id','message-id');
                messageIdDiv.setAttribute('message_id',message_detail['message_id']);
                modalBody.appendChild(messageIdDiv);


                if(message_detail['sender_type'] == "self"){
                    messageDiv = document.createElement('div');
                    messageDiv.setAttribute('id','sender-message');
                    messageDiv.setAttribute('class','message-participant');
                    modalBody.appendChild(messageDiv);
                }
                else{
                    messageDiv = document.createElement('div');
                    messageDiv.setAttribute('id','other-message');
                    messageDiv.setAttribute('class','message-participant');
                    modalBody.appendChild(messageDiv);
                }

                paddingDiv = document.createElement('div');
                paddingDiv.setAttribute('class','px-3');
                messageDiv.appendChild(paddingDiv);

                rowDiv = document.createElement('div');
                rowDiv.setAttribute('class','row message-grey');
                paddingDiv.appendChild(rowDiv);

                if(message_detail['sender_type'] == "other"){
                    picDiv = document.createElement('div');
                    picDiv.setAttribute('class','col-md-6 col-lg-6 pic-div');
                    rowDiv.appendChild(picDiv);

                    senderDiv = document.createElement('div');
                    senderDiv.setAttribute('class','align-text-left') ;
                    senderDiv.innerHTML = message_detail['sender'];
                    picDiv.appendChild(senderDiv);

                    sentTimeDiv = document.createElement('div');
                    sentTimeDiv.setAttribute('class','col-md-6 col-lg-6 align-text-right');
                    sentTimeDiv.innerHTML = message_detail['sent_time'];
                    rowDiv.appendChild(sentTimeDiv);
                }


                else{
                    sentTimeDiv = document.createElement('div');
                    sentTimeDiv.setAttribute('class','col-md-6 col-lg-6 align-text-left');
                    sentTimeDiv.innerHTML = message_detail['sent_time'];
                    rowDiv.appendChild(sentTimeDiv);

                    picDiv = document.createElement('div');
                    picDiv.setAttribute('class','col-md-6 col-lg-6 pic-div');
                    rowDiv.appendChild(picDiv);

                    senderDiv = document.createElement('div');
                    senderDiv.setAttribute('class','align-text-right') ;
                    senderDiv.innerHTML = message_detail['sender'];
                    picDiv.appendChild(senderDiv);
                }

                messageBodyRowDiv = document.createElement('div');
                messageBodyRowDiv.setAttribute('class','row mb-2');
                paddingDiv.appendChild(messageBodyRowDiv);

                messageBodyDiv = document.createElement('div');
                messageBodyDiv.setAttribute('class','col-xs-12  messages-body-facilitator align-middle');
                messageBodyDiv.innerHTML = message_detail['message_body'];
                messageBodyRowDiv.appendChild(messageBodyDiv);

                if(message_detail['attachments'] != null){
                    message_detail['attachments'].forEach((attachment)=>{
                        attachmentSpan = document.createElement('span');
                        attachmentSpan.setAttribute('class','attachments p-2 m-1 mb-2');
                        paddingDiv.appendChild(attachmentSpan);

                        anchorTag = document.createElement('a');
                        anchorTag.setAttribute('href',attachment['file_path']);
                        anchorTag.innerHTML = attachment['file_name']+'&nbsp';
                        attachmentSpan.appendChild(anchorTag);
                    });
                }

            });

    });


}
function reviewDiv(assignmentId){
    $('#resubmitDiv').toggle();

    $('#leftArrowIcon').toggleClass("fas fa-chevron-right fas fa-chevron-down");

    $('#resubmitDiv').removeAttr('hidden');
    resubmitDiv = document.getElementById('resubmitDiv');
    resubmitDiv.innerHTML = "";

    colDiv = document.createElement('div');
    colDiv.setAttribute('class','card-body');
    resubmitDiv.appendChild(colDiv);

    descriptionLabel = document.createElement('label');
    descriptionLabel.setAttribute('for','submissionDescription'+assignmentId);
    descriptionLabel.innerHTML = "Feedback";
    colDiv.appendChild(descriptionLabel);

    descriptionDiv = document.createElement('div');
    descriptionDiv.setAttribute('id','submissionDescription'+assignmentId);
    colDiv.appendChild(descriptionDiv);

    radioButtonRowDiv = document.createElement('div');
    radioButtonRowDiv.setAttribute('class','row no-gutters mt-4');
    radioButtonRowDiv.setAttribute('id','radioButton');
    colDiv.appendChild(radioButtonRowDiv);

    radioButtonContainerDiv1 = document.createElement('div');
    radioButtonContainerDiv1.setAttribute('class','form-check form-check-inline');
    radioButtonRowDiv.appendChild(radioButtonContainerDiv1)

    radioButton1 = document.createElement('input');
    radioButton1.setAttribute('class','form-check-input');
    radioButton1.setAttribute('id','radio1');
    radioButton1.setAttribute('value','1');
    radioButton1.setAttribute('type','radio');
    radioButton1.setAttribute('name','reviewAction');
    radioButtonContainerDiv1.appendChild(radioButton1);

    radioLabel1 = document.createElement('label');
    radioLabel1.setAttribute('class','form-check-label');
    radioLabel1.setAttribute('for','radio1');
    radioLabel1.innerHTML = "Accept";
    radioButtonContainerDiv1.appendChild(radioLabel1);

    radioButtonContainerDiv2 = document.createElement('div');
    radioButtonContainerDiv2.setAttribute('class','form-check form-check-inline');
    radioButtonRowDiv.appendChild(radioButtonContainerDiv2)

    radioButton2 = document.createElement('input');
    radioButton2.setAttribute('class','form-check-input');
    radioButton2.setAttribute('id','radio2');
    radioButton2.setAttribute('value','2');
    radioButton2.setAttribute('type','radio');
    radioButton2.setAttribute('name','reviewAction');
    radioButtonContainerDiv2.appendChild(radioButton2);

    radioLabel2 = document.createElement('label');
    radioLabel2.setAttribute('class','form-check-label');
    radioLabel2.setAttribute('for','radio2');
    radioLabel2.innerHTML = "Suggest Resubmission";
    radioButtonContainerDiv2.appendChild(radioLabel2);


    reviewPointsDiv = document.createElement('div');
    reviewPointsDiv.setAttribute('class','row no-gutters mt-4');
    reviewPointsDiv.setAttribute('id','review_points_div');
    colDiv.appendChild(reviewPointsDiv);

    var max_review_points = $('#reviewButton'+assignmentId).attr('review_max_score');

    inputGroupDiv = document.createElement('div');
    inputGroupDiv.setAttribute('class','input-group mb-3');
    reviewPointsDiv.appendChild(inputGroupDiv);

    inputGroupPrependDiv = document.createElement('div');
    inputGroupPrependDiv.setAttribute('class','input-group-prepend');
    inputGroupDiv.appendChild(inputGroupPrependDiv);

    inputGroupPrependSpan = document.createElement('span');
    inputGroupPrependSpan.setAttribute('class','input-group-text');
    inputGroupPrependSpan.innerHTML = "Points for review";
    inputGroupPrependDiv.appendChild(inputGroupPrependSpan);

    pointsBox = document.createElement('input');
    pointsBox.setAttribute('type','number');
    pointsBox.setAttribute('class','form-control textfield');
    pointsBox.setAttribute('id','review_max_score');
    pointsBox.setAttribute('name','review_max_score');
    inputGroupDiv.appendChild(pointsBox);

    inputGroupAppendDiv = document.createElement('div');
    inputGroupAppendDiv.setAttribute('class','input-group-append');
    inputGroupDiv.appendChild(inputGroupAppendDiv);

    inputGroupSpan = document.createElement('span');
    inputGroupSpan.setAttribute('class','input-group-text');
    inputGroupSpan.innerHTML = "/ "+max_review_points;
    inputGroupAppendDiv.appendChild(inputGroupSpan);


    $('#review_points_div').hide();

    $('input[name="reviewAction"]').on('click', function() {
       if ($(this).val() == '1') {
           $('#review_points_div').show();
       }
       else {
           $('#review_points_div').hide();
       }
   });


    fileUploadRowDiv = document.createElement('div');
    fileUploadRowDiv.setAttribute('class','row no-gutters');
    fileUploadRowDiv.setAttribute('hidden','hidden');
    fileUploadRowDiv.setAttribute('id','fileUploadButton');
    colDiv.appendChild(fileUploadRowDiv);

    fileUpload = document.createElement('div');
    fileUpload.setAttribute('id','message-attachments');
    fileUpload.setAttribute('class','mt-4');
    fileUploadRowDiv.append(fileUpload);

    ulList = document.createElement('ul');
    ulList.setAttribute('id','uploaded-file-list');
    ulList.setAttribute('class','pl-4');
    fileUpload.appendChild(ulList);

    uploadButtonDiv = document.createElement('div');
    uploadButtonDiv.setAttribute('class','row no-gutters mt-4');
    uploadButtonDiv.setAttribute('id','fileUploadButton');
    colDiv.appendChild(uploadButtonDiv);

    uploadButtonLabel = document.createElement('label');
    uploadButtonLabel.setAttribute('for','assignmentUpload')
    uploadButtonDiv.appendChild(uploadButtonLabel);

    uploadButton = document.createElement('button');
    uploadButton.setAttribute('id','assignmentUpload');
    uploadButton.setAttribute('class','btn border rounded file-upload-btn');
    uploadButton.setAttribute('onclick','initiateFileUploadModal(s3_details_4fileUpload,attachment_File_Prefix,attachmentUploadComplete)');
    uploadButton.innerHTML = 'Upload';
    uploadButtonDiv.appendChild(uploadButton);

    uploadIcon = document.createElement('i');
    uploadIcon.setAttribute('class','fas fa-upload');
    uploadIcon.setAttribute('style','margin-left:5px;');
    uploadIcon.setAttribute('aria-hidden','true');
    uploadButton.appendChild(uploadIcon);


    footerRowDiv = document.createElement('div');
    footerRowDiv.setAttribute('class','row');
    colDiv.appendChild(footerRowDiv);

    footerColDiv = document.createElement('div');
    footerColDiv.setAttribute('class','col');
    footerRowDiv.appendChild(footerColDiv);

    saveButton = document.createElement('button');
    saveButton.setAttribute('id','assignment-submit-button');
    saveButton.setAttribute('class','btn btn-bg-lightgray flat-btn mt-3');
    saveButton.setAttribute('onclick','reviewAssignmentSubmission('+assignmentId+')');
    saveButton.setAttribute('type','submit');
    footerColDiv.appendChild(saveButton);

    saveIcon = document.createElement('i');
    saveIcon.setAttribute('class','fas fa-check');
    saveButton.appendChild(saveIcon);

    saveTextDiv = document.createElement('div');
    saveTextDiv.setAttribute('class','button-label');
    saveTextDiv.innerHTML = 'save';
    saveButton.appendChild(saveTextDiv);

    submissionQuill = null;
    submissionQuill = startQuill('submissionDescription'+assignmentId);

}


function reviewAssignmentSubmission(assignmentId){
    var description = submissionQuill.root.innerHTML ;
    if (description === "<p><br></p>") {
        swal({
            title: "Sorry!",
            text: 'Write Something under Submission',
            icon: "error",
        });
    }

    submission['assignmentId'] = assignmentId;
    submission['description'] = description;
    submission['attachedFiles'] = attachedFiles;
    var max_review_points = $('#reviewButton'+assignmentId).attr('review_max_score');
    var review_points = $('#review_max_score').val();

   var action = $('input[name="reviewAction"]:checked').val();
   if (action == 1) {
       submission['reviewAction'] = "Accept";
       submission['reviewPoint'] = $('#review_max_score').val();
   } else{
        submission['reviewAction'] = "Resubmit";
   }


    var thread_id = $('#submittedAssignmentBody'+assignmentId).attr('thread_id');
    var learnersUserId = $('#submittedAssignmentBody'+assignmentId).attr('learnersUserId');
    var submissionId = $('#reviewButton'+assignmentId).attr('submissionid');
    $.post("/Assignment/reviewAssignmentSubmission", {
                thread_id: thread_id,
                learnersUserId: learnersUserId,
                submission: submission,
                submissionId: submissionId,
                assignmentId:assignmentId
        },
        function(response){
            response = JSON.parse(response);
            if (response.status === "Success") {
                if(submission['reviewAction'] === "Accept"){
                    $('#reviewButton'+assignmentId).attr('status','ACCEPTED');
                }
                else{
                    $('#reviewButton'+assignmentId).attr('status','RECOMMENDED_RESUBMISSION');
                }
                swal({
                    title: "Done!",
                    text: "Submission has been done successfully",
                    icon: "success",
                }).then(()=>{
                    $('#assignment-file-upload-modal').modal('hide');
                    $('.modal-backdrop').remove();
                    var submission_status = $('#reviewButton'+assignmentId).attr('submission_status');
                    var status = $('#reviewButton'+assignmentId).attr('status');
                    if(submission_status === "ACCEPTED" || status === "ACCEPTED"){
                        $('#reviewButton'+assignmentId).html('View');
                    }

                    if(status === "ACCEPTED"){
                        $('#review_tick'+assignmentId).append('<i class="fas fa-check" style="color:#60c8d7!important;;"></i>');
                    }
                    else{
                        $('#review_tick'+assignmentId).append('<i class="fas fa-check" style="color:#d76f5f;"></i>');
                    }
                });
                clearSubmissionModal(assignmentId);
            } else {
                swal({
                    title: "Sorry!",
                    text: 'Submission has been failed',
                    icon: "error",
                });
            }
    });

}

console.log("Test Log");