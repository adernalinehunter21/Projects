
$('#editProfile1').on('submit', function (e) {
    e.preventDefault();
    e.stopPropagation();
    
    var profile_binary = $('#profile-pic-binary').val();
    var name = $("#inputName").val();
    var lastName = $("#inputLastName").val();
    var password = $("#inputPassword").val();
    var timezone = $("#timezone-list").val();
    var calender = $("#calender-list").val();
    var linkedin_link = $("#linkedin-profile").val();
    var facebook_link = $("#facebook-profile").val();
    var profile = quill.root.innerHTML;
    
    var posting = $.post("/FirstLogin/updateProfile", {
        update: "profile",
        data: {
            profile_pic_binary: profile_binary,
            name: name,
            lastName : lastName,
            password: password,
            timezone: timezone,
            calender: calender,
            profile: profile,
            linkedin_link: linkedin_link,
            facebook_link: facebook_link

        }
    });

    posting.done(function (response) {
        response = JSON.parse(response);
        if (response.status == "Success") {
            
            $('#confirmModal').modal({
                backdrop: 'static',
                keyboard: false
            })
            .on('click', '#sendCalenderInvite', function (e) {
                $('.modal-title').html("Sending email invitition");
                $('.modal-body').html("<p>Please wait.......</p>");
                $('#sendCalenderInvite').hide();
                $('#goToHome').hide();
                
                $('#confirmModal').modal({
                    backdrop: 'static',
                    keyboard: false
                });
                var calendar = $("#calender-list").val();
                $.post("/schedule/calenderInvite", 
                    {
                        calender: calendar
                    },
                    function (response) {
                        response = JSON.parse(response);
                        if (response.status == "Success") {
                            
                            $('.modal-title').html("Sent email invitition successfully");
                            $('.modal-body').html("<p>Please check your email inbox after a minute</p>");
                            $('#goToHome').html("Ok");
                            $('#goToHome').show(true);
                            
                        } else{
                            $('.modal-title').html("Sending email invitition failed");
                            $('.modal-body').html("<p>An error happened, we suggest you to proceed to home page now</p><p>You will be able to initiate fresh calender invite under the Schedule page</p>");
                            $('#goToHome').html("Ok");
                            $('#goToHome').show(true);
                        }
                    }
                );
            }).on('click', '#goToHome', function (e) {
                window.location.replace("/home/index");
            });
        } else if (response.status == "Error") {
            alert("Sorry!<br>Foorowing error happened: " + response.message);
        } else {
            alert("Soory! <br>Received invalid response from server<br>We appologise and request you to report this to our technical team");
        }
    });

});

$('#timezone-region-list').change(function () {
    var val = $('#timezone-region-list').val();
    var timeZoneList = timeZones[val];
    $("#timezone-list").html("");
    var x = document.getElementById("timezone-list");
    Object.keys(timeZoneList).forEach(function (k) {
        var option = document.createElement("option");
        option.setAttribute("value", k); 
            option.text = timeZoneList[k].text;
            x.add(option);
    });
});