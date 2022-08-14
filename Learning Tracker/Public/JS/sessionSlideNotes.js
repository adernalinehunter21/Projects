function addOrEditNoteToSessionSlide(session_id) {
    var note_of_current_slide = ($('#current-slide-note-hidden' + session_id).html()).trim();
    var notes_div_id = "current-slide-note-container-" + session_id;
    var notes_editor_div_id = "slide-notes-editor-" + session_id;
    $('#' + notes_div_id).empty();
    $('#' + notes_div_id).append('<div id="' + notes_editor_div_id + '"></div>')

    quill = startQuill(notes_editor_div_id);

    buttons_div = document.createElement('div');
    buttons_div.setAttribute("class", "row ml-0 mr-0 mb-0 mt-1 p-0");
    buttons_div.setAttribute("style", "display:grid; grid-template-columns: 10fr 0.5fr 0.5fr;");
    $('#' + notes_div_id).append(buttons_div);

    blank_div = document.createElement('div');
    buttons_div.appendChild(blank_div);

    save_div = document.createElement('div');
    buttons_div.appendChild(save_div);

    if (note_of_current_slide) {
        update_button = document.createElement('button');
        update_button.setAttribute("type", "button");
        update_button.setAttribute("class", "btn btn-sm btn-outline-secondary mr-1");
        update_button.setAttribute("onclick", "handleUpdateNoteEvent(" + session_id + ")");
        update_button.innerHTML = "&nbsp;update&nbsp";
        save_div.appendChild(update_button);

        quill.root.innerHTML = note_of_current_slide;
    } else {
        save_button = document.createElement('button');
        save_button.setAttribute("type", "button");
        save_button.setAttribute("class", "btn btn-sm btn-outline-secondary mr-1");
        save_button.setAttribute("onclick", "handleNewNotesSaveEvent(" + session_id + ")");
        save_button.innerHTML = "&nbsp;save&nbsp";
        save_div.appendChild(save_button);
    }

    cancel_div = document.createElement('div');
    buttons_div.appendChild(cancel_div);

    cancel_button = document.createElement('button');
    cancel_button.setAttribute("id", "cancel_button");
    cancel_button.setAttribute("type", "button");
    cancel_button.setAttribute("class", "btn btn-sm btn-outline-danger");
    cancel_button.setAttribute("onclick", "handleCancelNoteEditEvent(" + session_id + ")");
    cancel_button.innerHTML = "cancel";
    cancel_div.appendChild(cancel_button);
}

function startQuill(notes_div_id = 'editor-container') {
    var toolbarOptions = [
        [{'size': ['small', false, 'large', 'huge']}],
        [{'font': fontNames}],
        [{'color': []}],
        ['bold', 'italic', 'underline', 'strike'],
        [{'list': 'ordered'}, {'list': 'bullet'}],
        ['clean']
    ];

    var quill = new Quill('#' + notes_div_id, {
        modules: {
            toolbar: toolbarOptions
        },
        theme: 'snow'
    });

    return quill;
}

function handleCancelNoteEditEvent(session_id) {
    var currentSlideNotesContainerDivId = "current-slide-note-container-" + session_id;
    var order = $('#' + currentSlideNotesContainerDivId).attr('order');
    var topic_id = $('#' + currentSlideNotesContainerDivId).attr('topic_id');
    var idOfHiddenDiv = "current-slide-note-hidden" + session_id;
    var note_of_current_slide = ($('#' + idOfHiddenDiv).html()).trim();
    if (note_of_current_slide) {
        $('#' + currentSlideNotesContainerDivId).html('<div id="current-slide-note-' + session_id + '" class="add-notes-text-box" onclick="addOrEditNoteToSessionSlide(' + session_id + ')">' + note_of_current_slide + '</div>');
    } else {
        //There was nothing so there is nothing to restore
        $('#' + currentSlideNotesContainerDivId).html('<div id="current-slide-note-' + session_id + '" class="add-notes-text-box" onclick="addOrEditNoteToSessionSlide('
                + session_id + ')"><i class="fas fa-notes-medical"></i> Click here to add your notes for the current slide</div>');
    }
}

function handleNewNotesSaveEvent(session_id) {
    var presentationImageDivId = 'presentationImage' + session_id;
    var topic_id = $('#' + presentationImageDivId).attr('topic_id');
    var order = $('#' + presentationImageDivId).attr('order');
    var notes = quill.root.innerHTML;

    var posting = $.post("/SlideNotes/addNotes", {
        "topic_id": topic_id,
        "order": order,
        "notes": notes
    });

    posting.done(function (response) {
        response = JSON.parse(response);
        if (response.status == "Success") {
            loadNotesOnSaveEvent(session_id, topic_id, order, notes, response.id);
            swal({
                title: "Done!",
                text: "Notes saved successfully",
                icon: "success",
            });
        } else if (response.status == "Fail") {
            swal({
                title: "Sorry!",
                text: "Notes seem to exists already",
                icon: "error",
            });
        } else {
            swal({
                title: "Sorry!",
                text: "Encountered an error while trying to save the notes. We request you to kindly try submitting again after reload of the page",
                icon: "error",
            });
        }
    });
}

function loadNotesOnSaveEvent(session_id, topic_id, order, note, slide_id) {
    topic_id = parseInt(topic_id);
    order = parseInt(order);
    var divIdOfTheSlideImage = 'presentationImage' + session_id;
    var slideImageSource = $('#' + divIdOfTheSlideImage).attr('src');

    var cardDiv = '<div class="card note-card box-shadow m-3" id="note-card' + slide_id + '" topic_id="' + topic_id + '" order="' + order + '">'
            + '<div class="row"><div class="col-1 offset-11"><i class="fas fa-trash note-delete-icon" title="Delete the note" onclick="deleteNote(' + slide_id + ')"></i></div></div>'
            + '<div class="card-body">'
            + '<img class="note-card-thumbnail" id="note-thumbnail-' + slide_id + '" src="' + slideImageSource + '">'
            + note
            + '</div>'
            + '</div>';

    var divIdOfTheNotesSection = 'session-notes-section-' + session_id;
    found_flag = false;
    $('#' + divIdOfTheNotesSection).children().each(function () {
        if (!found_flag) {
            if (topic_id < parseInt($(this).attr('topic_id'))) {

                $(cardDiv).insertBefore(this);
                found_flag = true;
            } else if (topic_id === parseInt($(this).attr('topic_id')) && order < parseInt($(this).attr('order'))) {

                $(cardDiv).insertBefore(this);
                found_flag = true;
            }
        }

    });
    if (!found_flag) {
        $('#' + divIdOfTheNotesSection).append($(cardDiv));
    }
    $('#' + "current-slide-note-container-" + session_id).html('<div id="current-slide-note-' + session_id + '" class="add-notes-text-box" onclick="addOrEditNoteToSessionSlide(' + session_id + ')">' + note + '</div>');
    $("#current-slide-note-hidden" + session_id).html(note);
}

function handleUpdateNoteEvent(session_id) {
    var divIdOfTheSlideImage = 'presentationImage' + session_id;
    var topic_id = parseInt($('#' + divIdOfTheSlideImage).attr('topic_id'));
    var order = parseInt($('#' + divIdOfTheSlideImage).attr('order'));
    var notes = quill.root.innerHTML;

    var posting = $.post("/SlideNotes/updateNotes", {
        "topic_id": topic_id,
        "order": order,
        "notes": notes
    });
    posting.done(function (response) {
        response = JSON.parse(response);
        if (response.status == "Success") {
            loadNotesOnUpdateEvent(session_id, response.id, notes);
            swal({
                title: "Done!",
                text: "Notes saved successfully",
                icon: "success",
            });
        } else {
            swal({
                title: "Sorry!",
                text: "Encountered an error while updating this note. We request you to kindly retry and report if this issue ever repeat",
                icon: "error",
            });
        }
    });
}

function loadNotesOnUpdateEvent(session_id, slide_id, notes) {
    var thumbnailImageLink = $('#note-thumbnail-' + slide_id).attr('src');
    $('#note-card' + slide_id).html(
            '<div class="row"><div class="col-1 offset-11"><i class="fas fa-trash note-delete-icon" title="Delete the note" onclick="deleteNote(' + slide_id + ')"></i></div></div>'
            +'<div class="card-body">'
            + '<img class="note-card-thumbnail" id="note-thumbnail-' + slide_id + '" src="' + thumbnailImageLink + '">'
            + notes
            + '</div>'
            );

    $('#' + "current-slide-note-container-" + session_id).html('<div id="current-slide-note-' + session_id + '" class="add-notes-text-box" onclick="addOrEditNoteToSessionSlide(' + session_id + ')">' + notes + '</div>');
    $("#current-slide-note-hidden" + session_id).html(notes);

}

function deleteNote(slide_id) {

    var topic_id = $('#note-card'+slide_id).attr("topic_id");
    var order = $('#note-card'+slide_id).attr("order");

    swal({
        title: "Are you sure?",
        text: "Once deleted, you will not be able to recover this Note!",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
    .then((willDelete) => {
        if (willDelete) {
            $.post("/SlideNotes/deleteNote", {
                "topic_id": topic_id,
                "order": order
            },
            function (response) {
                response = JSON.parse(response);
                if (response.status === "Success") {
                    loadOnDeleteOfNotes(slide_id);
                    swal({
                        title: "Done!",
                        text: "Note has been deleted successfully",
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
            )

        }
    });

}

function loadOnDeleteOfNotes(slide_id){
    $('#note-card'+slide_id).hide();
}

function notesExportModal(session_index) {
    $('#notes-layout-modal').attr('session_index', session_index);
    $('#notes-layout-modal').modal('show');
}

$('.formatCard').click(function (event) {
    selectedCard = $(this).attr('id');
    $('.formatCard').css({"background-color": "#fff"});
    $("#" + selectedCard).css({"background-color": "#ade2eb"});
});

function previewNotes() {
    var pageURL = $(location).attr("href");
    session_index = $('#notes-layout-modal').attr('session_index');

    formUrl = '/SlideNotes/previewSessionNotes' + '/' + session_index + '/' + selectedCard;
    window.open(formUrl, '_blank');
    $("#" + selectedCard).css({"background-color": "#fff"});
}

function downloadNotes() {
    var pageURL = $(location).attr("href");
    session_index = $('#notes-layout-modal').attr('session_index');
    
    formUrl = '/SlideNotes/exportSessionNotes' + '/' + session_index + '/' + selectedCard;
    window.open(formUrl, '_blank');
    $("#" + selectedCard).css({"background-color": "#fff"});
}