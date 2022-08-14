
$('#editProfile').on('submit', function (e) {
    e.preventDefault();
    e.stopPropagation();
    
    var profile_binary = $('#profile-pic-binary').val();
    var name = $("#inputName").val();
    var lastName = $("#lastName").val();
    var password = $("#inputPassword").val();
    var timezone = $("#timezone-list").val();
    var profile = quill.root.innerHTML;
    var linkedin_link = $("#linkedin-profile").val();
    var facebook_link = $("#facebook-profile").val();

    var posting = $.post("/profile/update",
    {
        update: "profile",
        data: {
            profile_pic_binary: profile_binary,
            name: name,
            lastName : lastName,
            password: password,
            timezone: timezone,
            profile: profile,
            linkedin_link: linkedin_link,
            facebook_link: facebook_link

        }
    }
    );

    posting.done(function (response) {
        response = JSON.parse(response);
        if (response.status == "Success") {
            alert("Changes saved");
            window.location.replace("/profile/show");

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
