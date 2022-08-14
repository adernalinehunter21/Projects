/**
 * 
 * @returns {undefined}
 */
var slideIndex = 1;
function openModal(topicId, imageId, presentationImages) {
    var count = presentationImages.length;
    presentationImages.forEach(function (presentationImage, index) {

        var imageIndex = index + 1;
        var element = document.createElement("DIV");
        element.className = "mySlides";
        element.id = "slideImage" + imageIndex;
        element.setAttribute("caption", presentationImage.topic_name);
        element.innerHTML = '<div class="numbertext">' + imageIndex + '/' + count + '</div><img src="' + presentationImage.filePath + '" style="width:100%">';
        var div = document.getElementById("presrentationImageModalContent");
        div.appendChild(element);
    });

    document.getElementById("presrentationImageModal").style.display = "block";

    showSlides(slideIndex);
}

function closeModal() {
    document.getElementById("presrentationImageModal").style.display = "none";
}

function navigateToNextSlideOfSession(direction, sessionId, sessionIndex) {
    var currentSlideIndex = parseInt($('#currentSlideIndex' + sessionId).html());
    var order = $('#presentationImage' + sessionId).attr('order');
    var topicId = $('#presentationImage' + sessionId).attr('topic_id');
    var countOfSlides = parseInt($('#countOfSlides' + sessionId).html());
    if ((direction === "Next" && currentSlideIndex < countOfSlides)
            || (direction === "Previous" && currentSlideIndex > 1)) {
        $.post('/Presentation/get',
                {
                    order: order,
                    topicId: topicId,
                    type: "Session",
                    sessionId: sessionId,
                    direction: direction
                },
                function (response) {
                    response = JSON.parse(response);
                    if (response.status === "Success") {
                        var nextImageDetails = response.data;
                        var nextPresentationNavBtnDiv = "nextPresentationNavBtn" + sessionId;
                        var previousPresentationNavBtnDiv = "previousPresentationNavBtn" + sessionId;
                        if (direction === "Next") {
                            $('#currentSlideIndex' + sessionId).html(++currentSlideIndex);
                            $("#" + previousPresentationNavBtnDiv).show();
                            if (currentSlideIndex === countOfSlides) {
                                $("#" + nextPresentationNavBtnDiv).hide();
                            }
                        } else {
                            $('#currentSlideIndex' + sessionId).html(--currentSlideIndex);
                            $('#' + nextPresentationNavBtnDiv).show();
                            if (currentSlideIndex === 1) {
                                $("#" + previousPresentationNavBtnDiv).hide();
                            }
                        }
                        $('#presentationImage' + sessionId).attr('src', nextImageDetails.filePath);
                        $('#presentationImage' + sessionId).attr('order', nextImageDetails.order);
                        $('#presentationImage' + sessionId).attr('topic_id', nextImageDetails.topic_id);
                        var currentSlideNotesContainerDivId = "current-slide-note-container-" + sessionId;
                        if (nextImageDetails.note) {
                            $('#current-slide-note-hidden' + sessionId).html(nextImageDetails.note);
                            $('#' + currentSlideNotesContainerDivId).html('<div id="current-slide-note-' + sessionId + '" class="add-notes-text-box" onclick="addOrEditNoteToSessionSlide(' + sessionId + ')">' + nextImageDetails.note + '</div>');
                        } else {
                            $('#current-slide-note-hidden' + sessionId).html("");
                            $('#' + currentSlideNotesContainerDivId).html('<div id="current-slide-note-' + sessionId + '" class="add-notes-text-box" onclick="addOrEditNoteToSessionSlide('
                                    + sessionId + ')"><i class="fas fa-notes-medical"></i> Click here to add your notes for the current slide</div>');
                        }

                    } else if (response.status === "Error") {
                        alert("Sorry!<br>Foorowing error happened while retriving next slide. Please raise support request: " + response.message);
                    } else {
                        alert("Soory! <br>Received invalid response from server<br>We appologise and request you to report this to our technical team");
                    }

                });
    }
}

function plusSlides(n) {
    showSlides(slideIndex += n);
}

function currentSlide(n) {
    showSlides(slideIndex = n);
}

function showSlides(n) {
    var i;
    var slides = document.getElementsByClassName("mySlides");

    if (n > slides.length) {
        slideIndex = 1
    }
    if (n < 1) {
        slideIndex = slides.length
    }
    for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }

    slides[slideIndex - 1].style.display = "block";
    var shownImageDiv = document.getElementById("slideImage" + slideIndex);
    var captionText = shownImageDiv.getAttribute("caption");
    var captionDiv = document.getElementById("caption");
    captionDiv.innerHTML = captionText;
}

function navigateToNextSlideOfModule(direction, moduleIndex) {
    var currentSlideIndex = parseInt($('#currentSlideIndex' + moduleIndex).html());
    var order = $('#presentationImage' + moduleIndex).attr('order');
    var topicId = $('#presentationImage' + moduleIndex).attr('topic_id');
    var countOfSlides = parseInt($('#countOfSlides' + moduleIndex).html());
    if ((direction === "Next" && currentSlideIndex < countOfSlides)
            || (direction === "Previous" && currentSlideIndex > 1)) {
        $.post('/Presentation/get',
                {
                    order: order,
                    topicId: topicId,
                    type: "Module",
                    moduleIndex: moduleIndex,
                    direction: direction
                },
                function (response) {
                    response = JSON.parse(response);
                    if (response.status === "Success") {
                        var nextImageDetails = response.data;
                        var nextPresentationNavBtnDiv = "nextPresentationNavBtn" + moduleIndex;
                        var previousPresentationNavBtnDiv = "previousPresentationNavBtn" + moduleIndex;
                        if (direction === "Next") {
                            $('#currentSlideIndex' + moduleIndex).html(++currentSlideIndex);
                            $('#' + previousPresentationNavBtnDiv).removeAttr('hidden');
                            if (currentSlideIndex === countOfSlides) {
                                $('#' + nextPresentationNavBtnDiv).attr('hidden', true);
                            }
                        } else {
                            $('#currentSlideIndex' + moduleIndex).html(--currentSlideIndex);
                            $('#' + nextPresentationNavBtnDiv).removeAttr('hidden');
                            if (currentSlideIndex === 1) {
                                $('#' + previousPresentationNavBtnDiv).attr('hidden', true);
                            }
                        }
                        $('#presentationImage' + moduleIndex).attr('src', nextImageDetails.filePath);
                        $('#presentationImage' + moduleIndex).attr('order', nextImageDetails.order);
                        $('#presentationImage' + moduleIndex).attr('topic_id', nextImageDetails.topic_id);

                    } else if (response.status === "Error") {
                        alert("Sorry!<br>Foorowing error happened while retriving next slide. Please raise support request: " + response.message);
                    } else {
                        alert("Soory! <br>Received invalid response from server<br>We appologise and request you to report this to our technical team");
                    }
                });
    }
}

function changeModuleSlideDuringNoteEdit(image_source, image_topic_id, image_order) {
    moduleIndex = $('#module_index_here').attr('title');
    $('#presentationImage' + moduleIndex).attr('src', image_source);
    $('#presentationImage' + moduleIndex).attr('topic_id', image_topic_id);
    $('#presentationImage' + moduleIndex).attr('order', image_order);

    var posting = $.post("/Presentation/getSlideNumUnderModule", {
        "module_index": moduleIndex,
        "image_topic_id": image_topic_id,
        "image_order": image_order
    });

    posting.done(function (response) {
        response = JSON.parse(response);
        if (response.status == "Success") {
            var moduleIndex = $('#module_index_here').attr('title');
            $('#currentSlideIndex' + moduleIndex).html(response.slide_number);
            var countOfSlides = parseInt($('#countOfSlides' + moduleIndex).html());
            var nextPresentationNavBtnDiv = "nextPresentationNavBtn" + moduleIndex;
            var previousPresentationNavBtnDiv = "previousPresentationNavBtn" + moduleIndex;
            if (response.slide_number > 1) {
                $('#' + previousPresentationNavBtnDiv).removeAttr('hidden');
            } else {
                $('#' + previousPresentationNavBtnDiv).attr('hidden', true);
            }
            if (response.slide_number < countOfSlides) {
                $('#' + nextPresentationNavBtnDiv).removeAttr('hidden');
            } else {
                $('#' + nextPresentationNavBtnDiv).attr('hidden', true);
            }
        } else if (response.status == "Error") {
            alert("Error. Note was not saved.");
        } else {
            alert("Error. Note was not saved");
        }
    });
}