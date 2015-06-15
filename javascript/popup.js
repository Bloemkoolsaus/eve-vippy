var popupSpeed = 100;
var popupContent = "";
var popupWidth = 0;
var popupHeight = 0;
var popupPreCloseHandler = null;
var popupPostCloseHandler = null;
var popupPostLoadHandler = null;

function showPopup(content, width, height, postCloseHandler, preCloseHandler, postLoadHandler)
{
	if (!content)
		content = "<p style='text-align: center;'>Loading</p>";

	popupContent = content;
	popupPreCloseHandler = preCloseHandler;
	popupPostCloseHandler = postCloseHandler;
	popupPostLoadHandler = postLoadHandler;

	if (width)
		popupWidth = width;
	if (height)
		popupHeight = height;

	popupStep1();
}

function popupStep1()
{
    addDiv("disabledPage");
	$("#disabledPage").css("width",$(document).width());
	$("#disabledPage").css("height",$(document).height());
	$("#disabledPage").fadeIn(popupSpeed, popupStep2);
}

function popupStep2()
{
	$("#disabledPage").click(function() { destroyPopup(); });
	addDiv("popup");
	loadPopup();
	setPopupPosition();
	$(window).bind('scroll', function() { setPopupPosition(); });
	$("#popup").slideDown(popupSpeed, popupPostLoadHandler);
}

function loadPopup()
{
	var popDiv = $("#popup");
	addDiv("popupHeader", popDiv);
	addDiv("popupContent", popDiv);
	addDiv("popupFooter", popDiv);
	setPopupHeader();
	setPopupContent();
	setPopupFooter();
}

function setPopupHeader()
{
	document.getElementById("popupHeader").innerHTML = "<button id='popupCloseBttn' onclick='destroyPopup(); return false;'> &nbsp;X&nbsp; </button>&nbsp;";
}

function setPopupContent(content,width,height)
{
	if (content)
		popupContent = content;
	if (width)
		popupWidth = width;
	if (height)
		popupHeight = height;

	$("#popupContent").html(popupContent);
	setPopupPosition();
}

function setPopupFooter()
{
}

function getPopupContent()
{
	return document.getElementById("popupContent").innerHTML;
}

function setPopupHeight(height)
{
	popupHeight = height;
	popupHeight += $("#popupHeader").height();
	popupHeight += $("#popupFooter").height();
	setPopupPosition(true);
}

function setPopupPosition(setToContent)
{
	if (popupWidth > 0)
		$("#popup").width(popupWidth);

	if (popupHeight > 0)
		$("#popup").height(popupHeight + $("#popupHeader").height());

	// Calculate Width
	var popWidth = $("#popup").width();
	var docWidth = $(window).width();
	var popLeft = Math.round(docWidth/2) - Math.round(popWidth/2);
	popLeft += $(window).scrollLeft();
	if (popLeft < 0)
		popLeft = 50;

	// Calculate Height
	var popHeight = $("#popup").height();
	var docHeight = $(window).height();
	var popTop = Math.round(docHeight/2) - Math.round(popHeight/2);
	popTop -= Math.round(popTop/2);
	popTop += $(window).scrollTop();
	if (popTop < 0)
		popTop = 50;

	// Set Position
	$("#popup").css("left", popLeft);
	$("#popup").css("top", popTop);
}

function destroyPopup(cancelCallback)
{
	if (!cancelCallback && popupPreCloseHandler != null)
		popupPreCloseHandler.call();

	$(window).unbind('scroll');
	popupContent = "";
	popupWidth = 0;
	popupHeight = 0;
	if (document.getElementById("popup"))
		$("#popup").slideUp(popupSpeed*1.5, function() { $("#popup").remove(); });
	if (document.getElementById("disabledPage"))
		$("#disabledPage").fadeOut(popupSpeed*1.5, function() { $("#disabledPage").remove(); });

	if (!cancelCallback && popupPostCloseHandler != null)
		popupPostCloseHandler.call();

	popupPreCloseHandler = null;
	popupPostCloseHandler = null;
	popupPostLoadHandler = null;
}

function showLoadingPopup()
{
    showPopup("Loading", 300, 100);
}

function addDiv(id, element)
{
    if (!element)
        element = $("body");

    element.append("<div id='"+id+"'></div>");
}