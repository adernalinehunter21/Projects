

$('#schedule-start-date-time').datetimepicker({
    format: 'dd mmm yyyy HH:MM',
    footer: true,
    modal: true,
    icons: {
        rightIcon: '<i class="material-icons">date_range</i>'
    }
});

$('#session-duration').timepicker({
    format: 'HH:MM',
    mode: '24hr',
    footer: true,
    modal: true,
    icons: {
        rightIcon: '<i class="fas fa-hourglass-start p-1"></i>'
    }
});

var session_topics = {};
function selectedProgram() {
    resetAndDisplayCreateNewSessionPanel();
    var courseId = $('#programList').val();
    var subjectId = $('#subjectList').val();
    $.post("/Session/getSessionsWithDetails",
            {
                course_id: courseId,
                subject_id: subjectId
            },
            function (response) {

                var responseArray = JSON.parse(response);

                $('#mainContentSection').html("");
                responseArray.forEach(function (session) {
                    main_section_div = document.getElementById('mainContentSection');

                    section = document.createElement('div');
                    section.setAttribute("id", "session-" + session.session_id);
                    section.setAttribute("class", "mb-4");
                    main_section_div.appendChild(section);
                    displayOneSessionDetails(section, session);
                });

                $('#upload-section').removeClass('d-none');
            }
    );
}

function displayOneSessionDetails(section, session) {


    section_heading = document.createElement('div');
    section_heading.setAttribute("class", "section-card-heading");
    section_heading.innerHTML = 'Session ' + session.session_index + ': ' + session.session_name;
    section.appendChild(section_heading);

    section_card = document.createElement('div');
    section_card.setAttribute("id", "section-card-" + session.session_id);
    section_card.setAttribute('class', 'card section-card');
    section.appendChild(section_card);

    first_row = document.createElement('div');
    first_row.setAttribute('class', 'row no-gutters p-3 flex-column-reverse flex-md-row');
    section_card.appendChild(first_row);

    schedules_col = document.createElement('div');
    schedules_col.setAttribute('class', 'col-xs-12 col-sm-11');
    first_row.appendChild(schedules_col);

    icon_col = document.createElement('div');
    icon_col.setAttribute('class', 'col-xs-12 col-sm-1');
    first_row.appendChild(icon_col);

    edit_delete_icons = document.createElement('div');
    edit_delete_icons.setAttribute('style', 'float: right');
    icon_col.appendChild(edit_delete_icons);

    edit_icon = document.createElement('i');
    edit_icon.setAttribute('class', 'fas fa-edit edit-delete-icon');
    edit_icon.setAttribute('id', "edit" + session.session_index);
    edit_icon.setAttribute('onClick', 'edit_session(' + session.session_index + ',' + session.session_id + ')');
    edit_delete_icons.appendChild(edit_icon);

    delete_icon = document.createElement('i');
    delete_icon.setAttribute('class', 'far fa-trash-alt edit-delete-icon');
    delete_icon.setAttribute('id', "trash" + session.session_index);
    delete_icon.setAttribute('onClick', 'delete_session(' + session.session_index + ')');
    edit_delete_icons.appendChild(delete_icon);

    schedule_row = document.createElement('div');
    schedule_row.setAttribute('class', 'row no-gutters');
    schedules_col.appendChild(schedule_row);

    date_col = document.createElement('div');
    date_col.setAttribute('class', 'col-8 col-sm-3 p-0');
    date_col.setAttribute('tittle', 'Date of the session as per your current timezone');
    date_col.innerHTML = '<i class="far fa-calendar-alt"></i> ' + session.date;
    schedule_row.appendChild(date_col)

    time_col = document.createElement('div');
    time_col.setAttribute('class', 'col-4 col-sm-2');
    time_col.setAttribute('tittle', 'Session start time as per your current timezone');
    time_col.innerHTML = '<i class="far fa-clock"></i> ' + session.time;
    schedule_row.appendChild(time_col)

    duration_col = document.createElement('div');
    duration_col.setAttribute('class', 'col-4 col-sm-3');
    duration_col.setAttribute('tittle', 'Duration of the session');
    duration_col.innerHTML = '<i class="fas fa-stopwatch"></i> ' + session.duration;
    schedule_row.appendChild(duration_col)

    if (session.meeting_link != "") {
        meeting_link_col = document.createElement('div');
        meeting_link_col.setAttribute('class', 'col-8 col-sm-4');
        meeting_link_col.setAttribute('link', session.meeting_link);
        meeting_link_col.setAttribute('tittle', 'Click to copy the link');
        schedule_row.appendChild(meeting_link_col);

        meeting_link_btn = document.createElement('button');
        meeting_link_btn.setAttribute("id", "meeting-link-btn-" + session.session_id);
        meeting_link_btn.setAttribute("type", "button");
        meeting_link_btn.setAttribute("class", "btn btn-outline-secondary");
        meeting_link_btn.setAttribute("onclick", "copyMeetingLinkToClipboard(" + session.session_id + ", '" + session.meeting_link + "')");
        meeting_link_btn.innerHTML = "Copy the Meeting link";
        meeting_link_col.appendChild(meeting_link_btn);

    }

    topics = session.topics;
    if (topics.length > 0) {

        topics_row = document.createElement('div');
        topics_row.setAttribute('class', 'row no-gutters p-3');
        section_card.appendChild(topics_row);

        topics_col = document.createElement('div');
        topics_col.setAttribute('class', 'col');
        topics_row.appendChild(topics_col);

        topics_heading = document.createElement('label');
        topics_heading.setAttribute('for', 'session-topics-' + session.session_id);
        topics_col.appendChild(topics_heading);
        topics_heading.innerHTML = "Topics";

        topics_table = document.createElement('table');
        topics_table.setAttribute('id', 'session-topics-' + session.session_id);
        topics_table.setAttribute('class', 'table table-bordered mb-0');
        topics_col.appendChild(topics_table);

        topicstable_body = document.createElement('tbody');
        topics_table.appendChild(topicstable_body);


        previous_module = null;
        module_row_index = -1;
        topics.forEach(function (topic, index) {
            if (previous_module !== topic.module_index) {
                previous_module = topic.module_index;
                module_row_index++;

                topicstable_row = document.createElement('tr');
                topicstable_body.appendChild(topicstable_row);

                if (topic.type === "subject_topic") {
                    module_html = topic.module_index + ". " + topic.module_name;
                } else if (topic.type === "general") {
                    module_html = "General"
                } else {
                    module_html = "";//This shoule never happen
                }
                topicstable_col1 = document.createElement('td');
                topicstable_row.appendChild(topicstable_col1);
                topicstable_col1.innerHTML = module_html;

                topicstable_col2 = document.createElement('td');
                topicstable_row.appendChild(topicstable_col2);

                topic_list = document.createElement('ul');
                topic_list.setAttribute('id', 'topic-list-' + session.session_id + module_row_index);
                topicstable_col2.appendChild(topic_list);
                tipic_list_item = document.createElement('li');
                topic_list.appendChild(tipic_list_item);
                tipic_list_item.innerHTML = topic.name;
            } else {
                topic_list = document.getElementById('topic-list-' + session.session_id + module_row_index);
                tipic_list_item = document.createElement('li');
                topic_list.appendChild(tipic_list_item);
                tipic_list_item.innerHTML = topic.name;
            }

        });
    }
    $('#main-content-section').removeClass('d-none');
}

function copyMeetingLinkToClipboard(session_id, text) {
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val(text).select();
    document.execCommand("copy");
    $temp.remove();

    $('#meeting-link-btn-' + session_id).attr('tittle', 'Copied the link to clipboard');
}

function delete_session(session_index) {
    swal({
        title: "Are you sure?",
        text: "Once deleted, you will not be able to recover this Session " + session_index,
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
            .then((willDelete) => {
                if (willDelete) {
                    course_id = $('#programList').val();
                    $.post("/Session/deleteSession", {
                        course_id: course_id,
                        session_index: session_index
                    },
                            function (response) {
                                response = JSON.parse(response);
                                if (response.status === "Success") {
                                    selectedProgram();
                                    swal({
                                        title: "Done!",
                                        text: "Session " + session_index + " has been deleted successfully",
                                        icon: "success",
                                    });
                                } else {
                                    swal({
                                        title: "Sorry!",
                                        text: response.error,
                                        icon: "error",
                                    });
                                }
                            })
                }
            });
}

function selectedRemoteMeetingCreatedDD(session_id = "") {
    var meeting_link_availability = $('#remote-meeting-created-dropdown' + session_id).val();
    if (meeting_link_availability === 'No') {
        $('#meeting-link-row' + session_id).addClass('d-none');
    } else {
        $('#meeting-link-row' + session_id).removeClass('d-none');
}
}

function openModalWithSubjectModulesLoaded(session_id = null) {
    var subject_id = $('#subjectList').val();
    $.post("/Module/getModulesOfTheSubject",
            {
                data: {
                    subject_id: subject_id
                }
            },
            function (response) {
                if (session_id === null) {
                    $('#add-topic-modal-btn').attr('onclick', 'addSelectedTopics()');
                } else {
                    $('#add-topic-modal-btn').attr('onclick', 'addSelectedTopics("' + session_id + '")');
                }

                module_drop_down = document.getElementById('module-drop-down');
                module_drop_down.innerHTML = "";
                option = document.createElement('option');
                option.setAttribute('value', "");
                option.innerHTML = "Select";
                module_drop_down.appendChild(option);

                var responseArray = JSON.parse(response);
                responseArray.forEach(function (module) {
                    option = document.createElement('option');
                    option.setAttribute('value', module.module_id);
                    option.setAttribute('module_index', module.module_index);
                    option.setAttribute('module_name', module.module_name);
                    option.innerHTML = module.module_index + ". " + module.module_name;
                    module_drop_down.appendChild(option);
                });
                $('#topics-list').html("");
                $('#topics-list-row').addClass('d-none');
                $('#add-topic-modal').modal('show');
            }
    );
}

$('#module-drop-down').change(function () {
    selected_module_id = $('#module-drop-down').val();
    if (selected_module_id === "") {
        $('#topics-list').html("");
        $('#topics-list-row').addClass('d-none');
    } else {
        $.post("/Topic/getTopicsOfTheModule",
                {
                    data: {
                        module_id: selected_module_id
                    }
                },
                function (response) {
                    topics_list = document.getElementById('topics-list');
                    topics_list.innerHTML = "";//Start with the clean div

                    var responseArray = JSON.parse(response);
                    responseArray.forEach(function (topic) {
                        topic_div = document.createElement('div');
                        topic_div.setAttribute('class', 'checkbox');
                        topics_list.appendChild(topic_div);

                        label_div = document.createElement('label');
                        topic_div.appendChild(label_div);

                        input_div = document.createElement('input');
                        input_div.setAttribute('type', 'checkbox');
                        input_div.setAttribute('value', topic.id);
                        label_div.appendChild(input_div);

                        span_div = document.createElement('span');
                        span_div.setAttribute('id', 'topic-span-' + topic.id)
                        span_div.innerHTML = " " + topic.name;
                        label_div.appendChild(span_div);

                    });
                    $('#topics-list-row').removeClass('d-none');
                });
    }
});

function addSelectedTopics(session_id = "") {
    topic_type = $('#topic-type-drop-down').val();
    if (topic_type === "general") {
        general_topic = $('#general-topic').val();
        general_topic = general_topic.trim();
        if (general_topic === "") {
            swal({
                title: "Sorry!",
                text: "Please enter the topic to add",
                icon: "error",
            });
        } else {//session_topics['s-'+session_id] = [];
            session_topics['s-' + session_id].push({
                id: "",
                name: general_topic,
                type: "general",
                module_index: "",
                module_name: "General"
            });
            $('#general-topic').val("");
            displayTopicsTable(session_id, session_topics['s-' + session_id]);
        }
    } else if (topic_type === "subject") {

        selected_module_index = $('#module-drop-down option:selected').attr('module_index');
        selected_module_name = $('#module-drop-down option:selected').attr('module_name');

        if (selected_module_index === "") {
            swal({
                title: "Sorry!",
                text: "Please select the Module and then choose one or more Topics to add",
                icon: "error",
            });
        } else {
            var i = 0;
            $("#topics-list input[type='checkbox']").each(function () {
                if (this.checked) {
                    topic_id = $(this).val();
                    topic = $('#topic-span-' + topic_id).html();
                    session_topics['s-' + session_id].push({
                        id: topic_id,
                        type: "subject_topic",
                        name: topic,
                        module_index: selected_module_index,
                        module_name: selected_module_name

                    });
                    i++;
                }
            });

            if (i === 0) {
                swal({
                    title: "Sorry!",
                    text: "Please select select at-least one topic to add",
                    icon: "error",
                });
            } else {
                displayTopicsTable(session_id, session_topics['s-' + session_id]);

            }
        }
}
}

function displayTopicsTable(session_id = "", topics) {
    session_topic_table_body = document.getElementById('session-topic-table-body' + session_id);
    session_topic_table_body.innerHTML = "";
    module_row_index = null;
    previous_module_index = null;
    cont_of_topics = topics.length;
    $.each(topics, function (index, topic) {
        if (topic.module_index !== previous_module_index) {
            previous_module_index = topic.module_index;
            module_row_index++;
            //first=row
            row = document.createElement('tr');
            session_topic_table_body.appendChild(row);

            module_col = document.createElement('td');
            if (topic.type === "subject_topic") {
                module_col.innerHTML = topic.module_index + ". " + topic.module_name;
            } else if (topic.type === "general") {
                module_col.innerHTML = "General";
            } else {
                module_col.innerHTML = "";
            }

            row.appendChild(module_col);

            topic_col = document.createElement('td');
            row.appendChild(topic_col);

            topic_list = document.createElement('ul');
            topic_list.setAttribute('id', 'topics-row' + session_id + module_row_index);
            topic_col.appendChild(topic_list);

            previous_module_id = topic.module_id;
        } else if (topic.module_id === previous_module_id) {

            topic_list = document.getElementById('topics-row' + session_id + module_row_index);

        }

        delete_topic_icon_html = ' <i class="fas fa-times edit-delete-icon" onclick="removeTopic(' + index + ', ' + session_id + ')" title="Remove this topic"></i>';
        move_topic_up_icon_html = ' <i class="fas fa-chevron-up edit-delete-icon" onclick="moveTopicUp(' + index + ', ' + session_id + ')" title="Move this topic up"></i>';
        move_topic_down_icon_html = ' <i class="fas fa-chevron-down edit-delete-icon" onclick="moveTopicDown(' + index + ', ' + session_id + ')" title="Move this topic down"></i>';

        topic_item = document.createElement('li');
        topic_item.setAttribute('id', 'topic-item-' + topic.id);
        topi_item_html = topic.name + delete_topic_icon_html;
        if (index > 0) {
            topi_item_html += move_topic_up_icon_html;
        }
        if (index < cont_of_topics - 1) {
            topi_item_html += move_topic_down_icon_html;
        }
        topic_item.innerHTML = topi_item_html;
        topic_list.appendChild(topic_item);

    });
    $('#session-topic-table-row' + session_id).removeClass('d-none');
    $('#add-topic-modal').modal('hide');
}

function removeTopic(topic_index, session_id = "") {
    session_topics['s-' + session_id].splice(topic_index, 1);
    displayTopicsTable(session_id, session_topics['s-' + session_id]);
}

function moveTopicUp(topic_index, session_id = "") {
    if (topic_index <= 0) {
        swal({
            title: "Sorry!",
            text: "There are no more topics above to move",
            icon: "error",
        });
    } else {
        above_topic_index = topic_index - 1;
        if (typeof session_topics['s-' + session_id][above_topic_index] == 'undefined') {
            swal({
                title: "Sorry!",
                text: "Not able to move this topic up",
                icon: "error",
            });
        } else {
            current_topic = session_topics['s-' + session_id][topic_index];
            above_topic = session_topics['s-' + session_id][above_topic_index];
            session_topics['s-' + session_id][above_topic_index] = current_topic;
            session_topics['s-' + session_id][topic_index] = above_topic;
            displayTopicsTable(session_id, session_topics['s-' + session_id]);
        }
}
}

function moveTopicDown(topic_index, session_id = "") {
    count_of_topics = session_topics['s-' + session_id].length;
    index_of_last_topic = count_of_topics - 1;

    if (topic_index >= index_of_last_topic) {
        swal({
            title: "Sorry!",
            text: "There are no more topics below to move",
            icon: "error",
        });
    } else {
        below_topic_index = topic_index + 1;
        if (typeof session_topics['s-' + session_id][below_topic_index] == 'undefined') {
            swal({
                title: "Sorry!",
                text: "Not able to move this topic down",
                icon: "error",
            });
        } else {
            current_topic = session_topics['s-' + session_id][topic_index];
            below_topic = session_topics['s-' + session_id][below_topic_index];
            session_topics['s-' + session_id][below_topic_index] = current_topic;
            session_topics['s-' + session_id][topic_index] = below_topic;
            displayTopicsTable(session_id, session_topics['s-' + session_id]);
        }
}
}

function submitNewSession() {
    validation = validateSessionCreationOrEditForm();
    if(validation.status != "Success"){
        swal({
            title: "Sorry!",
            text: validation.error,
            icon: "error",
        });     

    }else if(validation.status === "Success") {
            data = validation.data;
            if(data.send_notification === true){
            var message_to_user = "Once submitted, session will be created and all learners of the course will be notified";
            }
            if(data.send_notification === false){
                message_to_user = "Once submitted, the learners will not be notified of the new session.\n\nYou will have to notify the learners later.";
            }

        swal({
            title: "Are you sure?",
            text: message_to_user,
            icon: "info",
            buttons: true,
            dangerMode: true,
        })
        .then((update) => {
            if (update) {
                $.post("/Session/createNewSession",
                {
                    data: validation.data,
                    notify_user_flag: data.send_notification
                },
                function (response) {
                    var response = JSON.parse(response);
                    if (response['status'] === "Success") {
                        swal({
                            title: "Done!",
                            text: "New session has been created",
                            icon: "success",
                        });
                    resetAndDisplayCreateNewSessionPanel();
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


    }


function validateSessionCreationOrEditForm(session_id = "") {
     
    courseId = $('#programList').val();
    subjectId = $('#subjectList').val();

    session_index = parseInt($('#session-index' + session_id).val());
    if (session_index === "") {
        return error("Invalid Session Index. It needs to be a number");
    }

    session_name = $('#session-name' + session_id).val().trim();
    if (session_name === "") {
        return error("Please enter the Name for the Session");
    }

    session_start_date_time = $('#schedule-start-date-time' + session_id).val().trim();
    if (session_start_date_time === "") {
        return error("Please select/enter the session date and start time like 04 Jan 2020 09:30");
    }

    session_duration = $('#session-duration' + session_id).val().trim();
    if (session_duration === "") {
        return error("Please select/enter the time duration of the session like 03:30");
    }

    remote_meeting_availability = $('#remote-meeting-created-dropdown' + session_id).val();
    remote_meeting_link = "";
    if (remote_meeting_availability === "Yes") {
        remote_meeting_link = $('#session-meeting-link' + session_id).val().trim();
        if (remote_meeting_link === "") {
            return error("Please enter the meeting Link. If not available, please select the availability as No");
        }
    }

    if (session_topics['s-' + session_id].length < 1) {
        return error("Please add atleast one topic to proceed");
    }
    var checkbox_value = $('#send-notification-checkbox').is(':checked');
    return {
        status: "Success",
        data: {
            course_id: courseId,
            subject_id: subjectId,
            session_id: session_id,
            session_index: session_index,
            session_name: session_name,
            session_start_date_time: session_start_date_time,
            session_duration: session_duration,
            remote_meeting_availability: remote_meeting_availability,
            remote_meeting_link: remote_meeting_link,
//            topic_ids: topic_ids
            topics: session_topics['s-' + session_id],
            send_notification: checkbox_value
        }
    };
}

function edit_session(session_index, session_id) {
    course_id = $('#programList').val();
    $.post("/Session/getSessionDetailsForEditing",
            {
                data: {
                    course_id: course_id,
                    session_index: session_index
                }
            },
            function (response) {
                response = JSON.parse(response);
                if (response.status === "Success") {
                    data = response.data;
                    $('#session-' + session_id + " >.section-card-heading").html("Editing");
                    section_card = document.getElementById('section-card-' + session_id);
                    section_card.innerHTML = "";

                    first_row = document.createElement('div');
                    first_row.setAttribute('class', 'row no-gutters p-3');
                    section_card.appendChild(first_row);

                    index_col = document.createElement('div');
                    index_col.setAttribute('class', 'col-sm-3 col-md-2 px-1');
                    first_row.appendChild(index_col);
                    index_label = document.createElement('label');
                    index_label.setAttribute('for', 'session-index' + session_id);
                    index_label.innerHTML = "Index";
                    index_col.appendChild(index_label);
                    index_input = document.createElement('input');
                    index_input.setAttribute('type', 'number');
                    index_input.setAttribute('id', 'session-index' + session_id);
                    index_input.setAttribute('class', 'form-control-sm general-select-in-body select-border p-1');
                    index_input.setAttribute('min', "1");
                    index_input.value = session_index;
                    index_col.appendChild(index_input);

                    name_col = document.createElement('div');
                    name_col.setAttribute('class', 'col-sm-9 col-md-10 px-1');
                    first_row.appendChild(name_col);
                    name_label = document.createElement('label');
                    name_label.setAttribute('for', 'session-name' + session_id);
                    name_label.innerHTML = "Name";
                    name_col.appendChild(name_label);
                    name_input = document.createElement('input');
                    name_input.setAttribute('id', 'session-name' + session_id);
                    name_input.setAttribute('type', 'text');
                    name_input.setAttribute('class', 'form-control-sm general-select-in-body select-border nameTextBox');
                    name_input.value = data.session_name;
                    name_col.appendChild(name_input);

                    second_row = document.createElement('div');
                    second_row.setAttribute('class', 'row no-gutters px-3 mt-2');
                    section_card.appendChild(second_row);
                    schedule_col = document.createElement('div');
                    schedule_col.setAttribute('class', 'col-sm-6 col-md-4 px-1');
                    second_row.appendChild(schedule_col);
                    schedule_label = document.createElement('label');
                    schedule_label.setAttribute('for', 'schedule-start-date-time' + session_id);
                    schedule_label.innerHTML = "Start Date Time";
                    schedule_col.appendChild(schedule_label);
                    schedule_input = document.createElement('input');
                    schedule_input.setAttribute('id', 'schedule-start-date-time' + session_id);
                    schedule_input.setAttribute('class', 'form-control-sm general-select-in-body select-border p-1');
                    schedule_col.appendChild(schedule_input);
                    $('#schedule-start-date-time' + session_id).datetimepicker({
                        format: 'dd mmm yyyy HH:MM',
                        value: data.date + ' ' + data.time,
                        footer: true,
                        modal: true,
                        icons: {
                            rightIcon: '<i class="material-icons">date_range</i>'
                        }
                    });
                    duration_col = document.createElement('div');
                    duration_col.setAttribute('class', 'col-sm-6 col-md-4 px-1');
                    second_row.appendChild(duration_col);
                    duration_label = document.createElement('label');
                    duration_label.setAttribute('for', 'session-duration' + session_id);
                    duration_label.innerHTML = "Duration";
                    duration_col.appendChild(duration_label);
                    duration_input = document.createElement('input');
                    duration_input.setAttribute('id', 'session-duration' + session_id);
                    duration_input.setAttribute('class', 'form-control-sm general-select-in-body select-border p-1');
                    duration_col.appendChild(duration_input);
                    $('#session-duration' + session_id).timepicker({
                        format: 'HH:MM',
                        mode: '24hr',
                        footer: true,
                        modal: true,
                        value: data.duration,
                        icons: {
                            rightIcon: '<i class="fas fa-hourglass-start p-1"></i>'
                        }
                    });
                    meeting_link_choice_col = document.createElement('div');
                    meeting_link_choice_col.setAttribute('class', 'col-sm-6 col-md-4 px-1');
                    second_row.appendChild(meeting_link_choice_col);
                    meeting_link_choice_label = document.createElement('label');
                    meeting_link_choice_label.setAttribute('for', 'remote-meeting-created-dropdown' + session_id);
                    meeting_link_choice_label.innerHTML = "Remote Meeting Link Available?";
                    meeting_link_choice_col.appendChild(meeting_link_choice_label);
                    meeting_link_choice_input = document.createElement('select');
                    meeting_link_choice_input.setAttribute('id', 'remote-meeting-created-dropdown' + session_id);
                    meeting_link_choice_input.setAttribute('class', 'form-control-sm general-select-in-body select-border');
                    meeting_link_choice_input.setAttribute('onchange', 'selectedRemoteMeetingCreatedDD("' + session_id + '")');
                    meeting_link_choice_col.appendChild(meeting_link_choice_input);
                    choice_yes = document.createElement('option');
                    choice_yes.value = "Yes";
                    choice_yes.innerHTML = "Yes";
                    meeting_link_choice_input.appendChild(choice_yes);
                    choice_no = document.createElement('option');
                    choice_no.value = "No";
                    choice_no.innerHTML = "No";
                    meeting_link_choice_input.appendChild(choice_no);

                    third_row = document.createElement('div');
                    third_row.setAttribute('id', 'meeting-link-row' + session_id);
                    third_row.setAttribute('class', 'row no-gutters px-3 mt-2');
                    section_card.appendChild(third_row);
                    meeting_link_col = document.createElement('div');
                    meeting_link_col.setAttribute('class', 'col px-1');
                    third_row.appendChild(meeting_link_col);
                    meeting_link_label = document.createElement('label');
                    meeting_link_label.setAttribute('for', 'session-meeting-link' + session_id);
                    meeting_link_label.innerHTML = "Meeting Link";
                    meeting_link_col.appendChild(meeting_link_label);
                    meeting_link_input = document.createElement('input');
                    meeting_link_input.setAttribute('id', 'session-meeting-link' + session_id);
                    meeting_link_input.setAttribute('type', 'text');
                    meeting_link_input.setAttribute('class', 'form-control-sm general-select-in-body select-border');
                    meeting_link_input.value = data.meeting_link;
                    meeting_link_col.appendChild(meeting_link_input);
                    if (data.meeting_link === "") {
                        choice_no.setAttribute('selected', true);
                        third_row.setAttribute('class', 'd-none');
                    } else {
                        choice_yes.setAttribute('selected', true);
                    }

                    fourth_row = document.createElement('div');
                    fourth_row.setAttribute('class', 'row no-gutters px-3 mt-2');
                    section_card.appendChild(fourth_row);
                    topic_btn_col = document.createElement('div');
                    topic_btn_col.setAttribute('class', 'col col-sm-4 col-md-2 px-1');
                    fourth_row.appendChild(topic_btn_col);
                    topic_btn = document.createElement('button');
                    topic_btn.setAttribute('class', 'btn btn-secondary');
                    topic_btn.setAttribute('onclick', 'openModalWithSubjectModulesLoaded("' + session_id + '")');
                    topic_btn.innerHTML = '<i class="fas fa-plus"></i> Add Topics';
                    topic_btn_col.appendChild(topic_btn);

                    fifth_row = document.createElement('div');
                    fifth_row.setAttribute('class', 'row no-gutters p-3 d-none');
                    fifth_row.setAttribute('id', 'session-topic-table-row' + session_id);
                    section_card.appendChild(fifth_row);
                    topic_tbl_col = document.createElement('div');
                    topic_tbl_col.setAttribute('class', 'col px-1');
                    fifth_row.appendChild(topic_tbl_col);
                    topic_tbl = document.createElement('table');
                    topic_tbl.setAttribute('class', 'table table-bordered mb-0');
                    topic_tbl_col.appendChild(topic_tbl);
                    topic_tbl_heading = document.createElement('thead');
                    topic_tbl_heading.innerHTML = '<tr><th>Module</th><th>Topics</th></tr>';
                    topic_tbl.appendChild(topic_tbl_heading);
                    topic_tbl_body = document.createElement('tbody');
                    topic_tbl_body.setAttribute('id', 'session-topic-table-body' + session_id);
                    topic_tbl.appendChild(topic_tbl_body);

                    session_topics['s-' + session_id] = data.topics;
                    displayTopicsTable(session_id, session_topics['s-' + session_id]);

                    last_row = document.createElement('div');
                    last_row.setAttribute('class', 'row no-gutters p-3');
                    section_card.appendChild(last_row);
                    save_cancel_btn_col = document.createElement('div');
                    save_cancel_btn_col.setAttribute('class', 'col px-1');
                    last_row.appendChild(save_cancel_btn_col);
                    btn_grp = document.createElement('div');
                    btn_grp.setAttribute('style', 'float: right');
                    save_cancel_btn_col.appendChild(btn_grp);
                    btn_cancel = document.createElement('button');
                    btn_cancel.setAttribute('class', 'btn btn-secondary border');
                    btn_cancel.setAttribute('onclick', 'reloadSession("' + session_index + '","' + session_id + '")');
                    btn_cancel.innerHTML = "Cancel";
                    btn_grp.appendChild(btn_cancel);
                    btn_save = document.createElement('button');
                    btn_save.setAttribute('class', 'btn btn-primary border ml-2');
                    btn_save.setAttribute('id', 'save');
                    btn_save.setAttribute('onclick', 'saveSession("' + session_index + '","' + session_id + '")');
                    btn_save.setAttribute('style', 'background-color: #d76f5f');
                    btn_save.innerHTML = "Save";
                    btn_grp.appendChild(btn_save);


                } else {
                    swal({
                        title: "Sorry!",
                        text: "There was an error while trying to get the details of the session",
                        icon: "error",
                    });
                }
            }
    );
}

function error(error_str) {
    return {
        status: "Error",
        error: error_str
    };
}

function resetAndDisplayCreateNewSessionPanel() {
    session_topics['s-'] = [];
    document.getElementById('session-index').value = '';
    document.getElementById('session-name').value = '';
    document.getElementById('schedule-start-date-time').value = '';
    document.getElementById('session-duration').value = '';
    document.getElementById('session-meeting-link').value = '';
    document.getElementById('session-topic-table-body').innerHTML = '';
    document.getElementById('session-topic-table-row').classList.add("d-none");
}

function reloadSession(session_index, session_id) {
    course_id = $('#programList').val();
    $.post("/Session/getSessionDetailsForEditing",
            {
                data: {
                    course_id: course_id,
                    session_index: session_index
                }
            },
            function (response) {
                response = JSON.parse(response);
                if (response.status === "Success") {
                    section = document.getElementById('session-' + session_id);
                    section.innerHTML = "";
                    data = response.data;
                    displayOneSessionDetails(section, data);
                } else {
                    swal({
                        title: "Sorry!",
                        text: "There was an error while trying to get the details of the session",
                        icon: "error",
                    });
                }
            }
    );
}

function saveSession(session_index, session_id) {
    course_id = $('#programList').val();
    validation = validateSessionCreationOrEditForm(session_id);
    if (validation.status === "Success") {
        $.post("/Session/updateSession",
                {
                    data: validation.data
                },
                function (response) {
                    response = JSON.parse(response);
                    if (response.status === "Success") {
                        selectedProgram();
                        swal({
                            title: "Done!",
                            text: "Session " + validation.data.session_index + " has been created successfully",
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
    } else {
        swal({
            title: "Sorry!",
            text: validation.error,
            icon: "error",
        });
    }
}

function topicTypeChanged() {
    topic_type = $('#topic-type-drop-down').val();
    if (topic_type === "subject") {
        $('#general-topic-col').addClass('d-none');
        $('#module-drop-down-col').removeClass('d-none');
    } else if (topic_type === "general") {
        $('#general-topic-col').removeClass('d-none');
        $('#module-drop-down-col').addClass('d-none');
        $('#topics-list').html("");
        $('#topics-list-row').addClass('d-none');
    } else {
        //This would never happen
        $('#general-topic-col').addClass('d-none');
        $('#module-drop-down-col').addClass('d-none');
        $('#topics-list').html("");
        $('#topics-list-row').addClass('d-none');
    }
}
