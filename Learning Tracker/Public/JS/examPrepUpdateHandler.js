var selected_option = "";
var question_id = "";
function optionSubmitRequest() {
    
    selected_option = $("input[name='exam-prep-option']:checked").val();
    question_id = $("input[name='exam-prep-option']:checked").attr("question-id");
    $.post("/update.php", {
        update: "examPrepAnswer",
        data: {
            selected_option: selected_option,
            question_id: question_id
           
        }
    },
        function (response) {

            response = JSON.parse(response);
            if (response.status == "Success") {
                $("#btnSubmit" + question_id).attr("disabled", true);
                var examPrepTickMark = "";
                var txt = "Your option <b>" + response.option_type + "</b>"
                        +" is <b>" + response.correctness + "</b>"
                        +" so the score is <b>" + response.score+"</b>";
                document.getElementById("answer-status" + question_id).innerHTML = txt;
                var data = response.all_options;
                if(data.length > 0){
                    var temp = "<tr><th>Option</th><th>Correctnes</th><th>Score</th></tr>";
                    data.forEach(function(dt) {

                        temp += "<tr>";
                        temp += "<td>" + dt.option_type + "</td>";
                        temp += "<td>" + dt.option_correctness + "</td>";
                        temp += "<td>" + dt.option_score + "</td>" + "</tr>";

                    });
                    document.getElementById("tdata" + question_id).innerHTML = temp;
                } 
               examPrepTickMark += '<div class="col col-1 on-submission-tickmark"><i id = "check-{{ id }}" class="icon fa fa-check"></i></div>';
               document.getElementById("examPrepTickMark-" + question_id).innerHTML = examPrepTickMark;
            } else if (response.status == "Error") {
                alert("Sorry!<br>Foorowing error happened: " + response.message);
            } else {
                alert("Soory! <br>Received invalid response from server<br>We appologise and request you to report this to our technical team");
            }
        }
    );
    
    if (!$("input[name='exam-prep-option']:checked").val()) {
        alert('Please select one option before submitting!');
    }
    
}
