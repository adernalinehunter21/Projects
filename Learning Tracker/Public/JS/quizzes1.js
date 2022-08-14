function getQuizQuestions(quizId) {

    $.post("/Quiz/getQuizQuestions", {
            quizId: quizId
    },
            function (response) {
                response = JSON.parse(response);
                if (response.status == "Success") {
                    var quizDetails = response.quizDetails;
                    var quizModalBodyHtml = "";
                    var questionIndex = 1;
                    var quizGroupName = quizDetails.quizGroupName;
                    var quizFooter = "";
                    if (quizDetails['answerStatus'] == "UNANSWERED") {

                        quizDetails.questions.forEach(function (oneQuestion, index) {
                            quizModalBodyHtml += '<div class="text-left mb-3 quiz-question" question-type = "' + oneQuestion.answer_type + '" id = "quiz-question' + oneQuestion.id + '" question-id = "' + oneQuestion.id + '" question = "' + oneQuestion.question + '"><div class="mainQuestion" style="margin-bottom:-17px!important;margin-top:20px;"><div class="questionNum">' + questionIndex + '</div><div class="questionText">' + oneQuestion.question +'</div></div> ';
                            if (oneQuestion.answer_type == "OBJECTIVE") {
                                var objectiveQuizOptions = shuffle(oneQuestion.quiz_options);
                                objectiveQuizOptions.forEach(function (oneOption, optionIndex) {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="radio" class="radioButton" value = "' + oneOption.id + '" name="quiz-option' + oneQuestion.id + '"/> ' + oneOption.option_value + '</div>';
                                });
                            } else if (oneQuestion.answer_type == "MULTIPLE_CHOICE") {
                                var descriptiveQuizOptions = shuffle(oneQuestion.quiz_options);
                                descriptiveQuizOptions.forEach(function (oneOption, optionIndex) {
                                    quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><input type="checkbox" class="checkbox" value = "' + oneOption.id + '"  name="multi-choice' + oneQuestion.id + '"> ' + oneOption.option_value + '</div>';
                                });
                            } else if (oneQuestion.answer_type == "DESCRIPTIVE") {
                                quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><textarea class="form-control" placeholder="Enter Answer here*" rows="5"  name="descriptive-answer' + oneQuestion.id + '"></textarea></div>';
                            }
                            questionIndex = questionIndex + 1;
                        });
                        quizFooter += '<button class="btn btn-light" data-dismiss="modal" id = "btn-close" type="button">Close</button><button class="btn dasa-btn" type="submit" id = "quizModalSubmit" quiz-group-id="' + quizId + '" onclick="quizOptionSubmitRequest()">Submit</button>';
                    } else {

                        quizDetails.questions.forEach(function (oneQuestion, index) {
                            quizModalBodyHtml += '<div class="text-left mb-3 quiz-question" question-type = "' + oneQuestion.answer_type + '" id = "quiz-question' + oneQuestion.id + '" question-id = "' + oneQuestion.id + '" question = "' + oneQuestion.question + '"><div class="mainQuestion" style="margin-bottom:-17px!important;margin-top:20px;"><div class="questionNum" >' + questionIndex + '</div><div class="questionText">' + oneQuestion.question +'</div></div> ';
                            if (oneQuestion.answer_type == "OBJECTIVE") {
                                var objectiveQuizOptions = oneQuestion.quiz_options;
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
                                quizModalBodyHtml += '<div class="ml-3 mb-2 mt-2"><textarea class="form-control"  placeholder="' + oneQuestion.enteredDescriptiveAnswer + '"  rows="5" name="descriptive-answer' + oneQuestion.id + '" readonly></textarea></div>';
                            }
                            questionIndex = questionIndex + 1;
                        });
                        quizFooter += '<button class="btn btn-light" id = "btn-close" data-dismiss="modal" type="button">Close</button>';
                    }
                    document.getElementById("quizModalHeading").innerHTML = quizGroupName;
                    document.getElementById("quizModalBody").innerHTML = quizModalBodyHtml;
                    document.getElementById("quiz-footer").innerHTML = quizFooter;
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

/**
 * function which shuffles a array paseed as argument
 * here a is an array
 */

function shuffle(a){
	counter = 1;
	arr_len = a.length

	while(counter<arr_len){
		random_index_1 =Math.floor(Math.random()*arr_len);
		random_index_2 =Math.floor(Math.random()*arr_len);

		temp = a[random_index_1]
		a[random_index_1] = a[random_index_2]
		a[random_index_2] = temp

		counter ++;
	}
  return a;
}
