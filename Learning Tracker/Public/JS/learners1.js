
function selectedProgram() {
    $('#mainContentSection').html("");
    $('#main-content-section').addClass('d-none');
    
    var courseId = $('#programList').val();
    var subjectId = $('#subjectList').val();
    $.post("/Learner/get",
    {
        course_id: courseId,
    },
    function (response) {
        var responseArray = JSON.parse(response);
        if((responseArray['active']).length > 0 || (responseArray['inactive']).length > 0){
            $('#main-content-section').removeClass('d-none');
        }
        content_section = document.getElementById('mainContentSection');

        if((responseArray['inactive']).length > 0){
            
            var inactive_learners = responseArray['inactive'];
            
            inactives_card = document.createElement('div');
            inactives_card.setAttribute('class', 'card parent-card no-border p-2 mb-4');
            content_section.appendChild(inactives_card);
            
            card_heading = document.createElement('h4');
            card_heading.setAttribute('class', 'card-top-sub-heading main-heading-card ml-2');
            card_heading.innerHTML = "New";
            inactives_card.appendChild(card_heading);
            
            inactives_row = document.createElement('div');
            inactives_row.setAttribute('class', 'row no-gutters resource-content-row');
            inactives_card.appendChild(inactives_row);
            
            inactive_learners.forEach(function(learner){
                inactives_col = document.createElement('div');
                inactives_col.setAttribute('class', 'col-xs-12 col-sm-4 col-md-3 prog-par-col my-1');
                inactives_col.setAttribute('id', 'learner-col-'+learner.id);
                inactives_col.setAttribute('name', learner.name);
                inactives_col.setAttribute('last_name', learner.last_name);
                inactives_col.setAttribute('email', learner.email);
                inactives_row.appendChild(inactives_col);

                inactive_user_card = document.createElement('div');
                inactive_user_card.setAttribute('class', 'card chart-card box-shadow h-100 mx-1');
                inactives_col.appendChild(inactive_user_card);
                
                inactive_user_card_body = document.createElement('div');
                inactive_user_card_body.setAttribute('class', 'card-body');
                inactive_user_card.appendChild(inactive_user_card_body);
                
                inactive_user_name_row = document.createElement('div');
                inactive_user_name_row.setAttribute('class', 'row no-gutters');
                inactive_user_card_body.appendChild(inactive_user_name_row);
                                
                inactive_user_card_icons_col = document.createElement('div');
                inactive_user_card_icons_col.setAttribute('class', 'col-md-6 order-md-2');
                inactive_user_card_icons_col.setAttribute('style', 'text-align: right');
                inactive_user_name_row.appendChild(inactive_user_card_icons_col);
                
                inactive_user_card_edit_icon = document.createElement('div');
                inactive_user_card_edit_icon.setAttribute('class', 'far fa-edit edit-delete-icon');
                inactive_user_card_edit_icon.setAttribute('onclick','editLearner("'+courseId+'", "'+learner.id+'")');
                inactive_user_card_icons_col.appendChild(inactive_user_card_edit_icon);
                
                inactive_user_card_resend_icon = document.createElement('div');
                inactive_user_card_resend_icon.setAttribute('class', 'far fa-envelope edit-delete-icon');
                inactive_user_card_resend_icon.setAttribute('onclick','requestToResendPassword("'+courseId+'", "'+learner.id+'")');
                inactive_user_card_icons_col.appendChild(inactive_user_card_resend_icon);
                
                inactive_user_card_delete_icon = document.createElement('div');
                inactive_user_card_delete_icon.setAttribute('class', 'far fa-trash-alt edit-delete-icon');
                inactive_user_card_delete_icon.setAttribute('onclick','deleteLearner("'+courseId+'", "'+learner.id+'")');
                inactive_user_card_icons_col.appendChild(inactive_user_card_delete_icon);
                
                inactive_user_name_col = document.createElement('div');
                inactive_user_name_col.setAttribute('class', 'col-md-6 order-md-1');
                inactive_user_name_row.appendChild(inactive_user_name_col);
                
                inactive_user_name = document.createElement('p');
                var last_name = (learner.last_name).trim();
                inactive_user_name.innerHTML = learner.name + " " + last_name;
                inactive_user_name_col.appendChild(inactive_user_name);
                
                inactive_user_email = document.createElement('p');
                inactive_user_email.innerHTML = learner.email;
                inactive_user_card_body.appendChild(inactive_user_email)
                
            });
            
        }
        
        if((responseArray['active']).length > 0){
            
            var active_learners = responseArray['active'];
            actives_card = document.createElement('div');
            actives_card.setAttribute('class', 'card parent-card no-border p-2');
            content_section.appendChild(actives_card);
            
            card_heading = document.createElement('h4');
            card_heading.setAttribute('class', 'card-top-sub-heading main-heading-card ml-2');
            card_heading.innerHTML = "Activated";
            actives_card.appendChild(card_heading);
            
            actives_row = document.createElement('div');
            actives_row.setAttribute('class', 'row no-gutters resource-content-row');
            actives_card.appendChild(actives_row);
            
            active_learners.forEach(function(learner){
                actives_col = document.createElement('div');
                actives_col.setAttribute('class', 'col-xs-12 col-sm-4 col-md-3 prog-par-col my-1');
                actives_row.appendChild(actives_col);

                active_user_card = document.createElement('div');
                active_user_card.setAttribute('class', 'card chart-card box-shadow h-100 mx-1');
                actives_col.appendChild(active_user_card);
                
                active_user_card_body = document.createElement('div');
                active_user_card_body.setAttribute('class', 'card-body');
                active_user_card.appendChild(active_user_card_body);
                
                active_user_card_delete_icon = document.createElement('div');
                active_user_card_delete_icon.setAttribute('class', 'far fa-trash-alt edit-delete-icon');
                active_user_card_delete_icon.setAttribute('style', 'float: right');
                active_user_card_delete_icon.setAttribute('onclick','deleteLearner("'+courseId+'", "'+learner.id+'")');
                active_user_card_body.appendChild(active_user_card_delete_icon);
                
                active_user_image = document.createElement('img');
                active_user_image.setAttribute('class', 'card-img-top w-100 d-block card-image');
                if(!learner.profile_pic_binary){
                    active_user_image.setAttribute('src', '../../images/avatar.jpg');
                }else{
                    active_user_image.setAttribute('src', learner.profile_pic_binary)
                }
                active_user_card_body.appendChild(active_user_image);
                
                active_user_name = document.createElement('p');
                var last_name = (learner.last_name).trim();
                active_user_name.innerHTML = learner.name + " " + last_name;
                active_user_card_body.appendChild(active_user_name);
                
                active_user_email = document.createElement('p'); 
                active_user_email.innerHTML = learner.email;
                active_user_card_body.appendChild(active_user_email)
                
            });
        }
    });
    
    $('#upload-section').removeClass('d-none');
}

function deleteLearner(course_id, user_id) {

    swal({
        title: "Are you sure?",
        text: "Once deleted, you will not be able to recover this user",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })

    .then((willDelete) => {
        if (willDelete) {
            $.post("/Learner/delete",
            {
                course_id: course_id,
                user_id: user_id
            },
            function (response) {
                var responseArray = JSON.parse(response);
                if (responseArray['status'] === "Success") {
                    selectedProgram();
                    swal({
                        title: "Done!",
                        text: "User has been deleted successfully",
                        icon: "success",
                    });
                } else {
                    swal({
                        title: "Sorry!",
                        text: responseArray.error,
                        icon: "error",
                    });
                }
            });
        }
    });
}

function requestToResendPassword(course_id, user_id) {

    swal({
        title: "Are you sure?",
        text: "Invitation email with the credentials would be resent to user",
        icon: "info",
        buttons: true,
        dangerMode: true,
    })

    .then((send) => {
        if (send) {
            $.post("/Learner/resendPassword",
            {
                course_id: course_id,
                user_id: user_id
            },
            function (response) {
                var responseArray = JSON.parse(response);
                if (responseArray['status'] === "Success") {
                    selectedProgram();
                    swal({
                        title: "Done!",
                        text: "sent the invitition email successfully",
                        icon: "success",
                    });
                } else {
                    swal({
                        title: "Sorry!",
                        text: responseArray.error,
                        icon: "error",
                    });
                }
            });
        }
    });
}

function editLearner(course_id, user_id){
    
    learner_col_div = document.getElementById("learner-col-"+user_id);
    var name = learner_col_div.getAttribute('name');
    var last_name = learner_col_div.getAttribute('last_name');
    var email = learner_col_div.getAttribute('email');
    
    //Remove all classes of the column div
    learner_col_div.className = '';
    //Remove its content
    learner_col_div.innerHTML = '';
    
    learner_col_div.setAttribute('class', 'col-12 zero-padding');
    edit_card = document.createElement("div");
    edit_card.setAttribute('class', 'card section2-cards m-2');
    learner_col_div.appendChild(edit_card);
    
    edit_card_row1 = document.createElement("div");
    edit_card_row1.setAttribute('class', 'row no-gutters');
    edit_card.appendChild(edit_card_row1);
    
    edit_card_name_col = document.createElement("div");
    edit_card_name_col.setAttribute('class', 'col-12 col-sm-6 col-md-4 px-1');
    edit_card_row1.appendChild(edit_card_name_col);
    
    edit_card_name_label = document.createElement("label");
    edit_card_name_label.setAttribute('for', 'first-name'+user_id);
    edit_card_name_label.innerHTML = "First Name";
    edit_card_name_col.appendChild(edit_card_name_label);
    edit_card_name = document.createElement("input");
    edit_card_name.setAttribute('type', 'text');
    edit_card_name.setAttribute('id', 'first-name'+user_id);
    edit_card_name.setAttribute('maxlength', '50');
    edit_card_name.setAttribute('class', 'form-control-sm general-select-in-body select-border p-1');
    edit_card_name.value = name;
    edit_card_name_col.appendChild(edit_card_name);
    
    edit_card_last_name_col = document.createElement("div");
    edit_card_last_name_col.setAttribute('class', 'col-12 col-sm-6 col-md-4 px-1');
    edit_card_row1.appendChild(edit_card_last_name_col);
    
    edit_card_last_name_label = document.createElement("label");
    edit_card_last_name_label.setAttribute('for', 'last-name'+user_id);
    edit_card_last_name_label.innerHTML = "Last Name";
    edit_card_last_name_col.appendChild(edit_card_last_name_label);
    edit_card_last_name = document.createElement("input");
    edit_card_last_name.setAttribute('type', 'text');
    edit_card_last_name.setAttribute('id', 'last-name'+user_id);
    edit_card_last_name.setAttribute('maxlength', '50');
    edit_card_last_name.setAttribute('class', 'form-control-sm general-select-in-body select-border p-1');
    edit_card_last_name.value = last_name;
    edit_card_last_name_col.appendChild(edit_card_last_name);
    
    edit_card_email_col = document.createElement("div");
    edit_card_email_col.setAttribute('class', 'col-12 col-sm-6 col-md-4 px-1');
    edit_card_row1.appendChild(edit_card_email_col);
    
    edit_card_email_label = document.createElement("label");
    edit_card_email_label.setAttribute('for', 'email-id'+user_id);
    edit_card_email_label.innerHTML = "Email ID";
    edit_card_email_col.appendChild(edit_card_email_label);
    edit_card_email = document.createElement("input");
    edit_card_email.setAttribute('type', 'text');
    edit_card_email.setAttribute('id', 'email-id'+user_id);
    edit_card_email.setAttribute('maxlength', '100');
    edit_card_email.setAttribute('class', 'form-control-sm general-select-in-body select-border');
    edit_card_email.setAttribute('name', 'email');
    edit_card_email.value = email;
    edit_card_email_col.appendChild(edit_card_email);
    
    edit_card_row2 = document.createElement("div");
    edit_card_row2.setAttribute('class', 'mt-2 px-1');
    edit_card.appendChild(edit_card_row2);
    
    checkbox_container = document.createElement("div");
    checkbox_container.setAttribute('class', 'checkbox');
    edit_card_row2.appendChild(checkbox_container);
    
    checkbox_input = document.createElement('input');
    checkbox_input.setAttribute('id', 'send-invite-checkbox'+user_id);
    checkbox_input.setAttribute('type', 'checkbox');
    checkbox_input.setAttribute('checked', true);
    checkbox_container.appendChild(checkbox_input);
    
    checkbox_label = document.createElement('label');
    checkbox_label.setAttribute('for', 'send-invite-checkbox'+user_id);
    checkbox_label.innerHTML = "&nbsp;Send Invitation email to learner on update";
    checkbox_container.appendChild(checkbox_label);
    
    edit_card_btn_row_container = document.createElement("div");
    edit_card_btn_row_container.setAttribute('class', 'text-right button-div mt-2');
    edit_card.appendChild(edit_card_btn_row_container);
    
    edit_card_btn_row = document.createElement("div");
    edit_card_btn_row.setAttribute('class', 'row no-gutters');
    edit_card_btn_row_container.appendChild(edit_card_btn_row);
    edit_card_btn_col = document.createElement("div");
    edit_card_btn_col.setAttribute('class', 'col');
    edit_card_btn_row.appendChild(edit_card_btn_col);
    edit_card_btn = document.createElement("button");
    edit_card_btn.setAttribute('class', 'btn btn-primary text-right border rounded');
    edit_card_btn.setAttribute('type', 'submit');
    edit_card_btn.setAttribute('style', 'background-color: #d76f5f');
    edit_card_btn.setAttribute('id', 'resource-submit-btn'+user_id);
    edit_card_btn.setAttribute('onclick', 'updateLearner("'+course_id+'", "'+user_id+'")');
    edit_card_btn.innerHTML = "Submit";
    edit_card_btn_col.appendChild(edit_card_btn);
    
    
}

function validteForm(user_id = ""){
    var name = $('#first-name'+user_id).val();
    name = name.trim();
    if(name == ""){
        return {
            status: "Error",
            error: "Please enter the first name"
        };
    }
    
    var last_name = $('#last-name'+user_id).val();
    last_name = last_name.trim();
    
    var email = $('#email-id'+user_id).val();
    email = email.trim();
    if(email == ""){
        return {
            status: "Error",
            error: "Please enter the Email ID"
        };
    }
    var checkbox_value = $('#send-invite-checkbox'+user_id+':checked').val();
    if(typeof checkbox_value === 'undefined'){
        checkbox_value = 'off';
    }
    return {
        status: "Success",
        data:{
            name: name,
            last_name: last_name,
            email: email,
            send_invite: checkbox_value
        }
    };
    
}

function updateLearner(course_id, user_id){
    
    validation_result = validteForm(user_id);
    if(validation_result.status === "Error"){
        swal({
            title: "Sorry!",
            text: validation_result.error,
            icon: "error",
        });
    }else{
        data = validation_result.data;
        var message_to_user = "Once submitted, user details will be updated and new invitition email is sent to user";
        if(data.send_invite === "off"){
            message_to_user = "Once submitted, user details will be updated but no notification would be sent.\n\nThis could create an issue specially if you have changed the email ID";
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
                $.post("/Learner/update",
                {
                    course_id: course_id,
                    user_id: user_id,
                    first_name: data.name,
                    last_name: data.last_name,
                    email: data.email,
                    notify_user_flag: data.send_invite
                },
                function (response) {
                    var responseArray = JSON.parse(response);
                    if (responseArray['status'] === "Success") {
                        selectedProgram();
                        new_message_to_user = "User details updated and new invition is sent";
                        if(data.send_invite === "off"){
                            new_message_to_user = "User details updated";
                        }
                        swal({
                            title: "Done!",
                            text: new_message_to_user,
                            icon: "success",
                        });
                    } else {
                        swal({
                            title: "Sorry!",
                            text: responseArray.error,
                            icon: "error",
                        });
                    }
                });
            }
        });
    }
    
}

function submitNewLearner(){
    
    var course_id = $('#programList').val();
    
    validation_result = validteForm();
    
    if(validation_result.status === "Error"){
        swal({
            title: "Sorry!",
            text: validation_result.error,
            icon: "error",
        });
    }else{
        data = validation_result.data;
        var message_to_user = "Once submitted, new user will be created and an invitition email is sent to the given email id";
        if(data.send_invite === "off"){
            message_to_user = "Once submitted, new user will be created and no invitition email would be sent.\n\nYou will have to send the invitation later";
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
                $.post("/Learner/add",
                {
                    course_id: course_id,
                    first_name: data.name,
                    last_name: data.last_name,
                    email: data.email,
                    notify_user_flag: data.send_invite
                },
                function (response) {
                    var responseArray = JSON.parse(response);
                    if (responseArray['status'] === "Success") {
                        clearNewAdditioForm();
                        selectedProgram();
                        swal({
                            title: "Done!",
                            text: "created new user and an invition email",
                            icon: "success",
                        });
                    } else {
                        swal({
                            title: "Sorry!",
                            text: responseArray.error,
                            icon: "error",
                        });
                    }
                });
            }
        });
    }
}

function clearNewAdditioForm(){
    $('#first-name').val("");
    $('#last-name').val("");
    $('#email-id').val("");
}