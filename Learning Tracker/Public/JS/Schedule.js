
function addExpandCollapseIcon(rowDivId) {
    var session_id = $("#" + rowDivId).attr("session-id");
    var expandCollapseIconDiv = "session-expand-icon-" + session_id;
    var sessionDetailsDiv = "collapse-session" + session_id;

    if ($("#" + sessionDetailsDiv).hasClass("show")) {
        $("#" + expandCollapseIconDiv).addClass("fa fa-2x fa-angle-down");
    } else {
        $("#" + expandCollapseIconDiv).addClass("fa fa-2x fa-angle-right");
    }
}

function removeExpandCollapseIcon(rowDivId) {
    var session_id = $("#" + rowDivId).attr("session-id");
    var expandCollapseIconDiv = "session-expand-icon-" + session_id;
    $("#" + expandCollapseIconDiv).removeAttr("class");
}

function sessionHeadingRowClicked(rowDivId) {
    var session_id = $("#" + rowDivId).attr("session-id");
    var expandCollapseIconDiv = "session-expand-icon-" + session_id;
    var sessionDetailsDivId = "collapse-session" + session_id;

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

function recordingsSectionHeadingClicked(rowDivId) {
    var session_id = $("#" + rowDivId).attr("session-id");
    var expandCollapseIconDiv = "session-recording-expand-icon-" + session_id;
    var recordingSectionDiv = "session-recording-section-" + session_id;

    if ($("#" + recordingSectionDiv).hasClass("show")) {
        collapseRecordingsSection(recordingSectionDiv, rowDivId);
        $("#" + expandCollapseIconDiv).removeAttr("class").addClass("fa fa-2x fa-angle-double-right");
    } else {
        expandRecordingsSection(recordingSectionDiv, rowDivId)
        $("#" + expandCollapseIconDiv).removeAttr("class").addClass("fa fa-2x fa-angle-double-down");
    }
}

function collapseRecordingsSection(recordingSectionDiv, recordingsHeadingRowDivId) {
    $("#" + recordingSectionDiv).removeClass("show");
    $("#" + recordingsHeadingRowDivId).removeClass("recordings-section-expanded").addClass("recordings-section-collapsed");
}

function expandRecordingsSection(recordingSectionDiv, recordingsHeadingRowDivId) {
    $("#" + recordingSectionDiv).addClass("show");
    $("#" + recordingsHeadingRowDivId).removeClass("recordings-section-collapsed").addClass("recordings-section-expanded");
}

function addExpandCollapseIcon4SRS(rowDivId) {
    var session_id = $("#" + rowDivId).attr("session-id");
    var expandCollapseIconDiv = "session-recording-expand-icon-" + session_id;
    var recordingsSectionDiv = "session-recording-section-" + session_id;

    if ($("#" + recordingsSectionDiv).hasClass("show")) {
        $("#" + expandCollapseIconDiv).addClass("fa fa-2x fa-angle-double-down");
    } else {
        $("#" + expandCollapseIconDiv).addClass("fa fa-2x fa-angle-double-right");
    }
}

function removeExpandCollapseIcon4SRS(rowDivId) {
    var session_id = $("#" + rowDivId).attr("session-id");
    var expandCollapseIconDiv = "session-recording-expand-icon-" + session_id;
    $("#" + expandCollapseIconDiv).removeClass();
}

function sessionVideoIconClicked(iconDivId) {
    window.event.cancelBubble = true;
    var session_id = $("#" + iconDivId).attr("session-id");
    var sessionDetailsDivId = "collapse-session" + session_id;
    var sessionHeadingRowDivId = "session-heading-" + session_id;
    var expandCollapseIconDiv = "session-expand-icon-" + session_id;

    expandSessionDetails(sessionDetailsDivId, sessionHeadingRowDivId, expandCollapseIconDiv);

    var recordingsHeadingRowDivId = "session-recordings-heading-" + session_id;
    var recordingsSectionDivId = "session-recording-section-" + session_id;
    var recordingsSectionExpandIconDivId = "session-recording-expand-icon-" + session_id;
    expandRecordingsSection(recordingsSectionDivId, recordingsHeadingRowDivId, recordingsSectionExpandIconDivId);

    var videoSectionDivId = "video-section-" + session_id;
    $("#" + videoSectionDivId)[0].scrollIntoView();
}

function startDownload(url)
{
    window.location.assign(url);
}

function calenderInvite(calendar) {
    showWaitWithoutCloseOption("Calendar Invite Email being sent", "Please wait.....");
    $.post("/schedule/calenderInvite", {
        calender: calendar
    },
            function (response) {
                response = JSON.parse(response);
                if (response.status == "Success") {
                    showStatusWithCloseOptions("Success", response.message);
                } else if (response.status == "Error") {
                    showStatusWithCloseOptions("Error!", "Sorry!<br>Following error happened: " + response.message);
                } else {
                    showStatusWithCloseOptions("Error!", "Soory! <br>Received invalid response from server<br>We appologise and request you to report this to our technical team");
                }
            }
    );
}

function showWaitWithoutCloseOption(tittleMessage, bodyMessage) {
    $('#statusModalCloseIcon').hide();
    $('#statusModalFooter').hide();
    $('#statusModalTitle').html(tittleMessage);
    $('#statusModalBody').html(bodyMessage);
}


function showStatusWithCloseOptions(tittleMessage, bodyMessage){
    $('#statusModalCloseIcon').show();
    $('#statusModalFooter').show();
    $('#statusModalTitle').html(tittleMessage);
    $('#statusModalBody').html(bodyMessage);
}

function notesSectionHeadingClicked(rowDivId) {
    var session_id = $("#" + rowDivId).attr("session-id");
    var notesCollapseIconDivId = "notes-section-collapse-icon" + session_id;
    var notesSectionDiv = "session-notes-section-" + session_id;

    if ($("#" + notesSectionDiv).hasClass("show")) {
        collapseNotesSection(notesSectionDiv, rowDivId);
        $("#" + notesCollapseIconDivId).removeClass("fa fa-2x fa-minus").addClass("fa fa-2x fa-plus");
    } else {
        expandNotesSection(notesSectionDiv, rowDivId);
        $("#" + notesCollapseIconDivId).removeClass("fa fa-2x fa-plus").addClass("fa fa-2x fa-minus");
    }
}

function collapseNotesSection(notesSectionDiv, notesHeadingDivId) {
    $("#" + notesSectionDiv).removeClass("show");
    $("#" + notesHeadingDivId).removeClass("notes-section-expanded").addClass("notes-section-collapsed");
    
}

function expandNotesSection(notesSectionDiv, notesHeadingDivId) {
    $("#" + notesSectionDiv).addClass("show");
    $("#" + notesHeadingDivId).removeClass("notes-section-collapsed").addClass("notes-section-expanded");
}

function addExpandCollapseIcon4Notes(rowDivId) {
    var session_id = $("#" + rowDivId).attr("session-id");
    var expandCollapseIconDiv = "notes-section-collapse-icon" + session_id;
    var notesSectionDiv = "session-notes-section-" + session_id;

    if ($("#" + notesSectionDiv).hasClass("show")) {
        $("#" + expandCollapseIconDiv).removeClass("fa fa-2x fa-plus").addClass("fa fa-2x fa-minus");
    } else {
        $("#" + expandCollapseIconDiv).removeClass("fa fa-2x fa-minus").addClass("fa fa-2x fa-plus");
    }
}

function removeExpandCollapseIcon4Notes(rowDivId) {
    var session_id = $("#" + rowDivId).attr("session-id");
    var expandCollapseIconDiv = "notes-section-collapse-icon" + session_id;
    $("#" + expandCollapseIconDiv).removeAttr("class");
}