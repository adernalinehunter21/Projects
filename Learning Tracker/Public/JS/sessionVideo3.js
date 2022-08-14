/**
 * This function will take subjectId and get the programs and add to programs dropdown
 */
var uploadedVideoFile = null;
var uploadedThumbnailFile = null;
var uploadedTranscriptFile = null;
window.onload = function () {
    document.getElementById("programList").innerHTML = "<option>Loading...</option>";
    document.getElementById("sessionName").innerHTML = '<option>Sessions</option>';
    var subjectId = $('option').val();
    $.post("/SessionVideo/getProgramList", {
        data: {
            subjectId: subjectId
        }
    },
            function (response) {
                response = JSON.parse(response);
                if (response.status == "Success") {
                    var programDetails = response.programs;
                    programNameHtml = "";
                    programDetails.forEach(function (programName, index) {
                        programNameHtml += '<option selected>Programs</option>';
                        programName.forEach(function (oneProgramName, nameIndex) {
                            programNameHtml += '<option value = "' + oneProgramName.course_id + '">' + oneProgramName.course_name + '</option>';
                        });
                    });
                    document.getElementById("programList").innerHTML = programNameHtml;
                } else if (response.status == "Error") {
                    alert("Sorry!<br>Foorowing error happened: " + response.message);
                } else {
                    alert("Soory! <br>Received invalid response from server<br>We appologise and request you to report this to our technical team");
                }
            });

};
/**
 * This function will take courseId and get the sessions and add to sessions dropdown
 * this function will also add course session videos
 */

var currently_displayed_session_videos = [];
$("#programList").change(function () {
    document.getElementById("sessionName").innerHTML = "<option>Loading...</option>";
    var courseId = $(this).val();
    $("#topics").html("");
    $("#topicsHeading").html("");
    $("#uploadedSessionVideos").html("");
    $('#already-uploaded-videos').html("");
    $('#already-uploaded-videos-section').addClass('d-none');
    $('#upload-new-video').addClass('d-none');
    currently_displayed_session_videos = [];

    $.post("/SessionVideo/getSessionList", {
        data: {
            courseId: courseId
        }
    },
            function (response) {
                $('#upload-section').removeClass('d-none');
                response = JSON.parse(response);
                if (response.status == "Success") {
                    var sessionDetails = response.sessions;
                    var courseSessionVideos = response.course_session_videos;
                    currently_displayed_session_videos = courseSessionVideos;
                    if (courseSessionVideos.length > 0) {
                        $('#main-content-section').removeClass('d-none');
                        courseSessionVideos.forEach(function (oneSession, index) {
                            if (oneSession.session_videos.length > 0) {
                                displayOneSessionVideos(oneSession, index);
                            }
                        });

                        sessionNameHtml = "";
                        sessionDetails.forEach(function (sessionName, index) {
                            sessionNameHtml += '<option selected>Sessions</option>';
                            sessionName.forEach(function (oneSessionName, nameIndex) {
                                sessionNameHtml += '<option value = "' + oneSessionName.session_id + '">' + oneSessionName.session_name + '</option>';
                            });
                        });
                        document.getElementById("sessionName").innerHTML = sessionNameHtml;

                    }
                } else if (response.status == "Error") {
                    alert("Sorry!<br>Foorowing error happened: " + response.message);
                } else {
                    alert("Soory! <br>Received invalid response from server<br>We appologise and request you to report this to our technical team");
                }
            });
});

function displayOneSessionVideos(oneSession, index) {

    uploadedSessionVideosSection = document.getElementById('uploadedSessionVideos');

    uploadedSession = document.createElement('div');
    uploadedSession.setAttribute('id', 'uploadedSession' + oneSession.session_id);
    uploadedSession.setAttribute('class', 'mt-3');
    uploadedSessionVideosSection.appendChild(uploadedSession);

    session_label = document.createElement('label');
    session_label.setAttribute('for', 'session-card' + oneSession.session_id);
    session_label.innerHTML = oneSession.session_index + ". " + oneSession.session_name;
    uploadedSession.appendChild(session_label);

    session_card = document.createElement('div');
    session_card.setAttribute('id', 'session-card' + oneSession.session_id);
    session_card.setAttribute('class', 'card section2-cards p-2');
    uploadedSession.appendChild(session_card);

    session_card_row = document.createElement('div');
    session_card_row.setAttribute('class', 'row no-gutters m-0');
    session_card.appendChild(session_card_row);
    var sessionVideos = oneSession.session_videos;
    sessionVideos.forEach(function (oneSessionVideo, videoIndex) {

        videoIndexPlusOne = videoIndex + 1;
        sessionVideoId = oneSessionVideo.sessionVideoId;

        session_card_col = document.createElement('div');
        session_card_col.setAttribute('class', 'col-sm-2 col-md-4 my-1');
        session_card_row.appendChild(session_card_col);

        session_video_card = document.createElement('div');
        session_video_card.setAttribute('class', 'card rounded border-light shadow h-100 mx-1');
        session_card_col.appendChild(session_video_card);

        session_video_card_body = document.createElement('div');
        session_video_card_body.setAttribute('class', 'card-body text-center p-2');
        session_video_card.appendChild(session_video_card_body);


        session_video_delete_icon = document.createElement('i');
        session_video_delete_icon.setAttribute('class', 'far fa-trash-alt edit-delete-icon float-right');
        session_video_delete_icon.setAttribute('onclick', 'deleteVideo(' + oneSession.session_id + ', ' + sessionVideoId + ')');
        session_video_card_body.appendChild(session_video_delete_icon);

        if (oneSessionVideo.name !== null) {
            session_video_name = document.createElement('p');
            session_video_name.setAttribute('class', 'text-left');
            session_video_name.innerHTML = oneSessionVideo.name;
            session_video_card_body.appendChild(session_video_name);
        }

        if (oneSessionVideo.thumbnailLink === "") {

            session_video_thumbnail = document.createElement('i');
            session_video_thumbnail.setAttribute('class', 'fas fa-video fa-8x uploaded-session-video-icon text-center');
            session_video_thumbnail.setAttribute('data-count', videoIndexPlusOne);
        } else {
            //oneSessionCard += '<img class="card-image img-fluid mx-auto video-thumbnail" id="thumbnail' + sessionVideoId + '" src="' + oneSessionVideo.thumbnailLink + '" videoLink="' + oneSessionVideo.videoLink + '" onclick="getSessionVideo(' + sessionVideoId + ' )"/>';
            session_video_thumbnail = document.createElement('img');
            session_video_thumbnail.setAttribute('class', 'card-image img-fluid mx-auto video-thumbnail');
            session_video_thumbnail.setAttribute('src', oneSessionVideo.thumbnailLink);
        }
        session_video_thumbnail.setAttribute('id', 'thumbnail' + sessionVideoId);
        session_video_thumbnail.setAttribute('videoLink', oneSessionVideo.videoLink);
        session_video_thumbnail.setAttribute('onclick', 'getSessionVideo(' + sessionVideoId + ')');
        session_video_card_body.appendChild(session_video_thumbnail);

        if (oneSessionVideo.topics.length > 0) {

            session_video_topics_label = document.createElement('p');
            session_video_topics_label.setAttribute('class', 'text-left');
            session_video_topics_label.innerHTML = "Topics";
            session_video_card_body.appendChild(session_video_topics_label);

            session_video_topics = document.createElement('ul');
            session_video_topics.setAttribute('class', 'text-left');
            session_video_card_body.appendChild(session_video_topics);
            oneSessionVideo.topics.forEach(function (oneSessionTopic, topicIndex) {
                session_video_topic = document.createElement('li');
                session_video_topic.innerHTML = oneSessionTopic.name;
                session_video_topics.appendChild(session_video_topic);
            });
        }

    });

}

function deleteVideo(session_id, video_id) {
    var course_id = $('#programList').val();
    $.post("/SessionVideo/delete",
            {
                video_id: video_id,
                session_id: session_id,
                course_id: course_id
            },
            function (response) {
                response = JSON.parse(response);
                if (response.status == "Success") {
                    $('#programList').trigger('change');
                    swal({
                        title: "Done!",
                        text: "Video Removed successfully",
                        icon: "success",
                    });
                } else {
                    swal({
                        title: "Sorry!",
                        text: "Following error happened\n" + response.error,
                        icon: "error",
                    });
                }
            });
}

/**
 * This function will take sessionId and get the topics coverd for that sessionId
 */
$("#sessionName").change(function () {
    var sessionId = $(this).val();
    var already_uploaded_videos_of_session = [];
    currently_displayed_session_videos.forEach(function (oneSession, index) {
        if (oneSession.session_id == sessionId) {
            already_uploaded_videos_of_session = oneSession.session_videos;
        }
    });

    uploadedVideoFile = null;
    $('#upload-new-video').addClass('d-none')
    $('#already-uploaded-videos').html("");
    $('#already-uploaded-videos-section').addClass('d-none');
    videoFilePrefix = "SV" + sessionId + ("" + Math.random()).substring(2, 7);

    var videoButtonText = "Video";
    if (already_uploaded_videos_of_session.length > 0) {
        already_uploaded_video_divs = "";
        already_uploaded_videos_of_session.forEach(function (oneSessionVideo, videoIndex) {
            sessionVideoId = oneSessionVideo.sessionVideoId;

            if (oneSessionVideo.thumbnailLink === "") {
                videoIndexPlusOne = videoIndex + 1;
                already_uploaded_video_divs += '<div class="col-md-4 small-size-col-margin mb-1"><div class="card border-light h-100 card-padding-dash"><div class="card-body border rounded border-light shadow" style="text-align: center;"><p>' + oneSessionVideo.name + '</p><i class="fas fa-video fa-8x uploaded-session-video-icon" data-count="' + videoIndexPlusOne + '" src="' + oneSessionVideo.thumbnailLink + '" videoLink="' + oneSessionVideo.videoLink + '" onclick="getSessionVideo(' + sessionVideoId + ' )"></i>';
            } else {
                already_uploaded_video_divs += '<div class="col-md-4 small-size-col-margin mb-1"><div class="card border-light h-100 card-padding-dash"><div class="card-body border rounded border-light shadow" style="text-align: center;"><p>' + oneSessionVideo.name + '</p><img class="card-image img-fluid mx-auto video-thumbnail" id="thumbnail' + sessionVideoId + '" src="' + oneSessionVideo.thumbnailLink + '" videoLink="' + oneSessionVideo.videoLink + '" onclick="getSessionVideo(' + sessionVideoId + ' )"/>';
            }

            if (oneSessionVideo.topics.length > 0) {
                var topic = "";
                oneSessionVideo.topics.forEach(function (oneSessionTopic, topicIndex) {
                    topic += '<div class="row no-gutters"><div class="col-lg-12"><ul class="topic_list"><li class="objective-list module-section1-size">' + oneSessionTopic.name + '<br /></li></ul></div></div>';
                });
                already_uploaded_video_divs += '<div id="topics-' + sessionVideoId + '" style="text-align: left;">' + topic + '</div>';
            }
            already_uploaded_video_divs += '</div></div></div>';

        });
        $('#already-uploaded-videos').html(already_uploaded_video_divs);
        $('#already-uploaded-videos-section').removeClass('d-none');
        videoButtonText = 'Another Video';
    }
    $('#videoUpload').attr('sessionId', sessionId);
    $('#videoSession').html('<i class="fa fa-upload"></i> ' + videoButtonText);

    $('#upload-new-video').removeClass('d-none');

});

var sliders = [];

function videoUploadComplete(uploadedFile) {
    $('#videoSession').html('<i class="fa fa-paperclip" aria-hidden="true"></i> ' + uploadedFile.fileName);
    $('#videoSession').attr('title', "File is uploaded and ready for submission");

    uploadedVideoFile = uploadedFile;



    sessionId = $('#videoUpload').attr('sessionId');

    $("#topics").html("");
    $.post("/SessionVideo/getTopicListWithVideoDuration", {
        sessionId: sessionId,
        internalFileName: uploadedVideoFile['internalFileName']
    },
            function (response) {
                response = JSON.parse(response);
                if (response.status == "Success") {

                    var session_topic_mapping_rows = response.topics;
                    var durationOfTheVideoInSecs = parseInt(response.video_duration);

                    displayTopicsAndTimeSliders(session_topic_mapping_rows, durationOfTheVideoInSecs);

                    displayVideoNameSection();

                    displayThumbnailSection(sessionId);

                    displayTranscriptSection(sessionId);

                } else if (response.status == "Error") {
                    alert("Sorry!<br>Foorowing error happened: " + response.message);
                } else {
                    alert("Soory! <br>Received invalid response from server<br>We appologise and request you to report this to our technical team");
                }
            });
}

function displayTopicsAndTimeSliders(session_topic_mapping_rows, durationOfTheVideoInSecs) {
    topicsHeading = document.getElementById('topicsHeading');
    topicsHeading.innerHTML = "";

    heading4 = document.createElement('h4');
    heading4.setAttribute('class', 'card-top-sub-heading resource-size mb-0 mt-3');
    heading4.innerHTML = "Topics Covered";
    topicsHeading.appendChild(heading4);

    tpoic_note = document.createElement('p');
    tpoic_note.innerHTML = "* Please select the topics covered and indicate the time-window in the video where that topic is covered";
    topicsHeading.appendChild(tpoic_note);

    sliders = [];
    var topics_div = document.getElementById("topics");
    topics_div.innerHTML = "";
    session_topic_mapping_rows.forEach(function (session_topic_mapping_row) {
        topic_row = document.createElement('div');
        topic_row.setAttribute('class', 'row no-gutters mt-2');
        topic_row.setAttribute('session-topic-mapping-id', session_topic_mapping_row.id);
        topics_div.appendChild(topic_row);

        topic_col1 = document.createElement('div');
        topic_col1.setAttribute('class', 'col-12 col-md-4 col-lg-3');
        topic_row.appendChild(topic_col1);

        check_box = document.createElement('input');
        check_box.setAttribute('type', 'checkbox');
        check_box.setAttribute('id', 'formCheck-' + session_topic_mapping_row.id);
        check_box.setAttribute('name', 'topic-checkbox');
        check_box.setAttribute('value', session_topic_mapping_row.id);
        check_box.setAttribute('onclick', "topicClick('" + session_topic_mapping_row.id + "')");
        topic_col1.appendChild(check_box);

        topic_name = document.createElement('span');
        topic_name.innerHTML = ' ' + session_topic_mapping_row.name;
        topic_col1.appendChild(topic_name);

        topic_col2 = document.createElement('div');
        topic_col2.setAttribute('class', 'col-12 col-md-8 col-lg-9 pl-4 pr-4');
        topic_row.appendChild(topic_col2);

        slider = document.createElement('div');
        slider.setAttribute('id', 'time-span-' + session_topic_mapping_row.id);
        slider.setAttribute('class', 'pmd-range-slider pmd-range-tooltip d-none');

        topic_col2.appendChild(slider);

        sliders.push({
            id: session_topic_mapping_row.id,
            slider: slider
        });

        slider_div = document.getElementById('time-span-' + session_topic_mapping_row.id);
        noUiSlider.create(slider_div, {
            start: [0, durationOfTheVideoInSecs],
            connect: true,
            tooltips: [wNumb({decimals: 0}), wNumb({decimals: 0})],
            range: {
                'min': 0,
                'max': durationOfTheVideoInSecs
            }
        });
    });
}

function displayVideoNameSection() {
    $('#video-name-section').removeClass('d-none');
}

function displayThumbnailSection(sessionId) {
    uploadedThumbnailFile = null;
    $('#thumbnail-section').removeClass('d-none');
    $('#thumbnail-choice').attr('session_id', sessionId);
}

function thumbnailUploadComplete(uploadedFile) {
    $('#videoSessionThumbnail').html('<i class="fa fa-paperclip" aria-hidden="true"></i> ' + uploadedFile.fileName);
    $('#videoSessionThumbnail').attr('title', "File is uploaded and ready for submission");

    uploadedThumbnailFile = uploadedFile;
}

function displayTranscriptSection(sessionId) {
    uploadedTranscriptFile = null;
    $('#transcript-section').removeClass('d-none');
    $('#transcript-choice').attr('session_id', sessionId);
}

function transcriptUploadComplete(uploadedFile) {
    $('#videoSessionTranscript').html('<i class="fa fa-paperclip" aria-hidden="true"></i> ' + uploadedFile.fileName);
    $('#videoSessionTranscript').attr('title', "File is uploaded and ready for submission");

    uploadedTranscriptFile = uploadedFile;
}

function toggleSwitch(switch_div_id) {

    if (switch_div_id === 'uploaded-video-name-choice') {
        if ($('#' + switch_div_id).prop("checked")) {
            $('#uploaded-video-name').val('');
            $('#uploaded-video-name-col').removeClass('d-none');
        } else {
            $('#uploaded-video-name-col').addClass('d-none');
        }
    } else if (switch_div_id === 'thumbnail-choice') {
        if ($('#' + switch_div_id).prop("checked")) {
            thumbnailFilePrefix = "SVTh" + sessionId + ("" + Math.random()).substring(2, 7);
            $('#videoSessionThumbnail').html('<i class="fa fa-upload"></i><span> Overlay Image</span>');

            $('#thumbnail-upload-col').removeClass('d-none');
        } else {
            $('#thumbnail-upload-col').addClass('d-none');
        }
    } else if (switch_div_id === 'transcript-choice') {
        if ($('#' + switch_div_id).prop("checked")) {
            transcriptFilePrefix = "SVTr" + sessionId + ("" + Math.random()).substring(2, 7);
            $('#videoSessionTranscript').html('<i class="fa fa-upload"></i><span> Transcript</span>');

            $('#transcript-upload-col').removeClass('d-none');
        } else {
            $('#transcript-upload-col').addClass('d-none');
        }
    }
}

function topicClick(topicId) {

    if ($('#formCheck-' + topicId).prop("checked")) {
        $('#time-span-' + topicId).removeClass('d-none');
    } else {
        $('#time-span-' + topicId).addClass('d-none');
    }
}
/**
 * This function will take all video details, thumbnail and transcript details and send these details to updateVideos function to store into course session videos table
 */
function submitNewSessionVideoDetails() {
    var session_id = $("#sessionName").val();
    if (session_id === "Sessions") {
        swal({
            title: "Sorry!",
            text: "Please select one of the Session, upload its video and then submit again",
            icon: "error",
        });
        return;
    }
    var data = {
        session_id: session_id
    };
    var validation = "Passed";
    if (uploadedVideoFile === null) {
        swal({
            title: "Sorry!",
            text: "Please upload the video and submit again",
            icon: "error",
        });
        return;
    }
    var fileValidation = validateFileDetails(uploadedVideoFile);
    if (fileValidation.status === "Error") {
        swal({
            title: "Sorry!",
            text: "Encountered following error while checking uploaded video file:<br>" + fileValidation.error,
            icon: "error",
        });
        return;
    }
    data.video_file_details = fileValidation.file_details;

    if ($('#uploaded-video-name-choice').prop("checked")) {
        videoName = $('#uploaded-video-name').val();
        videoName = videoName.trim();
        if (videoName === "") {
            swal({
                title: "Sorry!",
                text: "Please enter the name for the video file and try again",
                icon: "error",
            });
            return;
        }
        data.video_name = videoName;
    }

    if ($('#thumbnail-choice').prop("checked")) {
        if (uploadedThumbnailFile === null) {
            swal({
                title: "Sorry!",
                text: "Please upload the Thumbnail and submit again",
                icon: "error",
            });
            return;
        }

        fileValidation = validateFileDetails(uploadedThumbnailFile);
        if (fileValidation.status === "Error") {
            swal({
                title: "Sorry!",
                text: "Encountered following error while checking uploaded Overlay image file:\n\n" + fileValidation.error,
                icon: "error",
            });
            return;
        }
        data.thumnail_file_details = fileValidation.file_details;
    }

    if ($('#transcript-choice').prop("checked")) {
        if (uploadedTranscriptFile === null) {
            swal({
                title: "Sorry!",
                text: "Please upload the Transcript and submit again",
                icon: "error",
            });
            return;
        }
        fileValidation = validateFileDetails(uploadedTranscriptFile);
        if (fileValidation.status === "Error") {
            swal({
                title: "Sorry!",
                text: "Encountered following error while checking uploaded Transcript file:\n\n" + fileValidation.error,
                icon: "error",
            });
            return;
        }
        data.transcript_file_details = fileValidation.file_details;
    }

    topics = [];
    $('input[name=topic-checkbox]').each(function () {
        if (this.checked) {
            id = $(this).val();
            slider_div = document.getElementById('time-span-' + id);
            value = slider_div.noUiSlider.get();
            topics.push({
                id: id,
                start: value[0],
                end: value[1]
            });
        }
    });
    if (topics.length !== 0) {
        data.topics = topics;
    } else {
        data.topics = [];
    }

    $.post("/SessionVideo/updateVideo",
            data,
            function (response) {
                response = JSON.parse(response);
                if (response.status == "Success") {
                    window.onbeforeunload = null;
                    //Reset the program drop-down list
                    $('#programList').trigger("change");
                    //reset the vodeo name section and hide
                    $('#uploaded-video-name').val('');
                    $('#uploaded-video-name-choice').removeProp('checked');
                    $('#video-name-section').addClass('d-none');
                    //reset the thumbnail btn and hide
                    $('#videoSessionThumbnail').html('<i class="fa fa-upload"></i><span> Overlay Image</span>');
                    $('#thumbnail-choice').removeProp('checked');
                    $('#thumbnail-section').addClass('d-none');
                    //reset the transcript btn and hide
                    $('#videoSessionTranscript').html('<i class="fa fa-upload"></i><span> Transcript</span>');
                    $('#transcript-choice').removeProp('checked');
                    $('#transcript-section').addClass('d-none');
                    swal({
                        title: "Done!",
                        text: "Thank you",
                        icon: "success",
                    });
                } else if (response.status == "Error") {
                    swal({
                        title: "Sorry!",
                        text: "Following error happened while trying to save new video and details:-\n\n" + response.error,
                        icon: "error",
                    });
                } else {
                    swal({
                        title: "Sorry!",
                        text: "Received an invalid response from the server. We request you to kindly try again and report if this repeats",
                        icon: "error",
                    });
                }
            }
    );
}

function validateFileDetails(file) {
    if (typeof file['fileName'] == 'undefined' || file['fileName'] == "") {
        return {
            status: "Error",
            error: "Invalid/Missing File Name"
        };
    }
    if (typeof file['internalFileName'] == 'undefined' || file['internalFileName'] == "") {
        return {
            status: "Error",
            error: "Invalid/Missing Internal File Name"
        };
    }
    if (typeof file['fileSize'] == 'undefined' || file['fileSize'] <= 0) {
        return {
            status: "Error",
            error: "Invalid File Size"
        };
    }
    if (typeof file['fileType'] == 'undefined' || file['fileType'] == "") {
        return {
            status: "Error",
            error: "Invalid File Type"
        };
    }
    return {
        status: "Success",
        file_details: file
    };
}

/**
 * 
 * This function is used to play a video on modal
 */
function getSessionVideo(sessionVideoId) {
    var videoLink = $('#thumbnail' + sessionVideoId).attr('videoLink');
    $('#videoModalSrc').attr("src", videoLink);
    $('#videoModalPlayer')[0].load();
    $('#videoModalPlayer')[0].play();
    $('#myModal').modal('show');
}

$('#myModal').on('hide.bs.modal', function () {
    $('#videoModalPlayer')[0].pause();
    $("#videoModalSrc").html("");
})
