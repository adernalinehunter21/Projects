/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var resourceData = null;//default there no resource data
var s3Details4fileUpload;
var resourceFilePrefix;
var resourceThumbnailPrefix;
var uploadedResourceFile = [];
var uploadedThumbnailFile = [];
function selectedProgram() {
    var courseId = $('#programList').val();
    var subjectId = $('#subjectList').val();
    $.post("/Resource/getConfiguredResourcesOfTheCourse",
            {
                courseId: courseId,
                subjectId: subjectId
            },
            function (response) {
                //Enable the upload section
                $('#upload-section').removeClass('d-none');
                $('#resource-for option:selected').removeAttr('selected');
                $("#resource-for").val("Program");
                selectedResourceFor();

                var responseArray = JSON.parse(response);
                resourceData = responseArray['resourceData'];
                s3Details4fileUpload = responseArray['s3Details4fileUpload'];
                resourceFilePrefix = responseArray['resourceFilePrefix'];
                resourceThumbnailPrefix = responseArray['resourceThumbnailPrefix'];
                var groupby = $('#groupby').val();

                var formattedData = formatData(resourceData, groupby);
                displayResorces(formattedData);
            }
    );
}

function formatData(data, groupby) {
    var values = {};
    if (groupby === "Sessions") {
        data.forEach(d => {
            var group;
            var groupIndex;

            var oneValue = {
                id: d.resource_id,
                name: d.name,
                thumbnail_source: d.thumbnail_source,
                thumbnail_link: d.thumbnail,
                source: d.source,
                document_link: d.link,
                tooltip: d.type,
                resource_is_of: d.resource_is_of
            };
            if (d.resource_for == "PROGRAM") {
                group = 'Program';
                groupIndex = 0;
            } else if (d.resource_for == "SESSION") {
                group = 'Session ' + d.session_index + ": " + d.session_name;
                groupIndex = parseInt(d.session_index) + 1;
            } else if (d.resource_for == "MODULE") {
                group = 'Module specific resourves';
                groupIndex = 1000;
            } else if (d.resource_for == "TOPIC") {
                group = 'Session ' + d.topic_session_index + ": " + d.topic_session_name;
                groupIndex = parseInt(d.topic_session_index) + 1;
            } else {
                group = 'WTF';
                groupIndex = 2000;
            }
            if (typeof values[groupIndex] === 'undefined') {
                values[groupIndex] = {
                    group: group,
                    resources: []
                };
            }

            values[groupIndex]['resources'].push(oneValue);
        }
        );
    } else if (groupby === "Modules") {
        data.forEach(d => {
            var group;
            var groupIndex;

            var oneValue = {
                id: d.resource_id,
                name: d.name,
                thumbnail_source: d.thumbnail_source,
                thumbnail_link: d.thumbnail,
                source: d.source,
                document_link: d.link,
                tooltip: d.type,
                resource_is_of: d.resource_is_of
            };
            if (d.resource_for == "PROGRAM") {
                group = 'Program';
                groupIndex = 0;
            } else if (d.resource_for == "SESSION") {
                group = 'Session specific';
                groupIndex = 1000;
            } else if (d.resource_for == "MODULE") {
                group = 'Module ' + d.module_index + ': ' + d.module_name;
                groupIndex = parseInt(d.module_index) + 1;
            } else if (d.resource_for == "TOPIC") {
                group = 'Module ' + d.topic_module_index + ": " + d.topic_module_name;
                groupIndex = parseInt(d.topic_module_index) + 1;
            } else {
                group = 'WTF';
                groupIndex = 2000;
            }
            if (typeof values[groupIndex] === 'undefined') {
                values[groupIndex] = {
                    group: group,
                    resources: []
                };
            }

            values[groupIndex]['resources'].push(oneValue);
        }
        );
    } else {
        data.forEach(d => {
            var type = d.type;
            var group;
            var groupIndex;

            var oneValue = {
                id: d.resource_id,
                name: d.name,
                thumbnail_source: d.thumbnail_source,
                thumbnail_link: d.thumbnail,
                source: d.source,
                document_link: d.link,
                resource_is_of: d.resource_is_of
            };
            group = type;
            groupIndex = type.split(" ").join("");

            if (d.resource_for == "PROGRAM") {
                oneValue.tooltip = "";
            } else if (d.resource_for == "SESSION") {
                oneValue.tooltip = "For Session " + d.session_index
                        + ": " + d.session_name;
            } else if (d.resource_for == "MODULE") {
                oneValue.tooltip = "For Module " + d.module_index
                        + ": " + d.module_name;
            } else if (d.resource_for == "TOPIC") {
                oneValue.tooltip = "For Topic: " + d.module_topic
                        + "(Module " + d.topic_module_index
                        + ")(Session " + d.topic_session_index
                        + ")";
            } else {
                oneValue.tooltip = "";
            }

            if (typeof values[groupIndex] === 'undefined') {
                values[groupIndex] = {
                    group: group,
                    resources: []
                };
            }

            values[groupIndex]['resources'].push(oneValue);
        }
        );
    }
    return values;
}

function displayResorces(data) {
    $('#mainContentSection').html("");
    $('#main-content-section').removeClass('d-none');
    for (var groupId in data) {

        var resources = data[groupId].resources;

        var resources_html = "";
        resources.forEach(resource => {
            var thumbnailDiv = '<img src="../../images/dummy-thumbnail.jpg" width="100%">';
            if(resource.thumbnail_source === "ICON"){
                thumbnailDiv = '<i class="' + resource.thumbnail_link + ' fa-6x" aria-hidden="true"></i>';
            }
            else if(resource.thumbnail_source === "EXTERNAL_LINK" || resource.thumbnail_source === "INTERNAL_FILE"){
                thumbnailDiv = '<img src="' + resource.thumbnail_link + '" width="100%">';
            }
            var oneResourceCard = '<div class="col-sm-4 col-md-3 col-lg-2 small-size-col-margin p-1">'
                    + '  <div class="card border-light h-100 card-padding-dash shadow" title="' + resource.tooltip + '">'
                    + '      <div class="row no-gutters ">'
                    + '         <div class="col-10 card-header">'
                    + '            <p class="card-title" resource_id = "'+ resource.id +'" style="margin-left:5px;margin-right:5px;">' + resource.name + '</p>'
                    + '         </div>'
                    + '         <div class="col-2 card-header">'
                    + '           <i class="far fa-trash-alt delete-icon" id="trash'+ resource.id +'" onclick="deleteResource('+ resource.id +',\''+resource.resource_is_of+'\')"></i>'
                    + '         </div>'
                    + '      </div>'
                    + '      <div class="card-body">'
                    + '         <a href= "' + resource.document_link + '" target="_blank">'
                    +               thumbnailDiv
                    + '         </a>'
                    + '      </div>'
                    + '  </div>'
                    + '</div>';
            resources_html += oneResourceCard;
        });

        var sectionDivId = "section-" + groupId;

        $('#mainContentSection').append('<div id="' + sectionDivId + '" class="mb-4"></div>');

        var sectionCardDivId = "section-card-" + groupId;
        var sessionHtml = '<div class="section-card-heading">'
                + data[groupId].group
                + '</div>'
                + '<div id="' + sectionCardDivId + '" class="card section-card p-2">'
                + '     <div class="row no-gutters">'
                + resources_html
                + '     </div>';
        +'</div>';
        document.getElementById(sectionDivId).innerHTML = sessionHtml;
        $('#groupby-selector').removeClass('d-none');

    }
}

function reorderResources() {
    var groupby = $('#groupby').val();

    var formattedData = formatData(resourceData, groupby);
    displayResorces(formattedData);
}

function selectedResourceFor() {
    var courseId = $('#programList').val();
    var resource_for = $('#resource-for').val();
    if (resource_for === "Session") {
        $('#select-module-dropdown').addClass('d-none');
        $('#select-topic-dropdown').addClass('d-none');
        $('#select-session-dropdown').removeClass('d-none');
        $('#select-session').html("<option>Loading...</option>");

        loadSessionsDropdown(courseId);
    } else if (resource_for === "Module") {
        var subjectId = $('#subjectList').val();
        $('#select-session-dropdown').addClass('d-none');
        $('#select-topic-dropdown').addClass('d-none');
        $('#select-module-dropdown').removeClass('d-none');

        $('#select-module').html("<option>Loading...</option>");

        loadModulesDropdown(subjectId);
    } else if (resource_for === "Topic") {
        var subjectId = $('#subjectList').val();
        $('#select-session-dropdown').addClass('d-none');
        $('#select-module-dropdown').removeClass('d-none');
        $('#select-topic-dropdown').removeClass('d-none');

        $('#select-module').html("<option>Loading...</option>");

        loadModulesDropdown(subjectId)
    } else {//Program
        $('#select-module').html("");
        $('#select-topic').html("");
        $('#select-session').html("");
        $('#select-module-dropdown').addClass('d-none');
        $('#select-topic-dropdown').addClass('d-none');
        $('#select-session-dropdown').addClass('d-none');
    }
}

function loadSessionsDropdown(courseId) {
    $.post("/Session/getSessionsOfTheCourse",
            {
                data: {
                    course_id: courseId
                }
            },
            function (response) {
                var sessions = JSON.parse(response);
                var sessionsHtml = "";
                sessions.forEach(function (session) {
                    sessionsHtml += '<option value="' + session.session_id + '">' + session.session_index + '. ' + session.session_name + '</option>';
                });
                $('#select-session').html(sessionsHtml);
            }
    );
}

function loadModulesDropdown(subjectId) {
    $.post("/Module/getModulesOfTheSubject",
            {
                data: {
                    subject_id: subjectId
                }
            },
            function (response) {
                var modules = JSON.parse(response);
                var modulesHtml = "";
                var firstModuleId = null;
                modules.forEach(function (module) {
                    if (firstModuleId === null) {
                        firstModuleId = module.module_id;
                    }
                    modulesHtml += '<option value="' + module.module_id + '">' + module.module_index + '. ' + module.module_name + '</option>';
                });
                $('#select-module').html(modulesHtml);
                loadTopicsDropdown(firstModuleId);
            }
    );
}

function selectedModule() {
    var resource_for = $('#resource-for').val();
    if (resource_for === "Topic") {
        var moduleId = $('#select-module').val();
        loadTopicsDropdown(moduleId);
    }
}

function loadTopicsDropdown(moduleId) {
    $('#select-topic').html("<option>Loading...</option>");
    $.post("/Topic/getTopicsOfTheModule",
            {
                data: {
                    module_id: moduleId
                }
            },
            function (response) {
                var topics = JSON.parse(response);
                var topicsHtml = "";
                topics.forEach(function (topic) {
                    topicsHtml += '<option value="' + topic.id + '">' + topic.order + '. ' + topic.name + '</option>';
                });
                $('#select-topic').html(topicsHtml);
            }
    );
}

function selectedResourceType() {
    var selectedType = $('#resource-type').val();
    if (selectedType === "addNew") {
        $('#resource-type-new-col').removeClass('d-none');
    } else {
        $('#resource-type-new').val("");
        $('#resource-type-new-col').addClass('d-none');
    }
}

function selectedResourceForm() {
    var formOfTheResource = $('#resource-form').val();
    if (formOfTheResource === "file-upload") {
        $('#resource-link-col').addClass('d-none');
        $('#resource-file-col').removeClass('d-none');
    } else if (formOfTheResource === "external-link") {
        $('#resource-file-col').addClass('d-none');
        $('#resource-link-col').removeClass('d-none');
        $('#resource-link').val("");
    }
}

function selectedThumbnailType() {
    var thumbnailType = $('#thumbnail-type').val();
    if (thumbnailType === "image-upload") {
        $('#thumnail-icon-col').addClass('d-none');
        $('#thumnail-link-col').addClass('d-none');
        $('#thumnail-image-col').removeClass('d-none');
    } else if (thumbnailType === "icon") {
        $('#thumnail-image-col').addClass('d-none');
        $('#thumnail-link-col').addClass('d-none');
        $('#thumnail-icon-col').removeClass('d-none');
    } else if (thumbnailType === "external-link") {
        $('#thumnail-image-col').addClass('d-none');
        $('#thumnail-icon-col').addClass('d-none');
        $('#thumnail-link-col').removeClass('d-none');
    }
}

// Default options
IconPicker.Init({
    // Required: You have to set the path of IconPicker JSON file to "jsonUrl" option. e.g. '/content/plugins/IconPicker/dist/iconpicker-1.5.0.json'
    jsonUrl: '../../json/iconpicker-1.5.0.json',
    // Optional: Change the buttons or search placeholder text according to the language.
    searchPlaceholder: 'Search Icon',
    showAllButton: 'Show All',
    cancelButton: 'Cancel',
    noResultsFound: 'No results found.', // v1.5.0 and the next versions
    borderRadius: '20px', // v1.5.0 and the next versions
});

IconPicker.Run('#GetIconPicker', function (argument) {
    $('#icon-pick-btn-text').html("");
});

function submitNewResource() {
    var course_id = $('#programList').val();
    var response = getResourceFor();
    if (response.status === "error") {
        swal({
            title: "Sorry!",
            text: response.message,
            icon: "error",
        });
    } else {
        var resource_for = response.value;
        response = getResourceDetails();
        if (response.status === "error") {
            swal({
                title: "Sorry!",
                text: response.message,
                icon: "error",
            });
        } else {
            var resource_details = response.value;
            response = getThumbnailsDetails();
            if (response.status === "error") {
                swal({
                    title: "Sorry!",
                    text: response.message,
                    icon: "error",
                });
            } else {
                $('#resource-submit-btn').attr('disabled', true);

                var thumbnail_details = response.value;
                $.post("/Resource/add",
                        {
                            data: {
                                course_id: course_id,
                                resource_for: resource_for,
                                resource_details: resource_details,
                                thumbnail_details: thumbnail_details
                            }
                        },
                        function (response) {
                            response = JSON.parse(response);
                            if (response.status === "Success") {
                                swal({
                                    title: "Done!",
                                    text: "Resource added to the course successfully",
                                    icon: "success",
                                });

                                selectedProgram();
                                resetUploadOption();

                            } else {
                                swal({
                                    title: "Sorry!",
                                    text: response.message,
                                    icon: "error",
                                });
                            }
                        }
                );
            }
        }
    }

}

function getResourceFor() {
    var resource_for = $('#resource-for').val();
    var possibleValues = ['Program', 'Session', 'Module', 'Topic'];
    if (!possibleValues.includes(resource_for)) {
        return {
            status: "error",
            message: "Invalid Resource for selection"
        };
    }
    var association = {
        for : resource_for
    };
    if (resource_for === "Program") {
        return {
            status: "success",
            value: association
        };
    } else {
        if (resource_for === "Session") {
            var session_id = $('#select-session').val();
            if (isNumeric(session_id)) {
                association['session_id'] = session_id;
                return {
                    status: "success",
                    value: association
                };
            } else {
                return {
                    status: "error",
                    message: "Invalid Session"
                };
            }
        } else if (resource_for === "Module") {
            var module_id = $('#select-module').val();
            if (isNumeric(module_id)) {
                association['module_id'] = module_id;
                return {
                    status: "success",
                    value: association
                };
            } else {
                return {
                    status: "error",
                    message: "Invalid Module"
                };
            }
        } else {
            var module_id = $('#select-module').val();
            if (!isNumeric(module_id)) {
                return {
                    status: "error",
                    message: "Invalid Module"
                };
            }
            var topic_id = $('#select-topic').val();
            if (isNumeric(topic_id)) {
                association['module_id'] = module_id;
                association['topic_id'] = topic_id;
                return {
                    status: "success",
                    value: association
                };
            } else {
                return {
                    status: "error",
                    message: "Invalid Topic"
                };
            }
        }
    }
}

function isNumeric(value) {
    if (typeof value === "number") {
        return true;
    } else if (typeof value === "string") {
        if (parseInt(value) > 0) {
            return true;
        }
    }
    return false;
}

function getResourceDetails() {
    var name = $('#resource-name').val().trim();
    if (name === "") {
        return {
            status: "error",
            message: "Please enter the Name!"
        };
    }


    var type = $('#resource-type').val();
    if (type === "addNew") {
        type = $('#resource-type-new').val();
    }
    if (typeof type !== "string") {
        return {
            status: "error",
            message: "Invalid Resource type"
        };
    }
    type = type.trim();
    if (type === "") {
        return {
            status: "error",
            message: "Invalid Resource type"
        };
    }

    var formOfTheResource = $('#resource-form').val();
    if (formOfTheResource === "file-upload") {
        if (uploadedResourceFile.length <= 0) {
            return {
                status: "error",
                message: "Resource file seem to be not uploaded yet"
            };
        }
        return {
            status: "success",
            value: {
                name: name,
                type: type,
                form: "File",
                uploadedResourceFiles: uploadedResourceFile
            }
        };
    } else if (formOfTheResource === "external-link") {
        var resourceLink = $('#resource-link').val();
        if (typeof resourceLink !== "string") {
            return {
                status: "error",
                message: "Invalid resource link"
            };
        }
        resourceLink = resourceLink.trim();
        if (resourceLink === "") {
            return {
                status: "error",
                message: "Empty resource link"
            };
        }
        return {
            status: "success",
            value: {
                name: name,
                type: type,
                form: "Link",
                resourceLink: resourceLink
            }
        };
    } else {
        return {
            status: "error",
            message: "Invalid source type"
        };
    }
}

function getThumbnailsDetails() {
    var thumbnailType = $('#thumbnail-type').val();

    if (thumbnailType === "image-upload") {
        if (uploadedThumbnailFile.length <= 0) {
            return {
                status: "error",
                message: "Thumbnail file seem to be not uploaded yet"
            };
        }
        return {
            status: "success",
            value: {
                type: thumbnailType,
                uploadedThumbnailFile: uploadedThumbnailFile
            }
        };
    } else if (thumbnailType === "icon") {
        var icon = $('#IconInput').val();
        icon = icon.trim();
        if (icon === "" || icon === "Pick") {
            return {
                status: "error",
                message: "Icon seem to be not picked yet"
            };
        }
        return {
            status: "success",
            value: {
                type: thumbnailType,
                icon: icon
            }
        };
    } else if (thumbnailType === "external-link") {
        var thumbnailLink = $('#thumnail-link').val();
        if (typeof thumbnailLink !== "string") {
            return {
                status: "error",
                message: "Invalid thumnail link"
            };
        }
        thumbnailLink = thumbnailLink.trim();
        if (thumbnailLink === "") {
            return {
                status: "error",
                message: "Empty thumnail link"
            };
        }
        return {
            status: "success",
            value: {
                type: thumbnailType,
                link: thumbnailLink
            }
        };
    }
}

function resetUploadOption(){
    uploadedThumbnailFile = [];
    uploadedResourceFile = [];
    $('#resource-name').val("");
    $('#resource-for').val("Program");
    $('#resource-type-new').val("");
    $('#resource-form').val("file-upload");
    $('#resource-file').html('<i class="fa fa-upload" aria-hidden="true"></i> Upload');
    $('#resource-link').val("");
    $('#thumbnail-type').val("image-upload");
    $('#thumbnail-file').html('<i class="fa fa-upload" aria-hidden="true"></i> Upload');
    $('#GetIconPicker').html('<span class="icon-picker-btn-content"><i id="IconPreview"></i><label id="icon-pick-btn-text">Pick</label></span>');
    $('#IconInput').val("");
    $('#thumnail-link').val("");
    $('#resource-submit-btn').removeAttr('disabled');
}

function deleteResource(resourceId,resource_is_of) {
    swal({
            title: "Are you sure?",
            text: "Once deleted, you will not be able to recover this Resource!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })

        .then((willDelete) => {
            if (willDelete) {
                $.post("/Resource/deleteResource", {
                        resourceId: resourceId,
                        resource_is_of: resource_is_of
                    },
                    function(response) {
                        response = JSON.parse(response);
                        if (response.status === "Success") {
                            selectedProgram();
                            swal({
                                title: "Done!",
                                text: "Resource has been deleted successfully",
                                icon: "success",
                            });
                        } else {
                            swal({
                                title: "Sorry!",
                                text: response.error,
                                icon: "error",
                            });
                        }
                    }
                )

            }
        });

}
