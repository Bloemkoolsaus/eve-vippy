function showExitFinder()
{
    $.ajax({
        url: "/map/"+$("#mapName").val()+"/exitfinder",
        data: { ajax: 1 },
        success: function(data) {
            showPopup(data, 500, 200, null, null, function() {
                setPopupHeight("auto");
            });
        }
    });
}

function exitFinderDistance()
{
    $("button#bttnFindExit>img").attr("src","/images/loading.gif");
    $.ajax({
        url: "/map/"+$("#mapName").val()+"/exitfinder",
        data: {
            find: $("input[name=findexit]").val(),
            ajax: 1
        },
        success: function(data) {
            $("#exitFinderForm").parent().html(data);
            setPopupHeight("auto");
        }
    });
}

function exitFinderSelect(solarSystemName)
{
    $.ajax({
        url: "/map/"+$("#mapName").val()+"/exitfinder/"+solarSystemName,
        data: { ajax: 1 },
        success: function(data) {
            $("#exitFinderForm").parent().html(data);
            setPopupHeight("auto");
        }
    });
}