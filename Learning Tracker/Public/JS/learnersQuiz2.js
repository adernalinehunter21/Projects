function quizOptionSubmitRequest() {
    var quizAnswers = [];
    var i = 0;
    var questionId;
    var questionType;
    var validation = "passed";
    var quizId = $("#quizModalSubmit").attr('quiz-group-id');
    $('.quiz-question').each(function () {

        if (validation == "passed") {
            questionId = $(this).attr('question-id');
            questionType = $(this).attr('question-type');
            question = $(this).attr('question');
            if (questionType == "OBJECTIVE") {
                if (!$("input[name='quiz-option" + questionId + "']:checked").val()) {
                    swal({
                        title: "Sorry!",
                        text: "Following question is not answered please check\n" + question.replace( /<.*?>/g, '' ),
                        icon: "error",
                    });
                    validation = "failed";
                }
                quizAnswers[i++] = {
                    quizId: quizId,
                    question_id: questionId,
                    question_type: questionType,
                    value: $("input[name='quiz-option" + questionId + "']:checked").val(),
                    question: $('quiz-question' + questionId).text()
                };

            } else if (questionType == "MULTIPLE_CHOICE") {
                if (!$("input:checkbox[name='multi-choice" + questionId + "']").is(":checked")) {
                    swal({
                        title: "Sorry!",
                        text: "Following question is not answered please check\n" + question.replace( /<.*?>/g, '' ),
                        icon: "error",
                    });
                    validation = "failed";
                }
                var multi_choice_answer = [];
                $("input:checkbox[name='multi-choice" + questionId + "']").each(function () {
                    if ($(this).is(":checked")) {
                        multi_choice_answer.push({
                            choice_id: $(this).val(),
                            status: 'checked'
                        });
                    } else {
                        multi_choice_answer.push({
                            choice_id: $(this).val(),
                            status: 'not checked'
                        });
                    }
                });
                quizAnswers[i++] = {
                    quizId: quizId,
                    question_id: questionId,
                    question_type: questionType,
                    value: multi_choice_answer,
                    question: $('quiz-question' + questionId).text()
                }

            } else {
                if (!$("textarea[name='descriptive-answer" + questionId + "']").val()) {
                    swal({
                        title: "Sorry!",
                        text: "Following question is not answered please check\n" + question.replace( /<.*?>/g, '' ),
                        icon: "error",
                    });
                    validation = "failed";
                }
                quizAnswers[i++] = {
                    quizId: quizId,
                    question_id: questionId,
                    question_type: questionType,
                    value: $("textarea[name='descriptive-answer" + questionId + "']").val(),
                    question: $('quiz-question' + questionId).text()
                }

            }
        }
    })

    if (validation == "passed") {
        $.post("/Quiz/submitQuiz", {
            data: quizAnswers
        },
            function (response) {
                response = JSON.parse(response);
                    if (response.status == "Success") {
                        var quizDetails = response.quizDetails;
                        console.log(quizDetails);
                        var quizModalBodyHtml = "";
                        var questionIndex = 1;
                        var quizGroupName = quizDetails.quizGroupName;
                        var quizAnswerStatus = quizDetails.answerStatus;
                        var quizFooter = "";
                        $("#quizModalSubmit").attr("disabled", true);
                        swal({
                            title: "Done!",
                            text: "Your answers has been successfully submitted",
                            icon: "success",
                        });
                        $("#quizModalHeading").html("");
                        $("#quizModalBody").html("");
                        $("#quiz-footer").html("");
                        quizDetails.questions.forEach(function (oneQuestion, index) {
                            quizModalBodyHtml += '<div class="text-left mb-3 quiz-question" question-type = "' + oneQuestion.answer_type + '" id = "quiz-question' + oneQuestion.id + '" question-id = "' + oneQuestion.id + '" question = "' + oneQuestion.question + '"><div class="mainQuestion" style="margin-bottom:-17px!important;margin-top:20px;"><div class="questionNum" >' + questionIndex + '</div><div class="questionText">' + oneQuestion.question +'</div></div> ';
                            if (oneQuestion.answer_type == "OBJECTIVE") {
                                var objectiveQuizOptions = shuffle(oneQuestion.quiz_options);
                                objectiveQuizOptions.forEach(function (oneOption, optionIndex) {
                                    if (oneOption.id === oneQuestion.chosenOptionId) {
                                        quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="radio" value = "' + oneOption.id + '" checked/> ' + oneOption.option_value + '</div>';
                                    } else {
                                        quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="radio" value = "' + oneOption.id + '"  /> ' + oneOption.option_value + '</div>';
                                    }

                                });
                                if (oneQuestion.chosenOptionId == oneQuestion.correctOptionId) {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2 p-1" style="background-color:#d4edda;"><i class="fa fa-check mr-2"></i>CORRECT</div>';
                                } else {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2 p-1" style="background-color:#fce3e4;"><i class="fa fa-times mr-2"></i>WRONG</div>';
                                }
                            } else if (oneQuestion.answer_type == "MULTIPLE_CHOICE") {
                                var multipleQuizOptions = oneQuestion.quiz_options;
                                var chosenOptionIds = oneQuestion.chosenOptionIds;
                                multipleQuizOptions.forEach(function (oneOption, optionIndex) {
                                    if (chosenOptionIds.includes(oneOption.id)) {
                                        quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="checkbox" value = "' + oneOption.id + '" checked/> ' + oneOption.option_value + '</div>';
                                    } else {
                                        quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="checkbox" value = "' + oneOption.id + '" /> ' + oneOption.option_value + '</div>';
                                    }

                                });
                                var allFounded;
                                var a = oneQuestion.chosenOptionIds;
                                var b = oneQuestion.correctOptionIds;
                                // if length is  equal
                                if (a.length == b.length) {
                                    allFounded = b.every(ai => a.includes(ai));
                                }

                                if (allFounded) {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2 p-1" style="background-color:#d4edda;"><i class="fa fa-check mr-2"></i>CORRECT</div>';
                                } else {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2 p-1" style="background-color:#fce3e4;"><i class="fa fa-times mr-2"></i>WRONG</div>';
                                }
                            } else if (oneQuestion.answer_type == "DESCRIPTIVE") {
                                quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><textarea class="form-control"  placeholder="' + oneQuestion.enteredDescriptiveAnswer + '"  rows="5"  name="descriptive-answer' + oneQuestion.id + '" readonly></textarea></div>';
                            }
                            questionIndex = questionIndex + 1;
                        });
                        quizFooter += '<button class="btn btn-light" id = "btn-close" data-dismiss="modal" type="button">Close</button>';

                        document.getElementById("quizModalHeading").innerHTML = quizGroupName;
                        document.getElementById("quizModalBody").innerHTML = quizModalBodyHtml;
                        document.getElementById("quiz-footer").innerHTML = quizFooter;
                        if (quizAnswerStatus == "ANSWERED") {
                           var quizTick = '<div class="col col-1 on-submission-tickmark"><i class="icon fa fa-check"></i></div>';
                        }
                        document.getElementById('quizTickMark-'+quizId).innerHTML = quizTick;
                    } else if (response.status == "Error") {
                        swal({
                            title: "Sorry!",
                            text: response.message,
                            icon: "error",
                        });
                } else {
                        swal({
                            title: "Sorry!",
                            text: "Received invalid response from server \n We appologise and request you to report this to our technical team",
                            icon: "error",
                        });
                }
            }
        );
    }

}

$('#quizModal').on("hidden.bs.modal", function(){
    $("#quizModalHeading").html("");
    $("#quizModalBody").html("");
    $("#quiz-footer").html("");
});
