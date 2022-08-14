

$(document).ready(function () {
    $('#datetimepicker1').datepicker({
        format: "dd/mm/yyyy",
        language: "en",
        autoclose: true,
        todayHighlight: true
    });

    $(".dropdown-menu li a").click(function () {

        var classOfSelected = $(this).attr("class");
        var selectedValue = $(this).text();
        if (classOfSelected == "goal-or-activity-item") {
            $('#selected-goal-or-activity').text(selectedValue);
            if (selectedValue == "Goal") {
                $("#goal-input-item").removeClass('d-none');
                $("#goal-selection-item").addClass('d-none');
                $("#activity-input-item").addClass('d-none');
                $("#activity-closure-date-input-item").addClass('d-none');
            } else {
                $("#goal-input-item").addClass('d-none');
                $("#goal-selection-item").removeClass('d-none');
                $("#activity-input-item").removeClass('d-none');
                $("#activity-closure-date-input-item").removeClass('d-none');
            }
        } else if (classOfSelected == "selected-activity-status-item") {
            $('#selected-activity-status').text(selectedValue);
        }
    });
});
