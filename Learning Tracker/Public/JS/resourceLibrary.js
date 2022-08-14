var SrchFlt = false, SrchBox = false, srchBoxFoc = false;
var sbar = $('#searchbar');
var sfilter = $('#SearchFilter');


if (sbar && sfilter) {
    sfilter.removeClass('popup');
    sfilter.hide();
    sfilter.removeClass('open');
    sbar.blur(function () {
        if (!SrchFlt) {
            sfilter.hide();
            sfilter.removeClass('open');
        }
        srchBoxFoc = false;
    });
    sbar.focus(function () {
        SrchFlt = false;
        srchBoxFoc = true;
        sfilter.show();
        sfilter.addClass('open');
    });
    sbar.mouseleave(function () {
        SrchBox = false;
    });
    sbar.mouseover(function () {
        SrchBox = true;
    });
    sfilter.mouseleave(function () {
        SrchFlt = false;
        if (!srchBoxFoc && !SrchBox) {
            sfilter.hide();
            sfilter.removeClass('open');
        }
    });
    sfilter.mouseover(function () {
        SrchFlt = true;
    });
}

//debounce function 
function debounce(func, wait, immediate) {
    var timeout;
    return function () {
        var context = this, args = arguments;
        var later = function () {
            timeout = null;
            if (!immediate)
                func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow)
            func.apply(context, args);
    };
};

//ajax request for searchbox event
var ajaxSearch = debounce(function () {

    var textEntered = $('#searchbar').val();
    var type = new Array();
    $("input[name=type]:checked").each(function () {
        type.push($(this).val());
    });
    var moduleSearch = new Array();
    $("input[name=moduleSearch]:checked").each(function () {
        moduleSearch.push($(this).val());
    });
    var sessionSearch = new Array();
    $("input[name=sessionSearch]:checked").each(function () {
        sessionSearch.push($(this).val());
    });
    if(textEntered.length == 0) {
        document.getElementById("searchMatch").innerHTML = "";
        document.getElementById("textSearched").innerHTML = "";
       return;
    }
    document.getElementById('textSearched').innerHTML = textEntered;
    $.post("/Resource/getResource", {
        data: {
            textEntered: textEntered,
            type: type,
            moduleSearch: moduleSearch,
            sessionSearch: sessionSearch
        }
    },
    function (response) {
        response = JSON.parse(response);
        var searchResources = response.search_resources;
        searchDetails = "";
        searchResources.forEach(function (serResult) {
            searchDetails += '<a class="mb-5 mt-5" href = "' + serResult.link + '" target="_blank"><i class = "' + serResult.icon + ' mr-1" style="font-size:15.5px;margin-top:3px;margin-bottom:5px;"></i>' + serResult.name + '</a><br>';
        });
        document.getElementById("searchMatch").innerHTML = searchDetails;
        if (searchResources.length == 0) {
            document.getElementById("searchMatch").innerHTML = "No results found!!";
        }
    });
}, 2000);

document.getElementById("searchbar").onkeyup = function (e) {
    ajaxSearch($("#searchbar").val());
    e.preventDefault();
    e.stopPropagation();
}


//ajax request for type checkbox event
$('input[type=checkbox][name=type]').change(function () {

    var textEntered = $('#searchbar').val();
    var type = new Array();
    $("input[name=type]:checked").each(function () {
        type.push($(this).val());
    });
    var moduleSearch = new Array();
    $("input[name=moduleSearch]:checked").each(function () {
        moduleSearch.push($(this).val());
    });
    var sessionSearch = new Array();
    $("input[name=sessionSearch]:checked").each(function () {
        sessionSearch.push($(this).val());
    });
    if(type.length == 0) {
        document.getElementById("type").innerHTML = "";
    }
    if(textEntered.length == 0){
        $('input[type=checkbox][name=type]').disabled = true;
        document.getElementById("searchMatch").innerHTML = "";
        return;
    }else{
        $('input[type=checkbox][name=type]').disabled = false;
    }
    
    $.post("/Resource/getResource", {
        data: {
            textEntered: textEntered,
            type: type,
            moduleSearch: moduleSearch,
            sessionSearch: sessionSearch
        }
    },
    function (response) {
        response = JSON.parse(response);
        var searchResources = response.search_resources;
        searchDetails = "";
        searchResources.forEach(function (serResult) {
            searchDetails += '<a class="mb-5 mt-5" href = "' + serResult.link + '" target="_blank"><i class = "' + serResult.icon + ' mr-1" style="font-size:15.5px;margin-top:3px;margin-bottom:5px;"></i>' + serResult.name + '</a><br>';
        });
        document.getElementById("searchMatch").innerHTML = searchDetails;
        if (searchResources.length == 0) {
            document.getElementById("searchMatch").innerHTML = "No Results found!!";
        }
    });
});


//ajax request for module checkbox event
$('input[type=checkbox][name=moduleSearch]').change(function () {
    
    var textEntered = $('#searchbar').val();
    var type = new Array();
    $("input[name=type]:checked").each(function () {
        type.push($(this).val());
    });
    var moduleSearch = new Array();
    $("input[name=moduleSearch]:checked").each(function () {
        moduleSearch.push($(this).val());
    });
    var sessionSearch = new Array();
    $("input[name=sessionSearch]:checked").each(function () {
        sessionSearch.push($(this).val());
    });
    
    if(moduleSearch.length == 0) {
        document.getElementById("moduleSearch").innerHTML = "";
    }
    if(textEntered.length == 0){
        $('input[type=checkbox][name=moduleSearch]').disabled = true;
        document.getElementById("searchMatch").innerHTML = "";
        return;
    }else{
        $('input[type=checkbox][name=moduleSearch]').disabled = false;
    }
    $.post("/Resource/getResource", {
        data: {
            textEntered: textEntered,
            type: type,
            moduleSearch: moduleSearch,
            sessionSearch: sessionSearch
        }
    },
    function (response) {
        response = JSON.parse(response);
        var searchResources = response.search_resources;
        searchDetails = "";
        searchResources.forEach(function (serResult) {
            searchDetails += '<a class="mb-5 mt-5" href = "' + serResult.link + '" target="_blank"><i class = "' + serResult.icon + ' mr-1" style="font-size:15.5px;margin-top:3px;margin-bottom:5px;"></i>' + serResult.name + '</a><br>';
        });
        document.getElementById("searchMatch").innerHTML = searchDetails;
        if (searchResources.length == 0) {
            document.getElementById("searchMatch").innerHTML = "No Results found!!";
        }
    });
});



//ajax request for session checkbox event
$('input[type=checkbox][name=sessionSearch]').change(function () {

    var textEntered = $('#searchbar').val();
    var type = new Array();
    $("input[name=type]:checked").each(function () {
        type.push($(this).val());
    });
    var moduleSearch = new Array();
    $("input[name=moduleSearch]:checked").each(function () {
        moduleSearch.push($(this).val());
    });
    var sessionSearch = new Array();
    $("input[name=sessionSearch]:checked").each(function () {
        sessionSearch.push($(this).val());
    });
    if(sessionSearch.length == 0) {
        document.getElementById("sessionSearch").innerHTML = "";
    }
    if(textEntered.length == 0){
        $('input[type=checkbox][name=sessionSearch]').disabled = true;
        document.getElementById("searchMatch").innerHTML = "";
        return;
    }else{
        $('input[type=checkbox][name=sessionSearch]').disabled = false;
    }
    $.post("/Resource/getResource", {
        data: {
            textEntered: textEntered,
            type: type,
            moduleSearch: moduleSearch,
            sessionSearch: sessionSearch
        }
    },
    function (response) {
        response = JSON.parse(response);
        var searchResources = response.search_resources;
        searchDetails = "";
        searchResources.forEach(function (serResult) {
            searchDetails += '<a class="mb-5 mt-5" href = "' + serResult.link + '" target="_blank"><i class = "' + serResult.icon + ' mr-1" style="font-size:15.5px;margin-top:3px;margin-bottom:5px;"></i>' + serResult.name + '</a><br>';
        });
        document.getElementById("searchMatch").innerHTML = searchDetails;
        if (searchResources.length == 0) {
            document.getElementById("searchMatch").innerHTML = "No Results found!!";
        }
    });
});
