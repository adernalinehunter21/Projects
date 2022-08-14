
function selectedProgram() {
    cleanUpCreationInterface();
    $('#upload-section').removeClass('d-none');

    var course_id = $('#programList').val();
    $('#mainContentSection').html("");
    $.post('/CourseConfiguration/getAlerts',
    {
        course_id: course_id
    },
    function(response){
        response =  JSON.parse(response);
        console.log(response);
        if(response['status'] === "Success"){
            displayConfiguredAlerts(response['data']);
        }
    }
    );
}

function displayConfiguredAlerts(data){
    main_content_section = document.getElementById('mainContentSection');
    data.forEach(alert_service =>{
        alerts = alert_service.data;
        if(alerts.length > 0){
            alert_service_section = document.createElement('div');
            alert_service_section.setAttribute('id', "alert-service-section-"+alert_service.id);
            main_content_section.appendChild(alert_service_section);

            alert_section_label = document.createElement('label');
            alert_section_label.innerHTML = alert_service.name;
            alert_section_label.setAttribute('for', 'alert-service-section-card-'+alert_service.id);
            alert_service_section.appendChild(alert_section_label);

            alert_section_card = document.createElement('div');
            alert_section_card.setAttribute('id', 'alert-service-section-card-'+alert_service.id);
            alert_section_card.setAttribute('class', 'row section-card');
            alert_service_section.appendChild(alert_section_card);

            alerts.forEach(alert => {

                alert_card_container = document.createElement('div');
                alert_card_container.setAttribute('id', 'alert-card-container-'+alert.id);
                alert_card_container.setAttribute('class', 'col-md-6 mb-2');
                alert_section_card.appendChild(alert_card_container);

                alert_card = document.createElement('div');
                alert_card.setAttribute('id', 'alert-card-'+alert.id);
                alert_card.setAttribute('class', 'card h-100 box-shadow');
                alert_card_container.appendChild(alert_card);

                alert_card_header = document.createElement('div');
                alert_card_header.setAttribute('class', 'card-header');
                alert_card_header.setAttribute('style', 'background-color:rgba(0,0,0,.03)');
                alert_card.appendChild(alert_card_header);

                alert_card_header_row = document.createElement('div');
                alert_card_header_row.setAttribute('class', 'row no-gutters');
                alert_card_header.appendChild(alert_card_header_row);

                alert_card_heading_col1 = document.createElement('div');
                alert_card_heading_col1.setAttribute('class', 'col-11');
                alert_card_header_row.appendChild(alert_card_heading_col1);

                time = formatTime(alert.time_before_session_schedule);
                alert_card_heading = document.createElement('p');
                alert_card_heading.setAttribute('class', 'aleart-heading');
                alert_card_heading.innerHTML = '<i class="far fa-clock"></i> ' + time + '<span class="dimmed-text"> Before Session Start</span>';
                alert_card_heading_col1.appendChild(alert_card_heading);

                alert_card_heading_col2 = document.createElement('div');
                alert_card_heading_col2.setAttribute('class', 'col-1 text-right');
                alert_card_header_row.appendChild(alert_card_heading_col2);
                alert_card_heading_delete_icon = document.createElement('i');
                alert_card_heading_delete_icon.setAttribute('class', 'far fa-trash-alt edit-delete-icon');
                alert_card_heading_delete_icon.setAttribute('onclick', 'delete_alert(' + alert.id + ')');
                alert_card_heading_col2.appendChild(alert_card_heading_delete_icon);

                alert_card_body = document.createElement('div');
                alert_card_body.setAttribute('class', 'card-body');
                alert_card.appendChild(alert_card_body);

                alert_card_subject_label = document.createElement('label');
                alert_card_subject_label.setAttribute('for', 'alert-card-subject-'+alert.id);
                alert_card_subject_label.innerHTML = "Subject:";
                alert_card_body.appendChild(alert_card_subject_label);
                alert_card_subject_container = document.createElement('div');
                alert_card_subject_container.setAttribute('id', 'alert-card-subject-'+alert.id);
                alert_card_body.appendChild(alert_card_subject_container);
                alert_card_subject = document.createElement('code');
                alert_card_subject.innerHTML = alert.subject;
                alert_card_subject_container.appendChild(alert_card_subject);

                alert_card_template_label = document.createElement('label');
                alert_card_template_label.setAttribute('for', 'alert-card-template-'+alert.id);
                alert_card_template_label.innerHTML = "Template:";
                alert_card_body.appendChild(alert_card_template_label);
                alert_card_template_container = document.createElement('div');
                alert_card_template_container.setAttribute('id', 'alert-card-template-'+alert.id);
                alert_card_body.appendChild(alert_card_template_container);
                alert_card_template = document.createElement('code');
                alert_card_template.innerHTML = alert.message_body;
                alert_card_template_container.appendChild(alert_card_template);

            });
        }

    });
    $('#main-content-section').removeClass('d-none');
}

function formatTime(input){
    var input_array = input.split(" ");
    if(input_array.length > 1){
        days = input_array[0];
        time = input_array[1];
    }
    else{
        days = 0
        time = input_array[0];
    }
    time_array = time.split(":");
    hours = time_array[0];
    minutes = time_array[1];

    result = "";
    if(days > 0){
        result = days + " Days";
    }
    if(hours > 0){
        result += " " + hours + " Hours";
    }
    if(minutes > 0){
        result += " " + minutes + " Minutes";
    }
    return result;
}

function delete_alert(alert_id){
    swal({
            title: "Are you sure?",
            text: "Once deleted, you will not be able to recover this Configuration!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {
            if (willDelete) {
                var course_id = $('#programList').val();
                $.post('/CourseConfiguration/deleteAlert',
                {
                    course_id: course_id,
                    alert_id: alert_id
                },
                response_json => {
                    response = JSON.parse(response_json);
                    if(response.status === "Success"){
                        swal({
                            title: "Done!",
                            text: "Configuration has been deleted successfully",
                            icon: "success",
                        });
                        removeAlertCardFromUI(alert_id);
                    }
                    else if(response.status === "Error"){
                        swal({
                            title: "Sorry!",
                            text: response.error,
                            icon: "error",
                        });
                    }
                    else {
                        swal({
                            title: "Sorry!",
                            text: "An error happened while trying to delete. Please retry after some time and report if issue reoccur",
                            icon: "error",
                        });
                    }
                });
            }
        });
}

function removeAlertCardFromUI(alert_id){
    $('#alert-card-container-'+alert_id).remove();
}

var variables = [];
function selectedAlertService() {
    newConfigurationInputs = document.getElementById('new-alert-service-inputs');

    selected_service = $('#alert').val();

    if (selected_service === "1") {//alertAboutUpcomingSession
        course_id = $('#programList').val();
        alert_id = $('#alert').val();
        $.post("/CourseConfiguration/getVariablesForAlertConfiguration",
                {
                    course_id: course_id,
                    alert_id: alert_id
                },
                function (response) {
                    response = JSON.parse(response);
                    if (response.status == "Success") {
                        variables = response.data;
                    } else if (response.status == "Error") {
                        swal({
                            title: "Sorry!",
                            text: "An error happened while getting viables for the chosen alert. Either you can create a template witout variables or try after reloading\n\nError:" + response.error,
                            icon: "error",
                        });
                    }
                }
        );

    }
}

function openInsertVariableModal() {

    if (variables.length > 0) {

        variable_details = document.getElementById('variable-details');
        variable_details.innerHTML = "";

        select_variable = document.getElementById('variable');
        select_variable.innerHTML = "";
        varable_option = document.createElement('option');
        varable_option.setAttribute('value', '');
        varable_option.innerHTML = "Select";
        select_variable.appendChild(varable_option);

        variables.forEach((variable, index) => {
            varable_option = document.createElement('option');
            varable_option.setAttribute('value', index);
            varable_option.setAttribute('type', variable['type']);
            varable_option.innerHTML = variable['name'];
            select_variable.appendChild(varable_option);
        });

        $('#insert-variable-modal').modal('show');
    }
    else{
        selected_alert_id = $('#alert').val();
        if(selected_alert_id === ""){
            swal({
                title: "Sorry!",
                text: "Please select the alert service and try again",
                icon: "error",
            });
        }
        else{
            swal({
                title: "Sorry!",
                text: "There seem to be no vaiables to configure for this selected alert service",
                icon: "warning",
            });
        }
    }
}

function selectedVariable() {
    selected_variable_index = $('#variable').val();
    variable_details = document.getElementById('variable-details');
    variable_details.innerHTML = "";

    selected_variable = variables[selected_variable_index];
    selected_variable_type = selected_variable['type'];

    variable_details_row1 = document.createElement('div');
    variable_details_row1.setAttribute('class', 'row no-gutters mt-4');
    variable_details.appendChild(variable_details_row1);

    if (selected_variable_type === "String") {

        format_n_example = getTwigFormatAndExampleStrings();
        variable_details_col11 = document.createElement('div');
        variable_details_col11.setAttribute('class', 'col col-md-6 px-1');
        variable_details_row1.appendChild(variable_details_col11);

        variable_schema_label = document.createElement('label');
        variable_schema_label.setAttribute('for', 'variable_schema');
        variable_schema_label.innerHTML = "Variable Schema";
        variable_details_col11.appendChild(variable_schema_label);

        variable_schema = document.createElement('div');
        variable_schema.setAttribute('id', 'variable_schema');
        variable_schema.setAttribute('class', 'badge badge-light text-wrap');
        variable_schema.setAttribute('style', 'display: block; text-align: left; font-size: inherit;');
        variable_schema.innerHTML = format_n_example.twig_format; //"{{ data['" + source_parameter + "'] }}";
        variable_details_col11.appendChild(variable_schema);


        variable_details_col12 = document.createElement('div');
        variable_details_col12.setAttribute('class', 'col col-md-6 px-1');
        variable_details_row1.appendChild(variable_details_col12);

        variable_example_label = document.createElement('label');
        variable_example_label.setAttribute('for', 'variable_example');
        variable_example_label.innerHTML = "Example Value";
        variable_details_col12.appendChild(variable_example_label);

        variable_example = document.createElement('div');
        variable_example.setAttribute('id', 'variable_example');
        variable_example.setAttribute('class', 'badge badge-light text-wrap');
        variable_example.setAttribute('style', 'display: block; text-align: left; font-size: inherit;');
        variable_example.innerHTML =  format_n_example.example_value; //selected_variable['example_value'];
        variable_details_col12.appendChild(variable_example);

    } else if (selected_variable_type === "Link") {
        variable_details_col1 = document.createElement('div');
        variable_details_col1.setAttribute('class', 'col px-1');
        variable_details_row1.appendChild(variable_details_col1);

        input_group = document.createElement('div');
        input_group.setAttribute('class', 'input-group mb-3');
        variable_details_col1.appendChild(input_group);

        input = document.createElement('input');
        input.setAttribute('class', 'form-control');
        input.setAttribute('type', 'text');
        input.setAttribute('id', 'link-text-input');
        input.setAttribute('placeholder', 'Enter the text here and press Go -->');
        input.setAttribute('aria-label', 'Enter the text here and press Go -->');
        input.setAttribute('aria-describedby', 'basic-addon2');
        input_group.appendChild(input);

        input_group_append = document.createElement('div');
        input_group_append.setAttribute('class', 'input-group-append');
        input_group.appendChild(input_group_append);

        input_btn = document.createElement('button');
        input_btn.setAttribute('class', 'btn btn-outline-secondary');
        input_btn.setAttribute('type', 'button');
        input_btn.setAttribute('onclick', 'linkInputTextEntered()');
        input_btn.innerHTML = "Go";
        input_group_append.appendChild(input_btn);

        variable_details_row2 = document.createElement('div');
        variable_details_row2.setAttribute('class', 'row no-gutters mt-4');
        variable_details_row2.setAttribute('id', 'link_variable_row');
        variable_details.appendChild(variable_details_row2);

    } else if (selected_variable_type === "Logo") {
        variable_details_col1 = document.createElement('div');
        variable_details_col1.setAttribute('class', 'col px-1');
        variable_details_row1.appendChild(variable_details_col1);

        input_group = document.createElement('div');
        input_group.setAttribute('class', 'input-group mb-3');
        variable_details_col1.appendChild(input_group);

        input = document.createElement('input');
        input.setAttribute('class', 'form-control');
        input.setAttribute('type', 'number');
        input.setAttribute('id', 'logo-height-input');
        input.setAttribute('placeholder', 'Enter the height of the logo in pixels here and press Go -->');
        input.setAttribute('aria-label', 'Enter the height of the logo in pixels here and press Go -->');
        input.setAttribute('aria-describedby', 'basic-addon2');
        input_group.appendChild(input);

        input_group_append = document.createElement('div');
        input_group_append.setAttribute('class', 'input-group-append');
        input_group.appendChild(input_group_append);

        input_btn = document.createElement('button');
        input_btn.setAttribute('class', 'btn btn-outline-secondary');
        input_btn.setAttribute('type', 'button');
        input_btn.setAttribute('onclick', 'logoHeightEnetered()');
        input_btn.innerHTML = "Go";
        input_group_append.appendChild(input_btn);

        variable_details_row2 = document.createElement('div');
        variable_details_row2.setAttribute('class', 'row no-gutters mt-4');
        variable_details_row2.setAttribute('id', 'link_variable_row');
        variable_details.appendChild(variable_details_row2);
    } else if (selected_variable_type === "DateTime") {
        variable_details_col1 = document.createElement('div');
        variable_details_col1.setAttribute('class', 'col px-1');
        variable_details_row1.appendChild(variable_details_col1);

        input_group = document.createElement('div');
        input_group.setAttribute('class', 'input-group mb-3');
        variable_details_col1.appendChild(input_group);

        input = document.createElement('select');
        input.setAttribute('class', 'form-control-sm general-select-in-body select-border');
        input.setAttribute('id', 'date-time-format');
        input.setAttribute('onchange', 'dateTimeFormatChosen()');
        input_group.appendChild(input);

        input_option = document.createElement('option');
        input_option.setAttribute('value', "");
        input_option.innerHTML = "Select the format";
        input.appendChild(input_option);

        format_list = selected_variable['formats'];
        if (format_list.length > 0) {
            format_list.forEach((one_format, index) => {
                input_option = document.createElement('option');
                input_option.setAttribute('value', index);
                input_option.innerHTML = one_format['format'];
                input.appendChild(input_option);
            });
        }

        variable_details_row2 = document.createElement('div');
        variable_details_row2.setAttribute('class', 'row no-gutters mt-4');
        variable_details_row2.setAttribute('id', 'date_time_variable_row');
        variable_details.appendChild(variable_details_row2);
    } else if (selected_variable_type === "List") {
        format_n_example = getTwigFormatAndExampleStrings();

        variable_details_col11 = document.createElement('div');
        variable_details_col11.setAttribute('class', 'col col-md-6 px-1');
        variable_details_row1.appendChild(variable_details_col11);

        variable_schema_label = document.createElement('label');
        variable_schema_label.setAttribute('for', 'variable_schema');
        variable_schema_label.innerHTML = "Variable Schema";
        variable_details_col11.appendChild(variable_schema_label);

        variable_schema = document.createElement('div');
        variable_schema.setAttribute('id', 'variable_schema');
        variable_schema.setAttribute('class', 'badge badge-light text-wrap');
        variable_schema.setAttribute('style', 'display: block; text-align: left; font-size: inherit;');
        variable_schema.innerHTML = format_n_example.twig_format;
        variable_details_col11.appendChild(variable_schema);


        variable_details_col12 = document.createElement('div');
        variable_details_col12.setAttribute('class', 'col col-md-6 px-1');
        variable_details_row1.appendChild(variable_details_col12);

        variable_example_label = document.createElement('label');
        variable_example_label.setAttribute('for', 'variable_example');
        variable_example_label.innerHTML = "Example Value";
        variable_details_col12.appendChild(variable_example_label);

        variable_example = document.createElement('div');
        variable_example.setAttribute('id', 'variable_example');
        variable_example.setAttribute('class', 'badge badge-light text-wrap');
        variable_example.setAttribute('style', 'display: block; text-align: left; font-size: inherit;');

        variable_example.innerHTML = format_n_example.example_value;
        variable_details_col12.appendChild(variable_example);
    }

}

function linkInputTextEntered() {
    link_text = $('#link-text-input').val();
    link_text = link_text.trim();
    if (!link_text) {
        swal({
            title: "Sorry!",
            text: "Please enter the text and click Go again",
            icon: "error",
        });
    } else {
        format_n_example = getTwigFormatAndExampleStrings();

        variable_details_row2 = document.getElementById('link_variable_row');
        variable_details_row2.innerHTML = "";

        variable_details_col21 = document.createElement('div');
        variable_details_col21.setAttribute('class', 'col col-md-6 px-1');
        variable_details_row2.appendChild(variable_details_col21);

        variable_schema_label = document.createElement('label');
        variable_schema_label.setAttribute('for', 'variable_schema');
        variable_schema_label.innerHTML = "Variable Schema";
        variable_details_col21.appendChild(variable_schema_label);

        variable_schema = document.createElement('div');
        variable_schema.setAttribute('id', 'variable_schema');
        variable_schema.setAttribute('class', 'badge badge-light text-wrap');
        variable_schema.setAttribute('style', 'display: block; text-align: left; font-size: inherit;');
        variable_schema.innerHTML = format_n_example.twig_format; //'<a href="{{ data[\'' + source_parameter + '\'] }}">' + link_text + '</a>';
        variable_details_col21.appendChild(variable_schema);


        variable_details_col22 = document.createElement('div');
        variable_details_col22.setAttribute('class', 'col col-md-6 px-1');
        variable_details_row2.appendChild(variable_details_col22);

        variable_example_label = document.createElement('label');
        variable_example_label.setAttribute('for', 'variable_example');
        variable_example_label.innerHTML = "Example Value";
        variable_details_col22.appendChild(variable_example_label);

        variable_example = document.createElement('div');
        variable_example.setAttribute('id', 'variable_example');
        variable_example.setAttribute('class', 'badge badge-light text-wrap');
        variable_example.setAttribute('style', 'display: block; text-align: left; font-size: inherit;');
        variable_example.innerHTML = format_n_example.example_value;
        variable_details_col22.appendChild(variable_example);

//        link_tag = document.createElement('a');
//        link_tag.setAttribute('href', selected_variable['example_value']);
//        link_tag.innerHTML = link_text;
//        variable_example.appendChild(link_tag);
    }
}

function logoHeightEnetered() {
    logo_height = $('#logo-height-input').val();
    logo_height = logo_height.trim();
    if (!logo_height) {
        swal({
            title: "Sorry!",
            text: "Please enter the height in number of pixels and click Go again",
            icon: "error",
        });
    } else {
        format_n_example = getTwigFormatAndExampleStrings();

        variable_details_row2 = document.getElementById('link_variable_row');
        variable_details_row2.innerHTML = "";

        variable_details_col21 = document.createElement('div');
        variable_details_col21.setAttribute('class', 'col col-md-6 px-1');
        variable_details_row2.appendChild(variable_details_col21);

        variable_schema_label = document.createElement('label');
        variable_schema_label.setAttribute('for', 'variable_schema');
        variable_schema_label.innerHTML = "Variable Schema";
        variable_details_col21.appendChild(variable_schema_label);

        variable_schema = document.createElement('div');
        variable_schema.setAttribute('id', 'variable_schema');
        variable_schema.setAttribute('class', 'badge badge-light text-wrap');
        variable_schema.setAttribute('style', 'display: block; text-align: left; font-size: inherit;');
        variable_schema.innerHTML = format_n_example.twig_format;
        variable_details_col21.appendChild(variable_schema);


        variable_details_col22 = document.createElement('div');
        variable_details_col22.setAttribute('class', 'col col-md-6 px-1');
        variable_details_row2.appendChild(variable_details_col22);

        variable_example_label = document.createElement('label');
        variable_example_label.setAttribute('for', 'variable_example');
        variable_example_label.innerHTML = "Example Value";
        variable_details_col22.appendChild(variable_example_label);

        variable_example = document.createElement('div');
        variable_example.setAttribute('id', 'variable_example');
        variable_example.setAttribute('class', 'badge badge-light text-wrap');
        variable_example.setAttribute('style', 'display: block; text-align: left; font-size: inherit;');
        variable_example.innerHTML = format_n_example.example_value;
        variable_details_col22.appendChild(variable_example);
    }
}

function dateTimeFormatChosen() {

    date_time_format_index = $('#date-time-format').val();
    if (typeof (format_list[date_time_format_index]) !== 'undefined') {

        format_n_example = getTwigFormatAndExampleStrings();

        variable_details_row2 = document.getElementById('date_time_variable_row');
        variable_details_row2.innerHTML = "";

        variable_details_col21 = document.createElement('div');
        variable_details_col21.setAttribute('class', 'col col-md-6 px-1');
        variable_details_row2.appendChild(variable_details_col21);

        variable_schema_label = document.createElement('label');
        variable_schema_label.setAttribute('for', 'variable_schema');
        variable_schema_label.innerHTML = "Variable Schema";
        variable_details_col21.appendChild(variable_schema_label);

        variable_schema = document.createElement('div');
        variable_schema.setAttribute('id', 'variable_schema');
        variable_schema.setAttribute('class', 'badge badge-light text-wrap');
        variable_schema.setAttribute('style', 'display: block; text-align: left; font-size: inherit;');
        variable_schema.innerHTML = format_n_example.twig_format;
        variable_details_col21.appendChild(variable_schema);


        variable_details_col22 = document.createElement('div');
        variable_details_col22.setAttribute('class', 'col col-md-6 px-1');
        variable_details_row2.appendChild(variable_details_col22);

        variable_example_label = document.createElement('label');
        variable_example_label.setAttribute('for', 'variable_example');
        variable_example_label.innerHTML = "Example Value";
        variable_details_col22.appendChild(variable_example_label);

        variable_example = document.createElement('div');
        variable_example.setAttribute('id', 'variable_example');
        variable_example.setAttribute('class', 'badge badge-light text-wrap');
        variable_example.setAttribute('style', 'display: block; text-align: left; font-size: inherit;');
        variable_example.innerHTML = format_n_example.example_value;
        variable_details_col22.appendChild(variable_example);

    }
}

function getTwigFormatAndExampleStrings(){
    selected_variable_index = $('#variable').val();
    selected_variable = variables[selected_variable_index];

    source_parameter = selected_variable['source'];
    selected_variable_type = selected_variable['type'];

    twig_format = "";
    example_value = "";
    if (selected_variable_type === "String") {
        twig_format = '{{ data[\'' + source_parameter + '\'] }}';
        example_value = selected_variable['example_value'];
    }
    else if(selected_variable_type === "Link"){
        link_text = $('#link-text-input').val();
        link_text = link_text.trim();

        twig_format = '<a href="{{ data[\'' + source_parameter + '\'] }}">' + link_text + '</a>';
        example_value = '<a href="{{ data[\'' + selected_variable['example_value'] + '\'] }}">' + link_text + '</a>'; ;
    }
    else if(selected_variable_type === "Logo"){
        logo_height = $('#logo-height-input').val();
        logo_height = logo_height.trim();

        twig_format = '<img src="{{ data[\'' + source_parameter + '\'] }}" height="' + logo_height + '" >';
        example_value = '<img src="' + selected_variable['example_value'] + '" height="' + logo_height + '" >';
    }
    else if (selected_variable_type === "DateTime") {
        date_time_format_index = $('#date-time-format').val();
        date_time_format = format_list[date_time_format_index];

        twig_format = '{{ data[\'' + source_parameter + '\'] | date("' + date_time_format['format'] + '") }}';
        example_value = date_time_format['example_value'];
    }
    else if (selected_variable_type === "List") {
        twig_format = '\n<ul>{% for listItem in data["' + source_parameter + '"] %}<li>{{ listItem }}</li>{% endfor %}</ul>\n';

        example_list = selected_variable['example_value'];
        if (example_list.length > 0) {
            list_html = "<ul>";
            example_list.forEach(listItem => {
                list_html += '<li>' + listItem + '</li>';
            });
            list_html += "</ul>";
        } else {
            list_html = "";
        }
        example_value = list_html;
    }

    return {
        twig_format: twig_format,
        example_value: example_value
    };
}

function insertVariable() {

    format_n_example = getTwigFormatAndExampleStrings();

    var cursorPos = $('#template-editor').prop('selectionStart');
    var v = $('#template-editor').val();
    var textBefore = v.substring(0,  cursorPos);
    var textAfter  = v.substring(cursorPos, v.length);

    $('#template-editor').val(textBefore + format_n_example.twig_format + textAfter);

    $('#insert-variable-modal').modal('hide');
}

function submitNewAlertConfiguration(){
    course_id = $('#programList').val();
    alert_id = $('#alert').val();
    days = $('#time_in_days').val();
    hours = $('#time_in_hours').val();
    minutes = $('#time_in_minutes').val();
    subject = $('#subject').val();
    template = $('#template-editor').val();

    $.post('/course-configuration/addAlert',
        {
            course_id: course_id,
            alert_id: alert_id,
            days: days,
            hours: hours,
            minutes: minutes,
            subject: subject,
            template: template
        },
        function (response_json){
            response = JSON.parse(response_json);
            if(response.status === "Success"){
                selectedProgram();
                swal({
                    title: "Done!",
                    text: "New alert configured successully",
                    icon: "success",
                });

            }else if(typeof(response.error) != 'undefined'){
                swal({
                    title: "Sorry!",
                    text: response.error,
                    icon: "error",
                });
            }
        }
    );
}

function cleanUpCreationInterface(){
    $('#alert').val("0");
    $('#time_in_days').val(1);
    $('#time_in_hours').val(0);
    $('#time_in_minutes').val(0);
    $('#subject').val("");
    $('#template-editor').val("");
}

$(function () {
    $('.time-input').keydown(inputKeyDownHandler);
    $('.time-input').keyup(inputKeyUpHandler);
});

function inputKeyDownHandler() {
    var min_value = $(this).attr('min');
    var max_value = $(this).attr('max');
    var current_value = parseInt($(this).val());
    // Save old value.
    if ($(this).val() && current_value >= min_value && current_value <= max_value) {
        $(this).data("old", current_value);
    }

}

function inputKeyUpHandler() {
    var min_value = $(this).attr('min');
    var max_value = $(this).attr('max');
    var current_value = parseInt($(this).val());
    var old_value = $(this).data("old");
    // Check correct, else revert back to old value.
    if (isNaN(current_value)) {
        ;
    } else {
        if (current_value < min_value || current_value > max_value) {
            if (!isNaN(old_value)) {
                $(this).val(old_value);
            } else if (current_value < min_value) {
                $(this).val(min_value);
            } else if (current_value > max_value) {
                $(this).val(max_value);
            }
        } else {
            ;
        }
    }
}
