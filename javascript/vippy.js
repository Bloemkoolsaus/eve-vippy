var mousePosX = 0;
var mousePosY = 0;

$(document).ready(function() {
	setSubmenu();
	setDatePicker();
	setAutoCompletes();
	hideRegisterFormAntiBot();
	setMouseCoordinates();
	setMenuNotifications();
});
$(window).resize(function() {
	setMenuNotifications();
});

function setMenuNotifications()
{
	if ($("#menuNotifications").length > 0)
	{
		$("#menuNotifications").width($("#maincontainer").width());
        setTimeout(function() {
            $("#menuNotifications").slideDown('fast', function() {
                setLayoutPositions();
            });
        }, 1000);
	}
}
function setLayoutPositions()
{
	if ($("#signatureMap").length > 0)
		resizeMap();
}

function setMouseCoordinates()
{
	$(document).mousemove(function(e) {
		mousePosX = e.pageX;
		mousePosY = e.pageY;
	});
}

function hideRegisterFormAntiBot()
{
	if ($("#registerform").length > 0)
		$("#street").hide();
}

function setSubmenu()
{
	$("li.mainmenu").each(function() {
		$(this).mouseover(function() {
			$("#submenu"+$(this).attr("rel")).show();
		});
	});
	$("li.mainmenu").each(function() {
		$(this).mouseout(function() {
			$("#submenu"+$(this).attr("rel")).hide();
		});
	});
}

function setDatePicker()
{
	$("input[rel=datepicker]").each(function() {
		$(this).datepicker({
			dateFormat: 'dd-mm-yy',
			monthNames: ['Januari','Februari','March','April','May','June','July','August','September','October','November','December'],
			monthNamesShort: ['Jan','Feb','Mar','Apr','May','June','July','Aug','Sep','Okt','Nov','Dec'],
			dayNames: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
			dayNamesMin: ['Su', 'Mo', 'Tue', 'Wed', 'Th', 'Fr', 'Sa'],
			dayNamesShort: ['Su', 'Mo', 'Tue', 'Wed', 'Th', 'Fr', 'Sa'],
			firstDay: 1,
			showAnim: 'drop',
			showWeek: true
		});
	});
}


function setAutoCompletes()
{
    $("input[rel=autocomplete]").each(function() {
        setAutoComplete($(this))
    });
}
function setAutoComplete(elem)
{
    if (elem.hasAttr("data-init"))
        return false;

    var searchMinLength = 2;
    var searchDelay = 150;

    if (elem.hasAttr("minlength"))
        searchMinLength = elem.attr("minlength");

    if (elem.hasAttr("delay"))
        searchDelay = elem.attr("delay");

    var reqURL = "index.php?autocomplete=1&ajax=1&minsearchlen=" + searchMinLength;

    var field = elem.attr("id");
    if (elem.hasAttr("field")) {
        reqURL +="&field="+elem.attr("field");
        field = elem.attr("field");
    }

    if (elem.hasAttr("element"))
        reqURL +="&element="+elem.attr("element");

    if (elem.hasAttr("table"))
        reqURL +="&table="+elem.attr("table");

    if (elem.hasAttr("keyfield"))
        reqURL +="&keyfield="+elem.attr("keyfield");

    if (elem.hasAttr("namefield"))
        reqURL +="&namefield="+elem.attr("namefield");

    if (elem.hasAttr("limit"))
        reqURL +="&limit="+elem.attr("limit");

    elem.autocomplete({
        source: reqURL,
        minLength: searchMinLength,
        delay: searchDelay,
        select: function(event,ui) {
            console.log(ui);
            $("#"+field).val(ui.item.id);
            $("#"+field).trigger('change');
        }
    });
    elem.attr("data-init", "true");
}

function showHelp(help)
{
    var content = "<div class='help' style='padding: 1em; text-align: left;'>";
    content += "<img src='/images/default/information.png' align='left' style='margin-right: 15px; margin-bottom: 15px;' />";
    content += help + "</div>";

    content += "<div class='help' style='padding: 1em; text-align: center;'>";
    content += "<button type='button' onclick='destroyPopup();'> OK </button>";
    content += "</div>";

    showPopup(content, 500, 220);
    return false;
}

$.fn.hasAttr = function(attrName) {
    return this.attr(attrName) !== undefined;
};


function getRequestParameter(name)
{
    if (name=(new RegExp('[?&]'+encodeURIComponent(name)+'=([^&]*)')).exec(location.search))
        return decodeURIComponent(name[1]);

    return null;
}