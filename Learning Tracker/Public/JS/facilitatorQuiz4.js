/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 function clearContentDiv(){
     $('#submittedQuizSection').empty();
 }

 function loadSubmittedQuiz() {
     var courseId = $('#programList').val();
     var subjectId = $('#subjectList').val();
     $.post("/Quiz/getSubmittedsubjectAndModuleQuiz", {
             courseId: courseId,
             subjectId:subjectId
         },
         function(response) {
             response = JSON.parse(response);

             if (response.status == "Success") {
                 clearContentDiv();
                 var sessions = response.session_wise_assignment_data;
                 if (sessions.length > 0) {
                     sessions.forEach((oneSession) => {
                         var submittedParticipants = oneSession.Participants;
                         if (submittedParticipants.length > 0) {
                             var sessionSectionDivId = "PROGRAM";
                             $("#submittedQuizSection").prepend('<div id="' + sessionSectionDivId + '"></div>');

                             var sessionCardHtml = "";
                             submittedParticipants.forEach((oneParticipant) => {
                                 var participantQuizCardsHtml = "";
                                 var submittedQuiz = oneParticipant.quiz;
                                 participantId = oneParticipant['id'];
                                 submittedQuiz.forEach((quiz) => {
                                     var quizName = quiz['name'];
                                     var quizId = quiz['id'];

                                     var oneQuizCard = '<div class="col-md-4 col-lg-4 small-size-col-margin mb-1 quizCard">' +
                                         '  <div class="card border-light h-100 card-padding-dash shadow" href="#quizModal" id="quizCard" data-toggle="modal" onclick = "getsubmittedQuizQuestions(' + quizId + ',' + participantId + ')"  id="quizCard">' +
                                         '      <div class="card-body">' +
                                         '          <div class="row align-items-center h-100 ">' +
                                         '              <div class="col-sm-2 col-md-3 col-lg-3 col-xl-2 marks" >' +
                                         '                   <p class="Points" style="font-size:11px;margin-top:12px;margin-left:-1px!important;text-align:center!important;">' + quiz['points_earned'] + '/' + quiz['points_allocated'] + '</p>' +
                                         '              </div>' +
                                         '              <div class="col col-sm-10 col-md-9 col-lg-9 col-xl-10 card-name">' +
                                         '                  <p class="card-title" style="margin-left:5px;">' + quizName + '</p>' +
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
                 swal({
                     title: "Sorry!",
                     text: response.message,
                     icon: "error",
                 });
             } else {
                 swal({
                     title: "Sorry!",
                     text: "Received invalid response from server \n We appologise and request you to report this to our technical team",
                     icon: "error",
                 });
             }
         }
     );
     loadSubmittedTopicQuiz()
 }


function loadSubmittedTopicQuiz() {
    var courseId = $('#programList').val();
    var subjectId = $('#subjectList').val();
    $.post("/Quiz/getSubmittedQuiz", {
            courseId: courseId,
            subjectId:subjectId
        },
        function(response) {
            response = JSON.parse(response);

            if (response.status == "Success") {

                var sessions = response.session_wise_assignment_data;
                if (sessions.length > 0) {
                    sessions.forEach((oneSession) => {
                        var submittedParticipants = oneSession.Participants;
                        if (submittedParticipants.length > 0) {
                            var sessionSectionDivId = "session-" + oneSession.session_id;
                            $("#submittedQuizSection").append('<div id="' + sessionSectionDivId + '"></div>');

                            var sessionCardHtml = "";
                            submittedParticipants.forEach((oneParticipant) => {
                                var participantQuizCardsHtml = "";
                                var submittedQuiz = oneParticipant.quiz;
                                participantId = oneParticipant['id'];
                                submittedQuiz.forEach((quiz) => {
                                    var quizName = quiz['name'];
                                    var quizId = quiz['id'];

                                    var oneQuizCard = '<div class="col-md-4 col-lg-4 small-size-col-margin mb-1 quizCard">' +
                                        '  <div class="card border-light h-100 card-padding-dash shadow" href="#quizModal" id="quizCard" data-toggle="modal" onclick = "getsubmittedQuizQuestions(' + quizId + ',' + participantId + ')"  id="quizCard">' +
                                        '      <div class="card-body">' +
                                        '          <div class="row align-items-center h-100 ">' +
                                        '              <div class="col-sm-2 col-md-3 col-lg-3 col-xl-2 marks" >' +
                                        '                   <p class="Points"  style="font-size:11px;margin-top:12px;margin-left:-1px!important;text-align:center!important;">' + quiz['points_earned'] + '/' + quiz['points_allocated'] + '</p>' +
                                        '              </div>' +
                                        '              <div class="col col-sm-10 col-md-9 col-lg-9 col-xl-10 card-name">' +
                                        '                  <p class="card-title" style="margin-left:5px;">' + quizName + '</p>' +
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
                swal({
                    title: "Sorry!",
                    text: response.message,
                    icon: "error",
                });
            } else {
                swal({
                    title: "Sorry!",
                    text: "Received invalid response from server \n We appologise and request you to report this to our technical team",
                    icon: "error",
                });
            }
        }
    );
}
correctCounter = 1
wrongCounter = 1
quill = null;

function subjectChanged() {
    var subject_id = $('#subjectList').val();
    correctOptions(correctCounter);
    wrongOptions(wrongCounter);
    resetCreationOptions();
    $('#upload-section').removeClass('d-none');
    if (subject_id !== "") {
        $.post("/Quiz/quizOfTheSubject", {
                subject_id: subject_id
            },
            function(response) {
                resetCreationOptions();
                var response = JSON.parse(response);
                if (response.status === "Success") {
                    displayConfiguredQuiz(response.data);
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

function displayConfiguredQuiz(data) {
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
            (reflection_group.reflections).forEach(function(reflection) {
                reflection_col = document.createElement('div');
                reflection_col.setAttribute('class', 'col col-sm-6 col-md-4 reflection-card-holder p-1');
                reflection_col.setAttribute('id', 'quiz-card-col-' + reflection.id);
                group_row.appendChild(reflection_col);

                reflection_card = document.createElement('div');
                reflection_card.setAttribute('class', 'card h-100 reflection-card box-shadow');
                reflection_col.appendChild(reflection_card);

                reflection_card_header = document.createElement('div');
                reflection_card_header.setAttribute('class', 'card-body px-0 mx-0 py-0');
                reflection_card_header.setAttribute('id', 'card-body-'+reflection.id);
                reflection_card.appendChild(reflection_card_header);



                reflection_card_header_col2 = document.createElement('div');
                reflection_card_header_col2.setAttribute('style', 'text-align:right;');
                reflection_card_header.appendChild(reflection_card_header_col2);

                quiz_edit_icon = document.createElement('i');
                quiz_edit_icon.setAttribute('class', 'far fa-edit edit-delete-icon');
                quiz_edit_icon.setAttribute('id', 'edit' + reflection.id);
                quiz_edit_icon.setAttribute('onclick', 'editQuiz("' + reflection.id + '")');

                reflection_delete_icon = document.createElement('i');
                reflection_delete_icon.setAttribute('class', 'fas fa-trash edit-delete-icon');
                reflection_delete_icon.setAttribute('id', 'trash' + reflection.id);
                reflection_delete_icon.setAttribute('onclick', 'deleteQuiz("' + reflection.id + '")');

                reflection_card_header_col2.appendChild(quiz_edit_icon);
                reflection_card_header_col2.appendChild(reflection_delete_icon);



                reflection_card_header_row = document.createElement('div');
                reflection_card_header_row.setAttribute('class', 'row no-gutters');
                reflection_card_header.appendChild(reflection_card_header_row);

                reflection_card_header_col1 = document.createElement('div');
                reflection_card_header_col1.setAttribute('class', 'col');
                reflection_card_header_col1.setAttribute('data-toggle', 'modal');
                reflection_card_header_col1.setAttribute('href', '#quizModal');
                reflection_card_header_col1.setAttribute('onclick', 'getQuizDetails('+reflection.id+')');
                reflection_card_header_row.appendChild(reflection_card_header_col1);
                reflection_name_p = document.createElement('p');
                reflection_name_p.setAttribute('id', reflection.id);
                reflection_name_p.innerHTML = reflection.name;
                reflection_card_header_col1.appendChild(reflection_name_p);

                quiz_card_marks_row = document.createElement('div');
                quiz_card_marks_row.setAttribute('class', 'row no-gutters');
                reflection_card_header.appendChild(quiz_card_marks_row);

                quiz_points = document.createElement('p');
                quiz_points.setAttribute('title','Total Points');
                quiz_points.setAttribute('class','total_points');
                quiz_points.innerHTML = 'P-<b>'+reflection.total_points+'</b>';
                quiz_card_marks_row.appendChild(quiz_points);

                quiz_questions = document.createElement('p');
                quiz_questions.setAttribute('title','Total Question');
                quiz_questions.setAttribute('class','total_question');
                quiz_questions.innerHTML = 'Q-<b>'+reflection.total_question+'</b>';
                quiz_card_marks_row.appendChild(quiz_questions);

            });
        }

    }

}

function deleteQuiz(quizId) {
    swal({
        title: "Are you sure?",
        text: "Once deleted, you will not be able to recover this quiz!",
        icon: "warning",
        buttons: true,
        dangerMode: true,

    }).then((willDelete) => {
        if (willDelete) {
            $.post("/Quiz/deleteQuiz", {
                    quizId: quizId,
                },
                function(response) {
                    response = JSON.parse(response);
                    if (response.status === "Success") {
                        subjectChanged();
                        swal({
                            title: "Done!",
                            text: "Quiz has been deleted successfully",
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

function formatData(data) {
    var values = {};
    data.forEach(d => {
        var group;
        var groupIndex;

        var oneValue = {
            id: d.id,
            name: d.name,
            description: d.description,
            total_points: d.total_points,
            total_question: d.total_question
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

function resetCreationOptions(quizId) {
    $('#quiz-name-'+quizId).val("");
    if (quill === null) {
        quill = startQuill('description');
    }
    for (x = 1; x <= correctCounter; x++) {
        $('#Correct' + x).val("");
    }

    for (x = 1; x <= wrongCounter; x++) {
        $('#wrong' + x).val("");
    }
    quill.root.innerHTML = "";
    $('#quiz-for-'+quizId).val("Subject");
    $('#quiz-for-'+quizId).trigger('onchange');
    $('#quizType').val("descriptive");
    $('#quizType').trigger('onchange');
    $('#max_score').val("");

    question = [];
    $('#quiz-option-table-row').addClass('d-none');


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
    fonts.forEach(function(font) {
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
        [{
            'header': [1, 2, 3, 4, 5, 6]
        }],
        [{
            'size': ['small', false, 'large', 'huge']
        }], // custom dropdown
        [{
            'font': fontNames
        }],
        ['bold', 'italic', 'underline', 'strike'], // toggled buttons
        ['blockquote', 'code-block', 'link'],
        [{
            'list': 'ordered'
        }, {
            'list': 'bullet'
        }],
        [{
            'indent': '-1'
        }, {
            'indent': '+1'
        }], // outdent/indent
        [{
            'color': []
        }, {
            'background': []
        }], // dropdown with defaults from theme
        [{
            'align': []
        }],
        [{
            'direction': 'rtl'
        }], // text direction
        ['clean'] // remove formatting button
    ];

    var quill = new Quill('#' + notes_div_id, {
        modules: {
            toolbar: toolbarOptions
        },
        theme: 'snow'
    });
    return quill;
}

function selectedAssociatedTo(quizId = 0, associated_module_id = null, associated_topic_id = null) {

    var resource_for = $('#quiz-for-'+quizId).val();
    if (resource_for === "Module") {
        var subjectId = $('#subjectList').val();
        $('#select-topic-dropdown-'+quizId).addClass('d-none');
        $('#select-module-dropdown-'+quizId).removeClass('d-none');

        $('#select-module-'+quizId).html("<option>Loading...</option>");

        loadModulesDropdown(subjectId, quizId, associated_module_id);

    } else if (resource_for === "Topic") {
        var subjectId = $('#subjectList').val();
        $('#select-module-dropdown-'+quizId).removeClass('d-none');
        $('#select-topic-dropdown-'+quizId).removeClass('d-none');

        $('#select-module-'+quizId).html("<option>Loading...</option>");

        loadModulesDropdown(subjectId, quizId, associated_module_id, associated_topic_id)
    } else { //Program
        $('#select-module-'+quizId).html("");
        $('#select-topic-'+quizId).html("");
        $('#select-module-dropdown-'+quizId).addClass('d-none');
        $('#select-topic-dropdown-'+quizId).addClass('d-none');
    }
}

function loadModulesDropdown(subjectId, quizId, associated_module_id = null, associated_topic_id = null) {
    $.post("/Module/getModulesOfTheSubject", {
            data: {
                subject_id: subjectId
            }
        },
        function(response) {
            var modules = JSON.parse(response);
            var modulesHtml = "";

            modules.forEach(function(module) {
                if(associated_module_id == module.module_id){
                    modulesHtml += '<option value="' + module.module_id + '" selected>' + module.module_index + '. ' + module.module_name + '</option>';
                }
                else{
                    modulesHtml += '<option value="' + module.module_id + '">' + module.module_index + '. ' + module.module_name + '</option>';
                }
            });
            $('#select-module-'+quizId).html(modulesHtml);

            $('#select-module-'+quizId).trigger('onchange');
        });
}

function loadTopicsDropdown(moduleId,quizId,associated_module_id, associated_topic_id) {
    $('#select-topic-'+quizId).html("<option>Loading...</option>");
    $.post("/Topic/getTopicsOfTheModule", {
            data: {
                module_id: moduleId
            }
        },
        function(response) {
            var topics = JSON.parse(response);
            var topicsHtml = "";
            topics.forEach(function(topic) {
                if(associated_topic_id == topic.id){
                    topicsHtml += '<option value="' + topic.id + '" selected>' + topic.order + '. ' + topic.name + '</option>';
                }
                else{
                    topicsHtml += '<option value="' + topic.id + '">' + topic.order + '. ' + topic.name + '</option>';
                }
            });
            $('#select-topic-'+quizId).html(topicsHtml);
        });
}

function selectedModule(quizId = 0,associated_module_id = null, associated_topic_id = null) {
    var resource_for = $('#quiz-for-'+quizId).val();
    if (resource_for === "Topic") {
        var module_id = $('#select-module-'+quizId).val();
        loadTopicsDropdown(module_id,quizId, associated_module_id, associated_topic_id );
    }
}

function addOptions() {
    var type = $('#quizType').val();
    if (type === 'objective') {
        $('.correctLabel').empty();
        $('.wrongLabel').empty();

        $('.option').removeClass('d-none');
        correctLabel = '<label for="Correct" class="correctLabel">Correct Options</label>'
        $('#correctOption').append(correctLabel);
        $('.correctLabel').insertBefore('#Correct1');

        wrongLabel = '<label for="Wrong" class="wrongLabel">Wrong Options</label>'
        $('#wrongOption').append(wrongLabel);
        $('.wrongLabel').insertBefore('#wrong1');

    }
    if (type === 'descriptive') {
        $('.option').addClass('d-none');
    }
}


function correctOptions(correctCounter) {
    correctOption = '<input id="Correct' + correctCounter + '" type="text" class="col-9 mt-2 form-control-sm general-select-in-body select-border" style="position:relative!important;"/>' +
        '<a class="col-2 mt-0 ml-2 duplicateIcon Correct' + correctCounter + '" onclick="correctOptionRemove(' + correctCounter + ')"><i class="fas fa-minus-circle"></i></a>'

    $('#correctOption').append(correctOption);
}

function correctOptionDuplicate() {
    correctCounter += 1
    correctOptions(correctCounter);
}

function correctOptionRemove(correctCounter) {
    $('#Correct' + correctCounter).remove();
    $('.Correct' + correctCounter).remove();
}


function wrongOptions(wrongCounter) {
    wrongOption = '<input id="wrong' + wrongCounter + '" type="text" class="col-9 mt-2 form-control-sm general-select-in-body select-border"/>' +
        '<a class="col-2 mt-0 ml-2 duplicateIcon wrong' + wrongCounter + '" onclick="WrongOptionRemove(' + wrongCounter + ')"><i class="fas fa-minus-circle"></i></a>'

    $('#wrongOption').append(wrongOption);
}

function WrongOptionDuplicate() {
    wrongCounter += 1
    wrongOptions(wrongCounter);
}

function WrongOptionRemove(wrongCounter) {
    $('#wrong' + wrongCounter).remove();
    $('.wrong' + wrongCounter).remove();
}

function clearQuestionModal() {
    correctCounter = 1;
    wrongCounter = 1;
    if (quill === null) {
        quill = startQuill('description');
    }
    for (x = 1; x <= correctCounter; x++) {
        $('#Correct' + x).val("");
    }

    for (x = 1; x <= wrongCounter; x++) {
        $('#wrong' + x).val("");
    }
    quill.root.innerHTML = "";
    $('#max_score').val("");
    $('#quizType').val("descriptive");
    $('#quizType').trigger('onchange');

    $('#correctOption').empty();
    correctOptions(correctCounter);
    $('#wrongOption').empty();
    wrongOptions(wrongCounter);


}

question = [];

function addQuestion() {
    quizId = $('#addQuestionModal').attr('quizId');
    oneQuestion = {};
    oneQuestion['correct_option'] = [];
    oneQuestion['wrong_option'] = [];
    oneQuestion['name'] = quill.root.innerHTML;
    oneQuestion['type'] = $('#quizType').val();
    oneQuestion['points'] = $('#max_score').val();
    if (oneQuestion['type'] === "descriptive") {
        oneQuestion['correct_option'] = null
        oneQuestion['wrong_option'] = null
    }
    if (oneQuestion['type'] === "objective") {
        for (x = 1; x <= correctCounter; x++) {
            if ($('#Correct' + x).val() !== undefined) {
                oneQuestion['correct_option'].push($('#Correct' + x).val());
            }
        }
        for (x = 1; x <= wrongCounter; x++) {
            if ($('#wrong' + x).val() !== undefined) {
                oneQuestion['wrong_option'].push($('#wrong' + x).val());
            }
        }
    }
    if ((oneQuestion['correct_option']) !== null && (oneQuestion['correct_option']).length > 1) {
        oneQuestion['real_type'] = 'MULTIPLE_CHOICE'
    } else if ((oneQuestion['correct_option']) !== null && (oneQuestion['correct_option']).length == 1) {
        oneQuestion['real_type'] = 'OBJECTIVE'
    } else {
        oneQuestion['real_type'] = 'DESCRIPTIVE'
    }
    result = validateQuestionFields(oneQuestion['correct_option'], oneQuestion['wrong_option']);
    if (result.status == "Error") {
        swal({
            title: "Sorry!",
            text: result.message,
            icon: "error",
        });
    } else {
        if(typeof(question[quizId])=='undefined'){

            question[quizId] = [oneQuestion];
        }
        else{

            question[quizId].push(oneQuestion);
        }
        clearQuestionModal();
        quizTable(quizId);
        correctCounter = 1;
        wrongCounter = 1;
    }

}

function closeQuestion() {
    clearQuestionModal();
    $('#addQuestionModal').modal('hide');

}

function quizTable(quizId) {
    table_body_div = document.getElementById('question-tbl-body-'+quizId);
    table_body_div.innerHTML = "";
    questions_to_be_rendered = question[quizId];

    questions_to_be_rendered.forEach(oneQuiz => {

        table_row = document.createElement('tr');
        table_body_div.appendChild(table_row);

        question_col = document.createElement('td');
        question_col.innerHTML = oneQuiz['name'];
        table_row.appendChild(question_col);

        question_type_col = document.createElement('td');
        question_type_col.innerHTML = oneQuiz['type'];
        table_row.appendChild(question_type_col);

        options_col = document.createElement('td');
        table_row.appendChild(options_col);

        if (oneQuiz['type'] !== "descriptive" && oneQuiz['type'] !== "DESCRIPTIVE") {
            options = document.createElement('ul');
            options_col.appendChild(options);

            correct_options = oneQuiz['correct_option'];
            correct_options.forEach(option_value => {
                option = document.createElement('li');
                option.innerHTML = option_value;
                option.setAttribute('class', 'correct-option');
                options.appendChild(option);
            })

            wrong_options = oneQuiz['wrong_option'];
            wrong_options.forEach(option_value => {
                option = document.createElement('li');
                option.innerHTML = option_value;
                option.setAttribute('class', 'wrong-option');
                options.appendChild(option);
            })

        }

        question_points_col = document.createElement('td');
        question_points_col.innerHTML = oneQuiz['points'];
        table_row.appendChild(question_points_col);

        if(quizId != 0){
            question_del_col = document.createElement('td');
            trash_icon  = document.createElement('i');
            trash_icon.setAttribute('class','fas fa-trash edit-delete-icon');
            trash_icon.setAttribute('onclick','deleteQuestion('+oneQuiz['question_id']+','+quizId+')');
            question_del_col.appendChild(trash_icon);
            table_row.appendChild(question_del_col);
        }

    })

    $('#quiz-option-table-row').removeClass('d-none');
    $('#addQuestionModal').modal('hide');
}

// ============================Submit Button===============================

function submitNewQuiz(quizId = 0) {
    result = validateFields(quizId);
    if (result.status == "Error") {
        swal({
            title: "Sorry!",
            text: result.message,
            icon: "error",
        });
    } else {
        $.post("/Quiz/add", {
                data: result.data
            },
            function(response) {
                response = JSON.parse(response);
                if (response.status === "Success") {
                    swal({
                        title: "Done!",
                        text: "Quiz added to the course successfully",
                        icon: "success",
                    });
                    resetCreationOptions(quizId);
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

function validateFields(quizId = 0) {
    var subject_id = $('#subjectList').val();
    var description = quill.root.innerHTML;
    var name = $('#quiz-name-'+quizId).val().trim();
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
    var response = getQuizFor(quizId);
    if (response.status === "Error") {
        return response;
    }

    var associated_to = response.associated_to;
    var associated_module_id = response.associated_module_id;
    var associated_topic_id = response.associated_topic_id;

    response = questionValidation(quizId);
    if (response.status === "Error") {
        return response;
    }
    var questions_of_the_quiz = response.value;
    return {
        status: "Success",
        data: {
            subject_id: subject_id,
            name: name,
            question: questions_of_the_quiz,
            associated_to: associated_to,
            associated_module_id: associated_module_id,
            associated_topic_id: associated_topic_id,
        }

    }
}

function validateQuestionFields(correctOptionList, wrongOptionList) {
    var question = quill.root.innerHTML;
    var points = $('#max_score').val();
    var type = $('#quizType').val();
    if (question === "<p><br></p>") {
        return {
            status: "Error",
            message: "Question Field is Empty!"
        };
    } else if (type === 'objective') {
        for (x = 1; x <= correctCounter; x++) {
            if ($('#Correct' + x).val() === "") {
                return {
                    status: "Error",
                    message: "Correct options Field is Empty!"
                };
            }
            if (correctOptionList.length == 0) {
                return {
                    status: "Error",
                    message: "Add a Correct Option!"
                };
            }
        }
        for (x = 1; x <= wrongCounter; x++) {
            if ($('#wrong' + x).val() === "") {
                return {
                    status: "Error",
                    message: "Wrong options Field is Empty!"
                };
            }
            if (wrongOptionList.length == 0) {
                return {
                    status: "Error",
                    message: "Add a Wrong Option!"
                };
            }

        }
    }
    if (points === "") {
        return {
            status: "Error",
            message: "Points Field is Empty!"
        };
    }

    return {
        status: "Success"
    }

}

function getQuizFor(quizId) {
    var resource_for = $('#quiz-for-'+quizId).val();
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
        var module_id = $('#select-module-'+quizId).val();
        if (isNumeric(module_id)) {
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
        var module_id = $('#select-module-'+quizId).val();
        if (!isNumeric(module_id)) {
            return {
                status: "Error",
                message: "Invalid Module"
            };
        }
        var topic_id = $('#select-topic-'+quizId).val();
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
    }
}

function questionValidation(quizId) {
    if (question[quizId].length == 0) {
        return {
            status: "Error",
            message: "Add atleast one Question!"
        };
    }

    return {
        status: "Success",
        value: question[quizId]
    }
}

// =========QuizSubmission================

function getsubmittedQuizQuestions(questionGroupId,userId) {
    var courseId = $('#programList').val();
    $.post("/Quiz/getQuizQuestions", {
            quizId: questionGroupId,
            user_id: userId,
            courseId: courseId,
    },
            function (response) {
                response = JSON.parse(response);
                if (response.status == "Success") {
                    var quizDetails = response.quizDetails;
                    var quizModalBodyHtml = "";
                    var questionIndex = 1;
                    var quizGroupName = quizDetails.quizGroupName;
                    var quizFooter = "";
                    if (quizDetails['answerStatus'] == "UNANSWERED") {

                        quizDetails.questions.forEach(function (oneQuestion, index) {
                            quizModalBodyHtml += '<div class="text-left mb-3 quiz-question" question-type = "' + oneQuestion.answer_type + '" id = "quiz-question' + oneQuestion.id + '" question-id = "' + oneQuestion.id + '" question = "' + oneQuestion.question + '"><div class="mainQuestion" style="margin-bottom:-17px!important;margin-top:20px;"><div class="questionNum">' + questionIndex + '</div><div class="questionText">' + oneQuestion.question +'</div></div> ';
                            if (oneQuestion.answer_type == "OBJECTIVE") {
                                var objectiveQuizOptions = oneQuestion.quiz_options;
                                objectiveQuizOptions.forEach(function (oneOption, optionIndex) {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="radio"  value = "' + oneOption.id + '" name="quiz-option' + oneQuestion.id + '"/> ' + oneOption.option_value + '</div>';
                                });
                            } else if (oneQuestion.answer_type == "MULTIPLE_CHOICE") {
                                var descriptiveQuizOptions = oneQuestion.quiz_options;
                                descriptiveQuizOptions.forEach(function (oneOption, optionIndex) {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="checkbox" value = "' + oneOption.id + '"  name="multi-choice' + oneQuestion.id + '"> ' + oneOption.option_value + '</div>';
                                });
                            } else if (oneQuestion.answer_type == "DESCRIPTIVE") {
                                quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><textarea class="form-control" placeholder="Enter Answer here*" rows="5"  name="descriptive-answer' + oneQuestion.id + '"></textarea></div>';
                            }
                            questionIndex = questionIndex + 1;
                        });
                        quizFooter += '<button class="btn btn-light" data-dismiss="modal" id = "btn-close" type="button">Close</button><button class="btn dasa-btn" type="submit" id = "quizModalSubmit" quiz-group-id="' + questionGroupId + '" onclick="quizOptionSubmitRequest()">Submit</button>';
                    } else {

                        quizDetails.questions.forEach(function (oneQuestion, index) {
                            quizModalBodyHtml += '<div class="text-left mb-3 quiz-question" question-type = "' + oneQuestion.answer_type + '" id = "quiz-question' + oneQuestion.id + '" question-id = "' + oneQuestion.id + '" question = "' + oneQuestion.question + '"><div class="mainQuestion" style="margin-bottom:-17px!important;margin-top:20px;"><div class="questionNum">' + questionIndex + '</div><div class="questionText">' + oneQuestion.question +'</div></div> ';
                            if (oneQuestion.answer_type == "OBJECTIVE") {
                                var objectiveQuizOptions = oneQuestion.quiz_options;
                                objectiveQuizOptions.forEach(function (oneOption, optionIndex) {
                                    if (oneOption.id === oneQuestion.chosenOptionId) {
                                        quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="radio" value = "' + oneOption.id + '" checked/> ' + oneOption.option_value + '</div>';
                                    } else {
                                        quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="radio" value = "' + oneOption.id + '"  /> ' + oneOption.option_value + '</div>';
                                    }

                                });
                                if (oneQuestion.chosenOptionId == oneQuestion.correctOptionId) {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2 p-1" style="background-color:#d4edda;"><i class="fa fa-check mr-2"></i>CORRECT</div>';
                                } else {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2 p-1" style="background-color:#fce3e4;"><i class="fa fa-times mr-2"></i>WRONG</div>';
                                }
                            } else if (oneQuestion.answer_type == "MULTIPLE_CHOICE") {
                                var multipleQuizOptions = oneQuestion.quiz_options;
                                var chosenOptionIds = oneQuestion.chosenOptionIds;
                                multipleQuizOptions.forEach(function (oneOption, optionIndex) {
                                    if (chosenOptionIds.includes(oneOption.id)) {
                                        quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="checkbox" value = "' + oneOption.id + '" checked/> ' + oneOption.option_value + '</div>';
                                    } else {
                                        quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="checkbox" value = "' + oneOption.id + '" /> ' + oneOption.option_value + '</div>';
                                    }

                                });
                                var allFounded;
                                var a = oneQuestion.chosenOptionIds;
                                var b = oneQuestion.correctOptionIds;
                                // if length is  equal
                                if (a.length == b.length) {
                                    allFounded = b.every(ai => a.includes(ai));
                                }

                                if (allFounded) {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2 p-1" style="background-color:#d4edda;"><i class="fa fa-check mr-2"></i>CORRECT</div>';
                                } else {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2 p-1" style="background-color:#fce3e4;"><i class="fa fa-times mr-2"></i>WRONG</div>';
                                }
                            } else if (oneQuestion.answer_type == "DESCRIPTIVE") {
                                quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><textarea class="form-control"  placeholder="' + oneQuestion.enteredDescriptiveAnswer + '"  rows="5" name="descriptive-answer' + oneQuestion.id + '" readonly></textarea></div>';
                            }
                            questionIndex = questionIndex + 1;
                        });
                        quizFooter += '<button class="btn btn-light" id = "btn-close" data-dismiss="modal" type="button">Close</button>';
                    }
                    document.getElementById("quizModalHeading").innerHTML = quizGroupName;
                    document.getElementById("quizModalBody").innerHTML = quizModalBodyHtml;
                    document.getElementById("quiz-footer").innerHTML = quizFooter;
                } else if (response.status == "Error") {
                    swal({
                        title: "Sorry!",
                        text: response.message,
                        icon: "error",
                    });
                } else {
                    swal({
                        title: "Sorry!",
                        text: "Received invalid response from server \n We appologise and request you to report this to our technical team",
                        icon: "error",
                    });
                }
            }
    );
}
function quizOptionSubmitRequest() {
    var quizAnswers = [];
    var i = 0;
    var questionId;
    var questionType;
    var validation = "passed";
    var quizGroupId = $("#quizModalSubmit").attr('quiz-group-id');
    $('.quiz-question').each(function () {

        if (validation == "passed") {
            questionId = $(this).attr('question-id');
            questionType = $(this).attr('question-type');
            question = $(this).attr('question');
            if (questionType == "OBJECTIVE") {
                if (!$("input[name='quiz-option" + questionId + "']:checked").val()) {
                    swal({
                        title: "Sorry!",
                        text: "Following question is not answered please check\n" + question.replace( /<.*?>/g, '' ),
                        icon: "error",
                    });
                    validation = "failed";
                }
                quizAnswers[i++] = {
                    quizGroupId: quizGroupId,
                    question_id: questionId,
                    question_type: questionType,
                    value: $("input[name='quiz-option" + questionId + "']:checked").val(),
                    question: $('quiz-question' + questionId).text()
                };

            } else if (questionType == "MULTIPLE_CORRECT") {
                if (!$("input:checkbox[name='multi-choice" + questionId + "']").is(":checked")) {
                    swal({
                        title: "Sorry!",
                        text: "Following question is not answered please check\n" + question.replace( /<.*?>/g, '' ),
                        icon: "error",
                    });
                    validation = "failed";
                }
                var multi_choice_answer = [];
                $("input:checkbox[name='multi-choice" + questionId + "']").each(function () {
                    if ($(this).is(":checked")) {
                        multi_choice_answer.push({
                            choice_id: $(this).val(),
                            status: 'checked'
                        });
                    } else {
                        multi_choice_answer.push({
                            choice_id: $(this).val(),
                            status: 'not checked'
                        });
                    }
                });
                quizAnswers[i++] = {
                    quizGroupId: quizGroupId,
                    question_id: questionId,
                    question_type: questionType,
                    value: multi_choice_answer,
                    question: $('quiz-question' + questionId).text()
                }

            } else {
                if (!$("textarea[name='descriptive-answer" + questionId + "']").val()) {
                    swal({
                        title: "Sorry!",
                        text: "Following question is not answered please check\n" + question.replace( /<.*?>/g, '' ),
                        icon: "error",
                    });
                    validation = "failed";
                }
                quizAnswers[i++] = {
                    quizGroupId: quizGroupId,
                    question_id: questionId,
                    question_type: questionType,
                    value: $("textarea[name='descriptive-answer" + questionId + "']").val(),
                    question: $('quiz-question' + questionId).text()
                }

            }
        }
    })

    if (validation == "passed") {
        $.post("/update.php", {
            update: "quizAnswer",
            data: JSON.stringify(quizAnswers)
        },
            function (response) {
                response = JSON.parse(response);
                    if (response.status == "Success") {
                        var quizDetails = response.quizDetails;
                        var quizModalBodyHtml = "";
                        var questionIndex = 1;
                        var quizGroupName = quizDetails.quizGroupName;
                        var quizAnswerStatus = quizDetails.answerStatus;
                        var quizFooter = "";
                        $("#quizModalSubmit").attr("disabled", true);
                        swal({
                            title: "Done!",
                            text: "Your answers has been successfully submitted",
                            icon: "success",
                        });
                        $("#quizModalHeading").html("");
                        $("#quizModalBody").html("");
                        $("#quiz-footer").html("");
                        quizDetails.questions.forEach(function (oneQuestion, index) {
                            quizModalBodyHtml += '<div class="text-left mb-3 quiz-question" question-type = "' + oneQuestion.answer_type + '" id = "quiz-question' + oneQuestion.id + '" question-id = "' + oneQuestion.id + '" question = "' + oneQuestion.question + '">' + questionIndex + '. ' + oneQuestion.question + '<br />';
                            if (oneQuestion.answer_type == "OBJECTIVE") {
                                var objectiveQuizOptions = oneQuestion.quiz_options;
                                objectiveQuizOptions.forEach(function (oneOption, optionIndex) {
                                    if (oneOption.id === oneQuestion.chosenOptionId) {
                                        quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="radio" value = "' + oneOption.id + '" checked/> ' + oneOption.option_value + '</div>';
                                    } else {
                                        quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="radio" value = "' + oneOption.id + '"  /> ' + oneOption.option_value + '</div>';
                                    }

                                });
                                if (oneQuestion.chosenOptionId == oneQuestion.correctOptionId) {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2 p-1" style="background-color:#d4edda;"><i class="fa fa-check mr-2"></i>CORRECT</div>';
                                } else {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2 p-1" style="background-color:#fce3e4;"><i class="fa fa-times mr-2"></i>WRONG</div>';
                                }
                            } else if (oneQuestion.answer_type == "MULTIPLE_CORRECT") {
                                var multipleQuizOptions = oneQuestion.quiz_options;
                                var chosenOptionIds = oneQuestion.chosenOptionIds;
                                multipleQuizOptions.forEach(function (oneOption, optionIndex) {
                                    if (chosenOptionIds.includes(oneOption.id)) {
                                        quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="checkbox" value = "' + oneOption.id + '" checked/> ' + oneOption.option_value + '</div>';
                                    } else {
                                        quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="checkbox" value = "' + oneOption.id + '" /> ' + oneOption.option_value + '</div>';
                                    }

                                });
                                var allFounded;
                                var a = oneQuestion.chosenOptionIds;
                                var b = oneQuestion.correctOptionIds;
                                // if length is  equal
                                if (a.length == b.length) {
                                    allFounded = b.every(ai => a.includes(ai));
                                }

                                if (allFounded) {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2 p-1" style="background-color:#d4edda;"><i class="fa fa-check mr-2"></i>CORRECT</div>';
                                } else {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2 p-1" style="background-color:#fce3e4;"><i class="fa fa-times mr-2"></i>WRONG</div>';
                                }
                            } else if (oneQuestion.answer_type == "DESCRIPTIVE") {
                                quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><textarea class="form-control"  placeholder="' + oneQuestion.enteredDescriptiveAnswer + '"  rows="5"  name="descriptive-answer' + oneQuestion.id + '" readonly></textarea></div>';
                            }
                            questionIndex = questionIndex + 1;
                        });
                        quizFooter += '<button class="btn btn-light" id = "btn-close" data-dismiss="modal" type="button">Close</button>';

                        document.getElementById("quizModalHeading").innerHTML = quizGroupName;
                        document.getElementById("quizModalBody").innerHTML = quizModalBodyHtml;
                        document.getElementById("quiz-footer").innerHTML = quizFooter;
                        if (quizAnswerStatus == "ANSWERED") {
                           var quizTick = '<div class="col col-1 on-submission-tickmark"><i class="icon fa fa-check"></i></div>';
                        }
                        document.getElementById('quizTickMark-'+quizGroupId).innerHTML = quizTick;
                    } else if (response.status == "Error") {
                        swal({
                            title: "Sorry!",
                            text: response.message,
                            icon: "error",
                        });
                } else {
                    swal({
                        title: "Sorry!",
                        text: "Received invalid response from server \n We appologise and request you to report this to our technical team",
                        icon: "error",
                    });
                }
            }
        );
    }

}

$('#quizModal').on("hidden.bs.modal", function(){
    $("#quizModalHeading").html("");
    $("#quizModalBody").html("");
    $("#quiz-footer").html("");
});

function getQuizDetails(quizId){
    var subjectId = $('#subjectList').val();
    $.post("/Quiz/getQuizDetails",{
        quizId : quizId,
        subjectId: subjectId
    },
        function(response){
            response = JSON.parse(response);

            var name = response.name;
            var total_question = response.total_question;
            var total_points = response.total_points;
            var associated_to =response.associated_to;
            var associated_topic = response.associated_topic;
            var associated_module = response.associated_module;
            var questions = response.questions;
            var modalBody = "";

            modal_body = document.getElementById('quizModalBody');
            modal_body.innerHTML = "";

            associated_div = document.createElement('div');
            modal_body.appendChild(associated_div);

            associated_to_div = document.createElement('p');
            associated_to_div.setAttribute('style','margin-left:0px!important;');
            associated_to_div.innerHTML = 'Associated to : <b>'+associated_to+'</b>';
            associated_div.appendChild(associated_to_div);

            if(associated_to === 'TOPIC'){

                associated_topic_name = document.createElement('p');
                associated_topic_name.setAttribute('style','margin-left:0px!important;');
                associated_topic_name.innerHTML = 'Associated topic :<b>'+ associated_topic +'</b>';
                associated_div.appendChild(associated_topic_name);

            }
            else if(associated_to === 'MODULE'){

                associated_module_name = document.createElement('p');
                associated_module_name.setAttribute('style','margin-left:0px!important;');
                associated_module_name.innerHTML = 'Associated module :<b>'+ associated_module +'</b>';
                associated_div.appendChild(associated_module_name);

            }

            total_question_p = document.createElement('p');
            total_question_p.setAttribute('style','margin-left:0px!important;');
            total_question_p.innerHTML = 'Total questions : <b style="color:#d61313;">'+total_question+'</b>';
            associated_div.appendChild(total_question_p);

            total_points_p = document.createElement('p');
            total_points_p.setAttribute('style','margin-left:0px!important;');
            total_points_p.innerHTML = 'Total Points : <b style="color:#d61313;">'+total_points+'</b>';
            associated_div.appendChild(total_points_p);

            viewCreatedQuestionTable();

            document.getElementById("quizModalHeading").innerHTML = name;

            table_body_div = document.getElementById('modal-question-tbl-body');
            table_body_div.innerHTML = "";

            questions.forEach(oneQuiz => {

                table_row = document.createElement('tr');
                table_body_div.appendChild(table_row);

                question_col = document.createElement('td');
                question_col.innerHTML = oneQuiz['name'];
                table_row.appendChild(question_col);

                question_type_col = document.createElement('td');
                question_type_col.innerHTML = oneQuiz['type'];
                table_row.appendChild(question_type_col);

                options_col = document.createElement('td');
                table_row.appendChild(options_col);

                if (oneQuiz['type'] === "OBJECTIVE" || oneQuiz['type'] === "MULTIPLE_CHOICE") {
                    options = document.createElement('ul');
                    options_col.appendChild(options);

                    correct_options = oneQuiz['correct_option'];
                    correct_options.forEach(option_value => {
                        option = document.createElement('li');
                        option.innerHTML = option_value;
                        option.setAttribute('class', 'correct-option');
                        options.appendChild(option);
                    })

                    wrong_options = oneQuiz['wrong_option'];
                    wrong_options.forEach(option_value => {
                        option = document.createElement('li');
                        option.innerHTML = option_value;
                        option.setAttribute('class', 'wrong-option');
                        options.appendChild(option);
                    })

                }

                question_points_col = document.createElement('td');
                question_points_col.innerHTML = oneQuiz['points'];
                table_row.appendChild(question_points_col);

            });

        }
    );
}

function viewCreatedQuestionTable(){
    div_row = document.createElement('div');
    div_row.setAttribute('class','row no-gutters pt-3');
    modal_body.appendChild(div_row);

    div_col= document.createElement('div');
    div_col.setAttribute('class','col');
    div_row.appendChild(div_col);

    table_label = document.createElement('label');
    table_label.setAttribute('for','quiz-option-table');
    table_label.innerHTML = "Questions";
    div_col.appendChild(table_label);

    table = document.createElement('table');
    table.setAttribute('class','table table-bordered mb-0');
    table.setAttribute('id','quiz-option-table');
    div_col.appendChild(table);

    table_head = document.createElement('thead');
    table_head.setAttribute('class','bg-light');
    table.appendChild(table_head);

    table_row = document.createElement('tr');
    table_head.appendChild(table_row);

    table_header_question = document.createElement('th');
    table_header_question.innerHTML = "Question";
    table_row.appendChild(table_header_question);

    table_header_type = document.createElement('th');
    table_header_type.innerHTML = "Type";
    table_row.appendChild(table_header_type);

    table_header_options = document.createElement('th');
    table_header_options.innerHTML = "Options";
    table_row.appendChild(table_header_options);

    table_header_points = document.createElement('th');
    table_header_points.innerHTML = "Points";
    table_row.appendChild(table_header_points);

    table_body = document.createElement('tbody');
    table_body.setAttribute('id','modal-question-tbl-body');
    table.appendChild(table_body);
}


function editQuiz(quizId){
    // remove the content of the clicked quiz card and takes complete row
    $('#quiz-card-col-'+quizId).removeClass('col col-sm-6 col-md-4');
    $('#quiz-card-col-'+quizId).addClass('col-12');
    $('#card-body-'+quizId).empty();

    var subjectId = $('#subjectList').val();
    $.post("/Quiz/getQuizDetails",{
        quizId : quizId,
        subjectId: subjectId
    },
        function(response){
            response = JSON.parse(response);

            var name = response.name;
            var associated_to =response.associated_to;
            var associated_topic_id =response.associated_topic_id;
            var associated_topic = response.associated_topic;
            var associated_module_id = response.associated_module_id;
            var associated_module = response.associated_module;
            question[quizId] = response.questions;

            card_body = document.getElementById('card-body-'+quizId);

            div_main = document.createElement('div');
            div_main.setAttribute('class','section2-cards');
            div_main.setAttribute('id','upload-section-card');
            card_body.appendChild(div_main);

            div_row = document.createElement('div');
            div_row.setAttribute('class','row no-gutters');
            div_main.appendChild(div_row);
            //quiz text box
            quiz_name_div = document.createElement('div');
            quiz_name_div.setAttribute('class','col-sm-6 col-md-3 px-1');
            quiz_name_label = document.createElement('label');
            quiz_name_label.setAttribute('for','quiz-name-'+quizId);
            quiz_name_label.innerHTML = "Name";
            quiz_textbox = document.createElement('input');
            quiz_textbox.setAttribute('id','quiz-name-'+quizId);
            quiz_textbox.setAttribute('type','text');
            quiz_textbox.setAttribute('class','form-control-sm general-select-in-body select-border');
            quiz_textbox.setAttribute('value',name);
            quiz_name_div.append(quiz_name_label);
            quiz_name_div.append(quiz_textbox);

            div_row.appendChild(quiz_name_div);

            associated_with_div = document.createElement('div');
            associated_with_div.setAttribute('class','col-sm-6 col-md-3 px-1');
            div_row.appendChild(associated_with_div);

            associated_with_label = document.createElement('label');
            associated_with_label.setAttribute('for','quiz-for-'+quizId);
            associated_with_label.innerHTML = "Associated with?";
            associated_with_div.appendChild(associated_with_label);

            associated_with_select = document.createElement('select');
            associated_with_select.setAttribute('class','form-control-sm general-select-in-body select-border');
            associated_with_select.setAttribute('id','quiz-for-'+quizId);
            associated_with_select.setAttribute('onchange','selectedAssociatedTo('+quizId+')');
            associated_with_div.appendChild(associated_with_select);

            associated_with_select_option1 = document.createElement('option');
            associated_with_select_option1.setAttribute('value','Subject');
            associated_with_select_option1.innerHTML = "Entire Subject";
            associated_with_select.appendChild(associated_with_select_option1);

            associated_with_select_option2 = document.createElement('option');
            associated_with_select_option2.setAttribute('value','Module');
            associated_with_select_option2.innerHTML = "Specific Module";
            associated_with_select.appendChild(associated_with_select_option2);

            associated_with_select_option3 = document.createElement('option');
            associated_with_select_option3.setAttribute('value','Topic');
            associated_with_select_option3.innerHTML = "Specific Topic";
            associated_with_select.appendChild(associated_with_select_option3);


            select_module_dropdown_div = document.createElement('div');
            select_module_dropdown_div.setAttribute('class','col-sm-6 col-md-3 px-1 d-none');
            select_module_dropdown_div.setAttribute('id','select-module-dropdown-'+quizId);
            div_row.appendChild(select_module_dropdown_div);

            select_module_dropdown_label =  document.createElement('label');
            select_module_dropdown_label.setAttribute('for','select-module-'+quizId);
            select_module_dropdown_label.innerHTML = "Module";
            select_module_dropdown_div.appendChild(select_module_dropdown_label);

            select_module_dropdown = document.createElement('select');
            select_module_dropdown.setAttribute('class','form-control-sm general-select-in-body select-border');
            select_module_dropdown.setAttribute('id','select-module-'+quizId);
            select_module_dropdown.setAttribute('onchange','selectedModule('+quizId+','+associated_module_id+','+associated_topic_id+')');
            select_module_dropdown_div.appendChild(select_module_dropdown);

            select_topic_dropdown_div = document.createElement('div');
            select_topic_dropdown_div.setAttribute('class','col-sm-6 col-md-3 px-1 d-none');
            select_topic_dropdown_div.setAttribute('id','select-topic-dropdown-'+quizId);
            div_row.appendChild(select_topic_dropdown_div);

            select_topic_dropdown_label =  document.createElement('label');
            select_topic_dropdown_label.setAttribute('for','select-topic-'+quizId);
            select_topic_dropdown_label.innerHTML = "Topic";
            select_topic_dropdown_div.appendChild(select_topic_dropdown_label);

            select_topic_dropdown = document.createElement('select');
            select_topic_dropdown.setAttribute('class','form-control-sm general-select-in-body select-border');
            select_topic_dropdown.setAttribute('id','select-topic-'+quizId);
            select_topic_dropdown_div.appendChild(select_topic_dropdown);


            if(associated_to === 'MODULE'){
                var selectedIndex='Module';
                $('#quiz-for-'+quizId).val(selectedIndex);

                selectedAssociatedTo(quizId,associated_module_id);
            }

            else if(associated_to === 'TOPIC'){
                var selectedIndex='Topic';
                $('#quiz-for-'+quizId).val(selectedIndex);

                selectedAssociatedTo(quizId, associated_module_id, associated_topic_id);

            }
            else if(associated_to === 'ENTIRE_SUBJECT'){
                var selectedIndex='Subject';
                $('#quiz-for-'+quizId).val(selectedIndex).change();

                selectedAssociatedTo(quizId);
            }

            //ADD QUESTION BUTTON
            addQuestionButtonDiv = document.createElement('div');
            addQuestionButtonDiv.setAttribute('class','row no-gutters mt-2');
            div_main.appendChild(addQuestionButtonDiv);

            addQuestionButton = document.createElement('button');
            addQuestionButton.setAttribute('class','btn btn-secondary addButton mt-4 ml-1');
            addQuestionButton.setAttribute('id',quizId);
            addQuestionButton.setAttribute('onclick','openAddQuestionModal('+quizId+')');
            addQuestionButton.setAttribute('style','width:20%;height:35px;margin-top:30px!important;');

            plus_icon = document.createElement('i');
            plus_icon.setAttribute('class','fas fa-plus');
            addQuestionButton.innerHTML = "Add Question "
            addQuestionButton.appendChild(plus_icon);

            addQuestionButtonDiv.appendChild(addQuestionButton);

            //DISPLAY QUESTION TABLE
            questionDivRow = document.createElement('div');
            questionDivRow.setAttribute('id','quiz-tbl-'+quizId);
            div_main.appendChild(questionDivRow);

            questionDivCol = document.createElement('div');
            questionDivCol.setAttribute('class','col-12');

            questionDivRow.appendChild(questionDivCol);

             updateButtonDiv = document.createElement('div');
             updateButtonDiv.setAttribute('class','text-right button-div mt-4');

             update_div_row = document.createElement('div');
             update_div_row.setAttribute('class','row no-gutters');
             updateButtonDiv.appendChild(update_div_row);
             div_main.appendChild(updateButtonDiv);

             update_div_col = document.createElement('div');
             update_div_col.setAttribute('class','col');
             update_div_row.appendChild(update_div_col);

             closeButton = document.createElement('button');
             closeButton.setAttribute('class','btn btn-primary text-right border rounded mr-2 btn btn-secondary border closeButton');
             closeButton.setAttribute('type','submit');
             closeButton.setAttribute('id','close-btn');
             closeButton.setAttribute('onclick','closeEdit('+quizId+')');
             closeButton.innerHTML = "Cancel";
             update_div_col.appendChild(closeButton);

             updateButton = document.createElement('button');
             updateButton.setAttribute('class','btn btn-primary text-right border rounded');
             updateButton.setAttribute('type','submit');
             updateButton.setAttribute('style','background-color: #d76f5f;');
             updateButton.setAttribute('id','quiz-update-btn');
             updateButton.setAttribute('onclick','updateQuiz('+quizId+')');
             updateButton.innerHTML = "Update";
             update_div_col.appendChild(updateButton);

            modal_body = document.getElementById('quiz-tbl-'+quizId);
            modal_body.innerHTML = "";

            questionTableHeader(quizId);
            quizTable(quizId);



        }
    );

}

function questionTableHeader(quizId){
    div_row = document.createElement('div');
    div_row.setAttribute('class','row no-gutters pt-3');
    modal_body.appendChild(div_row);

    div_col= document.createElement('div');
    div_col.setAttribute('class','col');
    div_row.appendChild(div_col);

    table_label = document.createElement('label');
    table_label.setAttribute('for','quiz-questions-table-'+quizId);
    table_label.innerHTML = "Questions";
    div_col.appendChild(table_label);

    table = document.createElement('table');
    table.setAttribute('class','table table-bordered mb-0');
    table.setAttribute('id','quiz-questions-table-'+quizId);
    div_col.appendChild(table);

    table_head = document.createElement('thead');
    table_head.setAttribute('class','bg-light');
    table.appendChild(table_head);

    table_row = document.createElement('tr');
    table_head.appendChild(table_row);

    table_header_question = document.createElement('th');
    table_header_question.innerHTML = "Question";
    table_row.appendChild(table_header_question);

    table_header_type = document.createElement('th');
    table_header_type.innerHTML = "Type";
    table_row.appendChild(table_header_type);

    table_header_options = document.createElement('th');
    table_header_options.innerHTML = "Options";
    table_row.appendChild(table_header_options);

    table_header_points = document.createElement('th');
    table_header_points.innerHTML = "Points";
    table_row.appendChild(table_header_points);

    table_header_points = document.createElement('th');
    table_header_points.innerHTML = "Action";
    table_row.appendChild(table_header_points);

    table_body = document.createElement('tbody');
    table_body.setAttribute('id','question-tbl-body-'+quizId);
    table.appendChild(table_body);
}

function deleteQuestion(questionId,quizId){
    modified_Question=[]
    swal({
        title: "Are you sure?",
        text: "Once deleted, you will not be able to recover this question!",
        icon: "warning",
        buttons: true,
        dangerMode: true,

    }).then((willDelete) => {
        if (willDelete) {
            question[quizId].forEach((oneQuestion, i) => {
                if(questionId == oneQuestion['question_id']){
                    question[quizId].splice(i,1);
                }
            });
            quizTable(quizId);
        }
    });
}
function openAddQuestionModal(quizId=null){
    if(quizId == null){
        quizId = 0;
    }
    $('#addQuestionModal').attr('quizId',quizId);
    $('#addQuestionModal').modal('show');
}
function closeEdit(quizId){
    $('#card-body-'+quizId).empty();
    $('#quiz-card-col-'+quizId).addClass('col col-sm-6 col-md-4');
    $('#quiz-card-col-'+quizId).removeClass('col-12');
    subjectChanged();
}

function updateQuiz(quizId = 0) {
    result = validateFields(quizId);
    subjectId = $('#subjectList').val();
    if (result.status == "Error") {
        swal({
            title: "Sorry!",
            text: result.message,
            icon: "error",
        });
    } else {
        $.post("/Quiz/updateQuiz", {
                data: result.data,
                quizId:quizId,
                subjectId:subjectId
            },
            function(response) {
                response = JSON.parse(response);
                if (response.status === "Success") {
                    swal({
                        title: "Done!",
                        text: "Quiz Updated successfully",
                        icon: "success",
                    });
                    resetCreationOptions(quizId);
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
