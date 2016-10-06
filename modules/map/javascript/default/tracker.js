function refreshCharacterLocation(characterID)
{
    $("#characterRefreshSelector").hide();

    if (characterID)
    {
        var icon = $("#bttnRefreshCharacterLocation>img").attr("src");
        setRefreshCharacterBttnIcon("/images/loading.gif");
        $.ajax({
            url: "/crest/character/location/"+characterID,
            data: {
                ajax: 1
            },
            success: function(data) {
                data = $.parseJSON(data);
                if (data.errors == null) {
                    setRefreshCharacterBttnIcon("/images/default/apply.png");

                    var id = "tracker"+$.now();
                    $("#mapButtons").before("<div class='success' id='"+id+"'><div><b>Character Refreshed: </b> "+data.character.name+" updated to "+data.system.name+"</div></div>");

                    setTimeout(function() {
                        setRefreshCharacterBttnIcon(icon);
                        $("#"+id).fadeOut(400, "swing", function() { $("#"+id).remove() });
                    }, 15000);
                } else {
                    setRefreshCharacterBttnIcon("/images/default/delete.png");
                    var id = "tracker"+$.now();
                    $("#mapButtons").before("<div class='error' id='"+id+"'><div><b>Character Refresh failed: </b> "+data.errors.join("<br />")+"</div></div>");
                    setTimeout(function() {
                        setRefreshCharacterBttnIcon(icon);
                        $("#"+id).fadeOut(400, "swing", function() { $("#"+id).remove() });
                    }, 15000);
                }
            }
        });
    }
    else
    {
        var id = "tracker"+$.now();
        $("#mapButtons").before("<div class='warning' id='"+id+"'><div><b>No character selected: </b> Please select a scan-alt in your <a href='/profile/account'>profile</a></div></div>");
        setTimeout(function() {
            $("#"+id).fadeOut(400, "swing", function() { $("#"+id).remove() });
        }, 15000);
    }
}

function setRefreshCharacterBttnIcon(icon)
{
    $("#bttnRefreshCharacterLocation>img").attr("src", icon);
}

function openRefreshCharacterSelector()
{
    if ($("#characterRefreshSelector").is(":visible"))
    {
        $("#characterRefreshSelector").hide();
    }
    else
    {
        $("#characterRefreshSelector").show();
        $("#characterRefreshSelector").css("top", $("#bttnRefreshCharacterLocation").position().top + $("#bttnRefreshCharacterLocation").outerHeight());
        $("#characterRefreshSelector").css("left", $("#bttnRefreshCharacterLocation").position().left);
    }
}