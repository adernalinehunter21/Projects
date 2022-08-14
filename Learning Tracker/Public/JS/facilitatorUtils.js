/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$('#subjectList').change(function () {
    if($("#programList").length != 0) {
        $('#programList').html("<option>Loading...</option>");
        $('#upload-section').addClass('d-none');
        $('#main-content-section').addClass('d-none');
        subjectId = $(this).val();
        if(subjectId === ""){
            $('#programList').html("<option>Select</option>");
        }else{
            $.post("/Course/listForSubject",
                    {
                        subjectId: subjectId
                    },
                    function (response) {
                        response = JSON.parse(response);
                        if (response.status == "Success") {
                            var course_list = response.course_list;
                            $('#programList').html("<option>Select</option>");
                            course_list.forEach(function (course) {
                                $('#programList').append($("<option></option>")
                                        .attr("value", course.course_id)
                                        .text(course.course_name));
                            }
                            );
                        } else if (response.status == "Error") {
                            alert(response.message);
                        } else {
                            alert("Sorry!\nAn error happened while trying to load Program list for the selected subject. Please try again")
                        }
                    }
            );
        }
    }
    
});

