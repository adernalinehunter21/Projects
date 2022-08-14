
quill = null;
function loadSubmittedReflections() {
    var courseId = $('#programList').val();
    $.post("/Reflection/getSubmittedReflection", {
        courseId: courseId
    },
            function (response) {
                response = JSON.parse(response);

                if (response.status == "Success") {

                    var sessions = response.session_wise_assignment_data;
                    if (sessions.length > 0) {
                        sessions.forEach((oneSession) => {
                            var submittedParticipants = oneSession.Participants;
                            if (submittedParticipants.length > 0) {
                                var sessionSectionDivId = "session-" + oneSession.session_id;
                                // console.log(sessionSectionDivId);
                                $("#submittedQuizSection").append('<div id="' + sessionSectionDivId + '"></div>');

                                var sessionCardHtml = "";
                                submittedParticipants.forEach((oneParticipant) => {
                                    var participantQuizCardsHtml = "";
                                    var submittedReflection = oneParticipant.reflection;
                                    participantId = oneParticipant['id'];
                                    submittedReflection.forEach((reflection) => {
                                        var reflectionName = reflection['name'];
                                        var reflectionId = reflection['id'];

                                        var oneQuizCard = '<div class="col-md-4 col-lg-4 small-size-col-margin mb-1 quizCard">' +
                                                '  <div class="card border-light h-100 card-padding-dash shadow" data-toggle="modal" href="#reflectionModal" id="reflectionModel" onclick = "getReflection(' + reflectionId + ',' + participantId + ')" >' +
                                                '      <div class="card-body">' +
                                                '          <div class="row align-items-center h-100 ">' +
                                                '              <div class="col col-sm-12 col-md-12 col-lg-12 col-xl-12 card-name">' +
                                                '                  <p class="card-title" id="reflection-' + reflectionId + '">' + reflectionName + '</p>' +
                                                '              </div>' +
                                                '          </div>' +
                                                '      </div>' +
                                                '  </div>' +
                                                '</div>';

                                        participantQuizCardsHtml += oneQuizCard;

                                    });

                                    var participantPhotoDiv = '';

                                    if (oneParticipant.profile_pic_binary != null) {
                                        participantPhotoDiv = '<img src="' + oneParticipant.profile_pic_binary + '" width="100" alt="" class="img-fluid rounded-circle">';
                                    } else {
                                        participantPhotoDiv = '<img src="https://learning-tracker-public-files.s3-ap-southeast-1.amazonaws.com/general/avatar.jpg" width="100" alt="" class="img-fluid rounded-circle">';
                                    }



                                    var participantCardHtml = '<div m-1>' +
                                            '  <div class="row no-gutters participant-row">' +
                                            '      <div class="col col-2 " >' +
                                            '          <div class="participant-avatar-container ">' +
                                            '              <div style="display: block" class="pic-name">' +
                                            participantPhotoDiv +
                                            '              <p class="parName">' + oneParticipant.name + '</p>' +
                                            '              </div>' +
                                            '          </div>' +
                                            '      </div>' +
                                            '      <div class="col col-10">' +
                                            '          <div class="row no-gutters">' +
                                            participantQuizCardsHtml +
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

function subjectChanged() {
    var subject_id = $('#subjectList').val();
    resetCreationOptions();
    $('#upload-section').removeClass('d-none');
    if (subject_id !== "") {
        $.post("/Reflection/reflectionsOfTheSubject", {
            subject_id: subject_id
        },
                function (response) {
                    resetCreationOptions();
                    var response = JSON.parse(response);
                    if (response.status === "Success") {
                        displayConfiguredReflections(response.data);
                    } else {
                        swal({
                            title: "Sorry!",
                            text: response.message,
                            icon: "error",
                        });
                    }
                });
    }
}

function resetCreationOptions() {

    $('#reflection-name').val("");
    if (quill === null) {
        quill = startQuill('description');
    }
    quill.root.innerHTML = "";
    uploadedReferenceFile = [];
    $('#reflection-for').val("Subject");
    $('#reflection-for').trigger('onchange');
    $('#max_score').val("");

}

function displayConfiguredReflections(data) {
    $('#configuredReflectionsSection').html("");
    section = document.getElementById('configuredReflectionsSection');
    $('#configured-reflections-section').removeClass('d-none');

    if (data.length > 0) {
        formatted_data = formatData(data);

        for (index in formatted_data) {
            reflection_group = formatted_data[index];
            group_card_label = document.createElement('label');
            group_card_label.setAttribute('for', 'group-card' + index);
            group_card_label.innerHTML = reflection_group.group;
            section.appendChild(group_card_label);

            group_card = document.createElement('div');
            group_card.setAttribute('id', 'group-card' + index);
            group_card.setAttribute('class', 'reflections-group-card h-100')
            section.appendChild(group_card);

            group_row = document.createElement('div');
            group_row.setAttribute('class', 'row no-gutters');
            group_card.appendChild(group_row);
            (reflection_group.reflections).forEach(function (reflection) {
                reflection_col = document.createElement('div');
                reflection_col.setAttribute('class', 'col col-sm-6 col-md-4 reflection-card-holder');
                reflection_col.setAttribute('id', 'reflection-card-holder-' + reflection.id);
                group_row.appendChild(reflection_col);

                reflection_card = document.createElement('div');
                reflection_card.setAttribute('class', 'card h-100 reflection-card box-shadow');
                reflection_col.appendChild(reflection_card);

                reflection_card_header = document.createElement('div');
                reflection_card_header.setAttribute('class', 'card-header');
                reflection_card.appendChild(reflection_card_header);
                reflection_card_header_row = document.createElement('div');
                reflection_card_header_row.setAttribute('class', 'row no-gutters');
                reflection_card_header.appendChild(reflection_card_header_row);
                reflection_card_header_col1 = document.createElement('div');
                reflection_card_header_col1.setAttribute('class', 'col-11');
                reflection_card_header_row.appendChild(reflection_card_header_col1);
                reflection_name_p =  document.createElement('p');
                reflection_name_p.setAttribute('id',reflection.id);
                reflection_name_p.innerHTML = reflection.name;
                reflection_card_header_col1.appendChild(reflection_name_p);
                reflection_card_header_col2 = document.createElement('div');
                reflection_card_header_col2.setAttribute('class', 'col-1');
                reflection_card_header_row.appendChild(reflection_card_header_col2);
                reflection_delete_icon = document.createElement('i');
                reflection_delete_icon.setAttribute('class', 'fas fa-trash edit-delete-icon');
                reflection_delete_icon.setAttribute('id','trash'+reflection.id);
                reflection_delete_icon.setAttribute('onclick', 'deleteReflection("' + reflection.id + '")');
                reflection_card_header_col2.appendChild(reflection_delete_icon);

                reflection_card_body = document.createElement('div');
                reflection_card_body.setAttribute('class', 'card-body');
                reflection_card_body.innerHTML = reflection.description;
                reflection_card.appendChild(reflection_card_body);

            });
        }

    }


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

function formatData(data) {
    var values = {};
    data.forEach(d => {
        var group;
        var groupIndex;

        var oneValue = {
            id: d.id,
            name: d.name,
            description: d.description,
        };
        if (d.associated_to == "ENTIRE_SUBJECT") {
            group = 'Program';
            groupIndex = 0;
        } else if (d.associated_to == "MODULE") {
            group = 'Module ' + d.module_index + ': ' + d.module_name;
            groupIndex = parseInt(d.module_index) + 1;
        } else if (d.associated_to == "TOPIC") {
            group = 'Module ' + d.module_index + ": " + d.module_name;
            groupIndex = parseInt(d.module_index) + 1;
        } else {
            group = 'Error';
            groupIndex = 2000;
        }
        if (typeof values[groupIndex] === 'undefined') {
            values[groupIndex] = {
                group: group,
                reflections: []
            };
        }

        values[groupIndex]['reflections'].push(oneValue);
    });

    return values;
}

function deleteReflection(reflection_id) {
    swal({
        title: "Are you sure?",
        text: "Once deleted, you will not be able to recover this reflection topic!",
        icon: "warning",
        buttons: true,
        dangerMode: true,

    }).then((willDelete) => {
        if (willDelete) {
            $.post("/Reflection/deleteReflection", {
                reflection_id: reflection_id,
            },
                    function (response) {
                        response = JSON.parse(response);
                        if (response.status === "Success") {
                            subjectChanged();
                            swal({
                                title: "Done!",
                                text: "Reflection topic has been deleted successfully",
                                icon: "success",
                            });
                        } else {
                            swal({
                                title: "Sorry!",
                                text: response.error,
                                icon: "error",
                            });
                        }
                    });
        }
    });
}

function selectedAssociatedTo() {

    var resource_for = $('#reflection-for').val();
    if (resource_for === "Module") {
        var subjectId = $('#subjectList').val();
        $('#select-topic-dropdown').addClass('d-none');
        $('#select-module-dropdown').removeClass('d-none');

        $('#select-module').html("<option>Loading...</option>");

        loadModulesDropdown(subjectId);
    } else if (resource_for === "Topic") {
        var subjectId = $('#subjectList').val();
        $('#select-module-dropdown').removeClass('d-none');
        $('#select-topic-dropdown').removeClass('d-none');

        $('#select-module').html("<option>Loading...</option>");

        loadModulesDropdown(subjectId)
    } else { //Program
        $('#select-module').html("");
        $('#select-topic').html("");
        $('#select-module-dropdown').addClass('d-none');
        $('#select-topic-dropdown').addClass('d-none');
    }
}

function loadModulesDropdown(subjectId) {
    $.post("/Module/getModulesOfTheSubject", {
        data: {
            subject_id: subjectId
        }
    },
            function (response) {
                var modules = JSON.parse(response);
                var modulesHtml = "";
                modules.forEach(function (module) {
                    modulesHtml += '<option value="' + module.module_id + '">' + module.module_index + '. ' + module.module_name + '</option>';
                });
                $('#select-module').html(modulesHtml);

                $('#select-module').trigger('onchange');
            });
}

function loadTopicsDropdown(moduleId) {
    $('#select-topic').html("<option>Loading...</option>");
    $.post("/Topic/getTopicsOfTheModule", {
        data: {
            module_id: moduleId
        }
    },
            function (response) {
                var topics = JSON.parse(response);
                var topicsHtml = "";
                topics.forEach(function (topic) {
                    topicsHtml += '<option value="' + topic.id + '">' + topic.order + '. ' + topic.name + '</option>';
                });
                $('#select-topic').html(topicsHtml);
            });
}

function selectedModule() {
    var resource_for = $('#reflection-for').val();
    if (resource_for === "Topic") {
        var module_id = $('#select-module').val();
        loadTopicsDropdown(module_id);
    }
}

function submitNewReflection() {
    result = validateFields();
    // console.log(response);
    if (result.status == "Error") {
        swal({
            title: "Sorry!",
            text: result.message,
            icon: "error",
        });
    } else {
        // $('#resource-submit-btn').attr('disabled', true);
        $.post("/Reflection/add", {
            data: result.data
        },
                function (response) {
                    response = JSON.parse(response);
                    if (response.status === "Success") {
                        swal({
                            title: "Done!",
                            text: "Reflection added to the course successfully",
                            icon: "success",
                        });
                        resetCreationOptions();
                        subjectChanged();
                    } else {
                        swal({
                            title: "Sorry!",
                            text: response.message,
                            icon: "error",
                        });
                    }
                });
    }
}

function validateFields() {
    var subject_id = $('#subjectList').val();
    var description = quill.root.innerHTML;
    var name = $('#reflection-name').val().trim();
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

    response = maxScore();
    if (response.status === "Error") {
        return response;
    }
    var max_score = response.value;
    return {
        status: "Success",
        data: {
            subject_id: subject_id,
            name: name,
            description: description,
            associated_to: associated_to,
            associated_module_id: associated_module_id,
            associated_topic_id: associated_topic_id,
            max_score: max_score
        }

    }
}

function getAssignmentFor() {
    var resource_for = $('#reflection-for').val();
    var possibleValues = ['Subject', 'Module', 'Topic'];
    if (!possibleValues.includes(resource_for)) {
        return {
            status: "Error",
            message: "Invalid Resource for selection"
        };
    }
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

function maxScore() {
    var max_score = $('#max_score').val();
    if ($.isNumeric(max_score)) {
        return {
            status: "Success",
            value: max_score
        };
    } else {
        return {
            status: "Error",
            message: "points allocated should be a digit!"
        };
    }
}
