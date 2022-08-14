var is_notes_section_open = false;

screen_width_global = window.innerWidth;

if (screen_width_global < 1007) {
    $('#side_bar').insertBefore('#exam_prep_section');
    $('#notes_content').insertBefore('#exam_prep_section');
}

$(document).ready(function () {
    $(window).resize(function () {
        screen_width_global = window.innerWidth;
        if (screen_width_global < 1007) {
            $('#side_bar').insertBefore('#exam_prep_section');
            $('#notes_content').insertAfter('#side_bar');
        } else {
            $('#side_bar').insertAfter('#main');
            $('#notes_content').insertAfter('#side_bar');
            setSideBarHeight();
        }
    });

});

function setSideBarHeight() {
    var height = document.getElementById("height").offsetHeight;
    document.getElementById("side_bar").style.maxHeight = height + 8 + "px";
    document.getElementById("notes_content").style.maxHeight = height + 8 + "px";
}
;

$(window).on("load", setSideBarHeight);

function expand_side_bar() {
    document.getElementById("expander").style.display = 'none';
    document.getElementById("contracter").style.display = 'inline-block';
    document.getElementById("notes_content").style.display = 'inline-block';
    document.getElementById("grid-container").style.gridTemplateColumns = '20fr 0.5fr 10fr';
    setSideBarHeight();
    is_notes_section_open = true;
}

function contract_side_bar() {
    document.getElementById("main").style.margin = "auto";
    document.getElementById("main").style.width = "100%";
    document.getElementById("main").style.height = "100%";
    document.getElementById("expander").style.display = 'inline-block';
    document.getElementById("contracter").style.display = 'none';
    document.getElementById("notes_content").style.display = 'none';
    document.getElementById("grid-container").style.gridTemplateColumns = '20fr 0.5fr';
    setSideBarHeight();
    is_notes_section_open = false;
}

function open_notes() {
    expand_side_bar();
}

function close_notes() {
    contract_side_bar();
}

function add_note(slide_image_id, mode) {
    expand_side_bar();

    if (mode == "presbar") {
        var image_element = document.getElementById(slide_image_id);
        var image_source = image_element.getAttribute("src");
        var image_topic_id = Number(image_element.getAttribute("topic_id"));
        var image_order = Number(image_element.getAttribute("order"));
    }

    if (mode == "notecard") {
        var image_element = document.getElementById(slide_image_id);
        var widthdiv = image_element.children[0];
        var thumbnail = widthdiv.children[1];
        var image_source = thumbnail.getAttribute("src");
        var image_topic_id = Number(image_element.getAttribute("topic_id"));
        var image_order = Number(image_element.getAttribute("order"));

        changeModuleSlideDuringNoteEdit(image_source, image_topic_id, image_order);
    }

    // check if note already exists

    var noteExists = false;

    var note_text = "";

    $('.note-card').each(function (i, obj) {
        var note_card_id = obj.getAttribute("id");
        var note_card_topic_id = obj.getAttribute("topic_id");
        var note_card_order = obj.getAttribute("order");
        if (image_topic_id == note_card_topic_id && image_order == note_card_order) {
            noteExists = true;
        }
        if (note_card_topic_id > image_topic_id) {
            if (note_card_order == image_order) {
                note_text = document.getElementById("note-text" + note_card_id).innerHTML;
            }

            return false;
        } else if (note_card_topic_id == image_topic_id) {
            if (note_card_order >= image_order) {
                if (note_card_order == image_order) {
                    note_text = document.getElementById("note-text" + note_card_id).innerHTML;
                }

                return false;
            }
        }
    });

    var notes_div = document.getElementById("pageLoadNotes");
    var notes_content_div = document.getElementById("notes_content");
    var edit_note_card = document.getElementsByClassName('edit-note-card')[0];

    if (notes_div.contains(edit_note_card)) {
        edit_note_card.remove();
    }

    $('.note-card').each(function (i, obj) {
        obj.style.display = "block";
    });

    createEditNoteCard(image_source, image_topic_id, image_order, notes_div, noteExists, note_text);

    $('.note-card').each(function (i, obj) {
        var note_card_id = obj.getAttribute("id");
        var note_card_topic_id = Number(obj.getAttribute("topic_id"));
        var note_card_order = Number(obj.getAttribute("order"));

        if (note_card_topic_id > image_topic_id) {
            $('#edit' + image_topic_id + image_order).insertBefore('#' + note_card_id);

            if (note_card_order == image_order) {
                note_text = document.getElementById("note-text" + note_card_id).innerHTML;
                obj.style.display = "none";
            }

            return false;
        } else if (note_card_topic_id == image_topic_id) {
            if (note_card_order >= image_order) {
                $('#edit' + image_topic_id + image_order).insertBefore('#' + note_card_id);

                if (note_card_order == image_order) {
                    note_text = document.getElementById("note-text" + note_card_id).innerHTML;
                    obj.style.display = "none";
                }

                return false;
            }
        }
    });

    autoScrollNotes('edit', image_topic_id, image_order);
}

function autoScrollNotes(mode, image_topic_id, image_order) {

    if (screen_width_global < 1007) {
        var editCard = document.getElementsByClassName("edit-note-card")[0];
        editCard.scrollIntoView();
        return false;
    }

    var notes_content_div = document.getElementById("notes_content");
    var notesContentTop = notes_content_div.offsetTop;

    var top_scroll_buffer = document.getElementById("top-scroll-buffer");
    top_scroll_buffer.style.height = 0;

    var bottom_scroll_buffer = document.getElementById("bottom-scroll-buffer");

    if (notes_content_div.contains(bottom_scroll_buffer)) {
        bottom_scroll_buffer.remove();
    }

    var mainTop = document.getElementById("main").offsetTop;
    var mainHeight = document.getElementById("main").offsetHeight;

    if (mode == 'edit') {
        var editCard = document.getElementsByClassName("edit-note-card")[0];
        var editCardTop = editCard.offsetTop;

        var heightOfNotesAbove = editCardTop - notesContentTop;

        var relpresentationsTop = document.getElementById("presentations").offsetTop;
        var presentationsTop = relpresentationsTop + mainTop;

        if (editCardTop < presentationsTop) {
            top_scroll_buffer.style.height = relpresentationsTop - heightOfNotesAbove + "px";
        } else {
            var bottom_scroll_buffer = document.createElement('div');
            bottom_scroll_buffer.setAttribute("id", "bottom-scroll-buffer");
            bottom_scroll_buffer.style.height = 0;
            notes_content_div.appendChild(bottom_scroll_buffer);
            bottom_scroll_buffer.style.height = mainHeight + "px";
        }

        notes_content_div.scrollTop = heightOfNotesAbove - relpresentationsTop;
    } else if (mode == "view") {
        var noteCard = document.getElementById(image_topic_id + image_order);
        if (noteCard != null) {
            var noteCardTop = noteCard.offsetTop;

            var heightOfNotesAbove = noteCardTop - notesContentTop;

            var relpresentationsTop = document.getElementById("presentations").offsetTop;
            var presentationsTop = relpresentationsTop + mainTop;

            if (noteCardTop < presentationsTop) {
                top_scroll_buffer.style.height = relpresentationsTop - heightOfNotesAbove + "px";
            } else {
                var bottom_scroll_buffer = document.createElement('div');
                bottom_scroll_buffer.setAttribute("id", "bottom-scroll-buffer");
                bottom_scroll_buffer.style.height = 0;
                notes_content_div.appendChild(bottom_scroll_buffer);
                bottom_scroll_buffer.style.height = mainHeight + "px";
            }

            notes_content_div.scrollTop = heightOfNotesAbove - relpresentationsTop;
        }
    }
}

function createEditNoteCard(image_source, image_topic_id, image_order, notes_div, noteExists, note_text) {
    edit_note_card = document.createElement('div');
    edit_note_card.setAttribute("id", "edit" + image_topic_id + image_order);
    edit_note_card.setAttribute("class", "edit-note-card card-body ml-2 mt-2 mr-2 mb-2");
    edit_note_card.style.border = "1px solid #ccc";
    notes_div.appendChild(edit_note_card);

    slide_thumbnail = document.createElement('img');
    slide_thumbnail.setAttribute("id", "slide_thumbnail");
    slide_thumbnail.setAttribute("class", "mt-0 mb-2 p-0");
    slide_thumbnail.setAttribute("style", "width: 50%; border: 1px solid #ccc;");
    slide_thumbnail.setAttribute("topic_id", image_topic_id);
    slide_thumbnail.setAttribute("order", image_order);
    slide_thumbnail.setAttribute("src", image_source);
    edit_note_card.appendChild(slide_thumbnail);

    editor_container_div = document.createElement('div');
    editor_container_div.setAttribute("id", "notes-editor-container");
    edit_note_card.appendChild(editor_container_div);

    quill = startQuill('notes-editor-container');

    buttons_div = document.createElement('div');
    buttons_div.setAttribute("class", "row ml-0 mr-0 mb-0 mt-2");
    buttons_div.setAttribute("style", "display:grid; grid-template-columns: 10fr 0.5fr 0.5fr;");
    edit_note_card.appendChild(buttons_div);

    blank_div = document.createElement('div');
    buttons_div.appendChild(blank_div);

    save_div = document.createElement('div');
    buttons_div.appendChild(save_div);

    if (noteExists) {
        update_button = document.createElement('button');
        update_button.setAttribute("type", "button");
        update_button.setAttribute("class", "btn btn-sm btn-outline-secondary mr-1");
        update_button.setAttribute("onclick", "handleUpdateNoteEvent()");
        update_button.innerHTML = "&nbsp;update&nbsp";
        save_div.appendChild(update_button);

        quill.root.innerHTML = note_text;
    } else {
        save_button = document.createElement('button');
        save_button.setAttribute("type", "button");
        save_button.setAttribute("class", "btn btn-sm btn-outline-secondary mr-1");
        save_button.setAttribute("onclick", "handleNewNotesSaveEvent()");
        save_button.innerHTML = "&nbsp;save&nbsp";
        save_div.appendChild(save_button);
    }

    cancel_div = document.createElement('div');
    buttons_div.appendChild(cancel_div);

    cancel_button = document.createElement('button');
    cancel_button.setAttribute("id", "cancel_button");
    cancel_button.setAttribute("type", "button");
    cancel_button.setAttribute("class", "btn btn-sm btn-outline-danger");
    cancel_button.setAttribute("onclick", "handleCancelNewNoteEvent()");
    cancel_button.innerHTML = "cancel";
    cancel_div.appendChild(cancel_button);
}

function delete_note(note_card_div_id) {
    swal({
        title: "Are you sure?",
        text: "Once deleted, you will not be able to recover this Note!",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
            .then((willDelete) => {
                if (willDelete) {
                    topic_id = $('#' + note_card_div_id).attr('topic_id');
                    order = $('#' + note_card_div_id).attr('order');
                    $.post("/SlideNotes/deleteNote", {
                        "topic_id": topic_id,
                        "order": order
                    },
                            function (response) {
                                response = JSON.parse(response);
                                if (response.status === "Success") {
                                    handleLoadNotesOnSaveEvent();
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

function startQuill(notes_div_id = 'notes-editor-container') {
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

function handleNewNotesSaveEvent() {
    var topic_id = document.getElementById("slide_thumbnail").getAttribute("topic_id");
    var order = document.getElementById("slide_thumbnail").getAttribute("order");
    var notes = quill.root.innerHTML;

    var posting = $.post("/SlideNotes/addNotes", {
        "topic_id": topic_id,
        "order": order,
        "notes": notes
    });

    posting.done(function (response) {
        response = JSON.parse(response);
        if (response.status == "Success") {
            handleLoadNotesOnSaveEvent();
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

function handleUpdateNoteEvent() {
    var topic_id = document.getElementById("slide_thumbnail").getAttribute("topic_id");
    var order = document.getElementById("slide_thumbnail").getAttribute("order");
    var notes = quill.root.innerHTML;

    var posting = $.post("/SlideNotes/updateNotes", {
        "topic_id": topic_id,
        "order": order,
        "notes": notes
    });
    posting.done(function (response) {
        response = JSON.parse(response);
        if (response.status == "Success") {
            handleLoadNotesOnSaveEvent();
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

function handleLoadNotesOnSaveEvent() {
    clearBuffers();
    var module_index = document.getElementById("module_index_here").getAttribute("title");

    var posting = $.post("/SlideNotes/moduleNotes", {
        "module_index": module_index
    });

    posting.done(function (response) {
        response = JSON.parse(response);
        if (response.status == "Success") {
            document.getElementById("pageLoadNotes").innerHTML = "";
            createSavedNotesDivs(response);
        } else if (response.status == "Error") {
            alert("Error. Note was not saved.");
        } else {
            alert("Error. Note was not saved");
        }
    });
}

function createSavedNotesDivs(response) {

    notes_div = document.getElementById("pageLoadNotes");

    for (i in response.module_notes) {

        createEachNoteDiv(response);
    }
}

function createEachNoteDiv(response) {
    note_card = document.createElement('div');
    note_card.setAttribute("class", "note-card card-body ml-2 mt-2 mr-2 mb-2 pt-1 row");
    note_card.setAttribute("id", response.module_notes[i].topic_id + response.module_notes[i].order);
    note_card.setAttribute("topic_id", response.module_notes[i].topic_id);
    note_card.setAttribute("order", response.module_notes[i].order);
    note_card.style.border = "1px solid #ccc";
    note_card.style.textAlign = "justify";
    notes_div.appendChild(note_card);

    note_card_content = document.createElement('div');
    note_card_content.style.width = "100%";
    note_card.appendChild(note_card_content);

    edit_delete_div = document.createElement("div");
    edit_delete_div.setAttribute("class", "edit-delete-note-row");
    note_card_content.appendChild(edit_delete_div);

    edit_delete_spacer = document.createElement("div");
    edit_delete_div.appendChild(edit_delete_spacer);

    edit_button = document.createElement("div");
    edit_button.setAttribute("class", "edit-delete-notes-icon");
    edit_button.setAttribute("title", "Edit Note");
    edit_button.innerHTML = '<i class="far fa-edit" title="Edit the note" onclick="add_note(' + response.module_notes[i].topic_id + response.module_notes[i].order + ',\'notecard\')"></i>';
    edit_delete_div.appendChild(edit_button);

    delete_button = document.createElement("div");
    delete_button.setAttribute("class", "edit-delete-notes-icon");
    delete_button.setAttribute("title", "Delete Note");
    delete_button.innerHTML = '<i class="fas fa-trash" title="Delete the note" onclick="delete_note(' + response.module_notes[i].topic_id + response.module_notes[i].order + ')"></i>';
    edit_delete_div.appendChild(delete_button);

    var image = document.createElement("IMG");
    image.setAttribute("src", response.module_notes[i].slide_link);
    image.style = "float:left";
    image.style.width = "150px";
    image.style.border = "1px solid #ccc";
    image.style.marginRight = "25px"
    note_card_content.appendChild(image);


    var note = document.createElement("div");
    note.setAttribute("id", "note-text" + response.module_notes[i].topic_id + response.module_notes[i].order);
    note.innerHTML = response.module_notes[i].note;
    note_card_content.appendChild(note);
}

function handleCancelNewNoteEvent() {
    clearBuffers();
    document.getElementsByClassName('edit-note-card')[0].style.display = 'none';
    quill.root.innerHTML = "some string now";
}

function clearBuffers() {
    $('.note-card').each(function (i, obj) {
        obj.style.display = "block";
    });
    var bottom_scroll_buffer = document.getElementById("bottom-scroll-buffer");
    var top_scroll_buffer = document.getElementById("top-scroll-buffer");
    var notes_content_div = document.getElementById("notes_content");
    if (notes_content_div.contains(top_scroll_buffer)) {
        top_scroll_buffer.style.height = 0;
    }
    if (notes_content_div.contains(bottom_scroll_buffer)) {
        bottom_scroll_buffer.style.height = 0;
    }
}
function open_popup(confirm_delete_id) {
    $('.popuptext').removeClass('show');
    var popup = document.getElementById(confirm_delete_id);
    popup.classList.add("show");
}

function close_popups(event) {
    if (event.target.classList[1] !== "fa-trash") {
        if (event.target.classList[1] !== "edit-delete-icons") {
            $('.popuptext').removeClass('show');
        }
    }
}

function notesExportModal(module_index) {
    $('#notes-layout-modal').attr('module_index', module_index);
    $('#notes-layout-modal').modal('show');
}

//variable to store the id of the card
var selectedCard;

function previewNotes() {
    var pageURL = $(location).attr("href");
    module_index = pageURL.split('/')[5];

    formUrl = '/SlideNotes/previewModuleNotes' + '/' + module_index + '/' + selectedCard;
    window.open(formUrl, '_blank');
    $("#" + selectedCard).css({"background-color": "#fff"});
}

function downloadNotes() {
    var pageURL = $(location).attr("href");
    module_index = pageURL.split('/')[5];
    formUrl = '/SlideNotes/exportModuleNotes' + '/' + module_index + '/' + selectedCard;
    window.open(formUrl, '_blank');
    $("#" + selectedCard).css({"background-color": "#fff"});
}

$('.formatCard').click(function (event) {
    selectedCard = $(this).attr('id');
    $('.formatCard').css({"background-color": "#fff"});
    $("#" + selectedCard).css({"background-color": "#ade2eb"});
});



//js for session notes
//Hide-show download button
$('.sessionDownloadButton').hide();

$('.showNotes').on('click', function () {
    $('.sessionDownloadButton').toggle();
});


function sessionNotesExportModal(session_index) {
    $('#notes-layout-modal').attr('session_index', session_index);
    $('#notes-layout-modal').modal('show');
}

function previewSessionNotes() {
    var pageURL = $(location).attr("href");
    session_index = $('#notes-layout-modal').attr('session_index');

    formUrl = '/SlideNotes/previewSessionNotes' + '/' + session_index + '/' + selectedCard;
    window.open(formUrl, '_blank');
    $("#" + selectedCard).css({"background-color": "#fff"});
}

function downloadSessionNotes() {
    var pageURL = $(location).attr("href");
    session_index = $('#notes-layout-modal').attr('session_index');

    formUrl = '/SlideNotes/exportSessionNotes' + '/' + session_index + '/' + selectedCard;
    window.open(formUrl, '_blank');
    $("#" + selectedCard).css({"background-color": "#fff"});
}

function startDownload(url) {
    window.location.assign(url);
}
