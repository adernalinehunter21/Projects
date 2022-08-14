
function submitNewCourse() {
    subject_id = $('#subjectList').val();
    if (subject_id == "") {
        swal({
            title: "Sorry!",
            text: "Please select the subject and try again",
            icon: "error",
        });
        return;
    }
    course_name = $('#course-name').val();
    course_name = course_name.trim();

    if (course_name === "") {
        swal({
            title: "Sorry!",
            text: "Name for the course is required! Please enter and submit again",
            icon: "error",
        });
    } else {
        selected_facilitators = [];
        count_of_facilitators = 0;
        $("#co-facilitators input[type='checkbox']").each(function () {

            if (this.checked) {
                facilitator_id = $(this).val();
                facilitaror_name = $('#facilitator-' + facilitator_id).html();
                selected_facilitators.push({
                    id: facilitator_id,
                    name: facilitaror_name
                });
            }
            count_of_facilitators++;
        });

        var social_media_links = [];
        var error = false;
        $('input[type="url"].social-media-link').each(function () {

            var social_media_name = $(this).attr('name');
            var social_media_link = $(this).val();
            var div_id = $(this).attr('id');
            social_media_link = social_media_link.trim();
            if (social_media_link) {
                if (!isUrlValid(social_media_link)) {
                    swal({
                        title: "Sorry!",
                        text: "Please enter valid link for " + social_media_name + " platform and try again",
                        icon: "error",
                    });
                    error = true;
                    return false;
                }
                var placement = $('#' + div_id + '-place').val();
                if (placement != "NAV_BAR" && placement != "COMMUNITY_PAGE" && placement != "BOTH") {
                    swal({
                        title: "Sorry!",
                        text: "Please select the placement for " + social_media_name + " platform and try again",
                        icon: "error",
                    });
                    error = true;
                    return false;
                }
                social_media_links.push(
                        {
                            platform: social_media_name,
                            link: social_media_link,
                            placement: placement
                        }
                );
                social_media_links[social_media_name] = social_media_link;
            }
        });
        if (!error) {
            createNewCourse(subject_id, course_name, selected_facilitators, social_media_links);
        }
    }
}

function createNewCourse(subject_id, course_name, co_facilitators, social_media_links) {
    $.post("/Course/addNew", {
        subject_id: subject_id,
        course_name: course_name,
        co_facilitators: co_facilitators,
        social_media_links: social_media_links
    },
            function (response) {
                response = JSON.parse(response);
                if (response.status === "Success") {
                    $('#subjectList').trigger("change");
                    swal({
                        title: "Done!",
                        text: "New course has been added successfully",
                        icon: "success",
                    });
                } else {
                    swal({
                        title: "Sorry!",
                        text: response.error,
                        icon: "error",
                    });
                }
            }
    );
}

function clearCreationSection() {
    $('#course-name').val("");

    $("#co-facilitators input[type='checkbox']").each(function () {
        $(this).prop('checked', false);
    });

}

$('#subjectList').change(function () {
    subject_id = $(this).val();
    $('#mainContentSection').html("");//Clear the deck first
    clearCreationSection();
    $.post("/Course/getCourses", {
        subject_id: subject_id
    },
            function (response) {
                response = JSON.parse(response);
                if (response.status === "Success") {
                    section = document.getElementById('mainContentSection');

                    row = document.createElement('div');
                    row.setAttribute('class', 'row no-gutters');
                    section.appendChild(row);

                    courses = response.data;
                    courses.forEach(function (course, index) {
                        col = document.createElement('div');
                        col.setAttribute('class', 'col col-sm-4 p-1')
                        row.appendChild(col);

                        card = document.createElement('div');
                        card.setAttribute('class', 'card h-100 course-card box-shadow');
                        card.setAttribute('id', 'course-card-' + course['id']);
                        col.appendChild(card);

                        card_header = document.createElement('div');
                        card_header.setAttribute('class', "card-header")
                        card_header.setAttribute('style', 'background-color:rgba(0,0,0,.03)!important;');
                        card.appendChild(card_header);

                        card_header_row = document.createElement('div');
                        card_header_row.setAttribute('class', 'row no-gutters');
                        card_header.appendChild(card_header_row);

                        course_name_col = document.createElement('div');
                        course_name_col.setAttribute('class', 'col-11');
                        card_header_row.appendChild(course_name_col);

                        course_name_p = document.createElement('p');
                        course_name_p.innerHTML = course['name'];
                        course_name_p.setAttribute('id', +course['id']);
                        course_name_col.appendChild(course_name_p);

                        delete_icon_col = document.createElement('div');
                        delete_icon_col.setAttribute('class', 'col-1');
                        delete_icon_col.innerHTML = '<i class="fas fa-trash edit-delete-icon" style="margin-left:20px;" id="trash' + course['id'] + '" onclick="deleteCourse(' + course['id'] + ')"></i>';
                        card_header_row.appendChild(delete_icon_col);

                        var social_media_links = course['social_media_links'];
                        if (social_media_links.length > 0) {
                            card_header_row2 = document.createElement('div');
                            card_header_row2.setAttribute('class', 'row no-gutters');
                            card_header.appendChild(card_header_row2);

                            card_header_row2_col = document.createElement('div');
                            card_header_row2_col.setAttribute('class', 'col');
                            card_header_row2.appendChild(card_header_row2_col);

                            social_media_links.forEach(function (social_media, index) {
                                var tooltip_text = social_media.platform;
                                if (social_media.position === "NAV_BAR") {
                                    tooltip_text += " link for top navigation bar";
                                } else if (social_media.position === "COMMUNITY_PAGE") {
                                    tooltip_text += " link for community page";
                                } else if (social_media.position === "COMMUNITY_PAGE") {
                                    tooltip_text += " link for top navigation bar and community page";
                                }

                                social_media_icon_link = document.createElement('a');
                                social_media_icon_link.setAttribute('href', social_media.link);
                                social_media_icon_link.setAttribute('class', 'px-1');
                                social_media_icon_link.setAttribute('target', 'blank');
                                card_header_row2_col.appendChild(social_media_icon_link);

                                social_media_icon = document.createElement('i');
                                social_media_icon.setAttribute('class', social_media.icon_classes);
                                social_media_icon.setAttribute('title', tooltip_text);
                                social_media_icon_link.appendChild(social_media_icon);

                            });

                        }

                        card_body = document.createElement('div');
                        card_body.setAttribute('class', 'card-body card-text');
                        card.appendChild(card_body);

                        if (course['sessions']['planned'] === 0) {
                            card_text = document.createElement('p');
                            card_text.innerHTML = "Course is yet to be planned. Please add sessions";
                            card_body.appendChild(card_text);

                        } else if (course['sessions']['completed'] === 0) {
                            card_text1 = document.createElement('p');
                            card_text1.innerHTML = "Number of Sessions planned: " + course['sessions']['planned'];
                            card_body.appendChild(card_text1);

                            card_text2 = document.createElement('p');
                            card_text2.innerHTML = "Course is yet to start";
                            card_body.appendChild(card_text2);
                        } else if (course['sessions']['completed'] < course['sessions']['planned']) {
                            card_text1 = document.createElement('p');
                            card_text1.innerHTML = "Number of Sessions completed: " + course['sessions']['completed'] + ' / ' + course['sessions']['planned'];
                            card_body.appendChild(card_text1);

                            card_text2 = document.createElement('p');
                            card_text2.innerHTML = "Course is in progress";
                            card_body.appendChild(card_text2);
                        } else {
                            card_text1 = document.createElement('p');
                            card_text1.innerHTML = "Number of Sessions completed: " + course['sessions']['completed'] + ' / ' + course['sessions']['planned'];
                            card_body.appendChild(card_text1);

                            card_text2 = document.createElement('p');
                            card_text2.innerHTML = "Course is completed";
                            card_body.appendChild(card_text2);
                        }

                        card_footer = document.createElement('div');
                        card_footer.setAttribute('class', 'card-footer');
                        card_footer.setAttribute('style', 'background-color:rgba(0,0,0,.03)!important;');
                        card.appendChild(card_footer);

                        card_footer_row = document.createElement('div');
                        card_footer_row.setAttribute('class', 'row no-gutters');
                        card_footer.appendChild(card_footer_row);

                        course_footer_col = document.createElement('div');
                        course_footer_col.setAttribute('class', 'col');
                        card_footer_row.appendChild(course_footer_col);

                        label = document.createElement('label');
                        label.setAttribute('for', 'facilitators-of-course-' + course['id']);
                        label.innerHTML = "Facilitators";
                        course_footer_col.appendChild(label);

                        facilitator_list = document.createElement('ul');
                        facilitator_list.setAttribute('id', 'facilitators-of-course-' + course['id']);
                        course_footer_col.appendChild(facilitator_list);
                        facilitators = course['facilitators'];
                        facilitators.forEach(function (facilitator) {
                            one_facilitator = document.createElement('li');
                            one_facilitator.innerHTML = facilitator['name'];
                            facilitator_list.appendChild(one_facilitator);
                        });

                    });
                    $('#main-content-section').removeClass('d-none');
                } else {
                    swal({
                        title: "Sorry!",
                        text: response.error,
                        icon: "error",
                    });
                }

            });
});

function deleteCourse(course_id) {
    swal({
        title: "Are you sure?",
        text: "Once deleted, you will not be able to recover this Course!",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
            .then((willDelete) => {
                if (willDelete) {
                    $.post("/Course/delete", {
                        course_id: course_id
                    },
                            function (response) {
                                response = JSON.parse(response);
                                if (response.status === "Success") {
                                    $('#subjectList').trigger("change");
                                    swal({
                                        title: "Done!",
                                        text: "Course has been deleted successfully",
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

function isUrlValid(url) {
    return /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(url);
}

$(function () {

    $(".field-wrapper .field-placeholder").on("click", function () {
        $(this).closest(".field-wrapper").find("input").focus();
    });

    $(".field-wrapper input").on("keyup", function () {
        var value = $.trim($(this).val());
        if (value) {
            $(this).closest(".field-wrapper").addClass("hasValue");
        } else {
            $(this).closest(".field-wrapper").removeClass("hasValue");
        }
    });

});