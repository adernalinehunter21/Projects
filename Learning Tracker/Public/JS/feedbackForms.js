/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function feedbackForm(courseFbFormId, feedbackName) {
    $('#feedbackModalTitle').html(feedbackName);
    $('#feedbackModalBody').html("Loading, please wait......");
    $('#feedbackModalResult').html("");
    $('#feedbackSubmitBtn').attr('hidden', false);
    $('#feedbackModal').modal('show');
    $('#feedbackSubmitBtn').attr('courseFbFormId', courseFbFormId);
    $.getJSON('/Feedback/getQuestions/' + courseFbFormId, function (response) {
//        response = JSON.parse(response);
        if (response['status'] == "Success") {
            var data = response['data'];
            var feedbackModalBodyHtml = "";
            data.forEach(function (question, index) {
                var questionNumber = index + 1;
                feedbackModalBodyHtml += '<div class="text-left mb-3" >' + questionNumber + '. ' + question.question + '</div>';
                feedbackModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><textarea rows="5" id = "fb-question' + question.id + '" class="form-control feedback-answer" placeholder="Enter Answer here*" fb-question-id = "' + question.id + '"></textarea></div>';
            });
            $('#feedbackModalBody').html(feedbackModalBodyHtml);
        } else {
            $('#feedbackModalBody').html("Something went wrong!<br>Request you to kindly try again<br><br>If this repeats, we request you to kindly raise a support request and help us address the issue");
        }
    });


}

function feedbackFormSubmission() {
    var courseFbFormId = $('#feedbackSubmitBtn').attr("courseFbFormId");
    var answers = [];
    $('textarea.feedback-answer').each(function (index, object) {
        var divId = object.id;
        var id = $('#' + divId).attr('fb-question-id');
        var value = $('#' + divId).val();
        answers.push({
            id: id,
            answer: value
        });
    });

    $.post('/Feedback/update',
        {
            courseFbFormId: courseFbFormId,
            answers: answers
        },
        function (response) {
            response = JSON.parse(response);
            if (response.status == "Success") {

                $('#feedbackModalResult').html("Your feedback has been updated successfully, Thank you");
                $('#feedbackModalResult').removeClass('feedbackModalResultFailure');
                $('#feedbackModalResult').addClass('feedbackModalResultSuccess');
                $('#feedbackSubmitBtn').attr('hidden', true);

            } else {
                $('#feedbackModalResult').html("Sorry!<br>An error happened while trying to update your feedback.<br>We request you to kindly raise a support request for this");
                $('#feedbackModalResult').removeClass('feedbackModalResultSuccess');
                $('#feedbackModalResult').addClass('feedbackModalResultFailure');
            }
        }
    );
}

$('#feedbackModal').on('hidden.bs.modal', function () {
    $('#feedbackModalTitle').html("");
    $('#feedbackModalBody').html("");
    $('#feedbackModalResult').html("");
    if($('#feedbackModalResult').hasClass('feedbackModalResultSuccess')){
        $('#feedbackModalResult').removeClass('feedbackModalResultSuccess');
        location.reload();
    }else{
        $('#feedbackModalResult').removeClass('feedbackModalResultFailure');
    }
    
})
