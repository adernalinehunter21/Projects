$(function () {
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    $('#timezone').attr("value", tz);

    $(".field-wrapper .field-placeholder").on("click", function () {
        $(this).closest(".field-wrapper").find("input").focus();
    });
    $(".field-wrapper input").on("keyup", function () {
        var value = $.trim($(this).val());
        if (value) {
            $(this).closest(".field-wrapper").addClass("hasValue");
        } else {
            $(this).closest(".field-wrapper").removeClass("hasValue");
        }
    });

});
function optedToRemember() {
    if ($('#remember_me').prop("checked")) {
        $("#cookies_usage_confirmation").attr("hidden", false);
        $("#cookie_notification").attr("hidden", false);
        $('#cookies_usage_confirmation_checkbox').attr('required', true);
    } else {
        $("#cookies_usage_confirmation").attr("hidden", true);
        $("#cookie_notification").attr("hidden", true);
        $('#cookies_usage_confirmation_checkbox').attr('required', false);
    }

}
