$(document).ready(function() {
    $("div#mainmenu").append("<div id='sinterklaas'><img src='/images/sinterklaas/sint.png'></div>");
    $("div#mainmenu>ul").append('<li class="mainmenu right" rel="Sinterklaas"><a href="https://en.wikipedia.org/wiki/Sinterklaas" target="_blank">Sinterklaas</a></li>');
    $("div#infoheader-name").prepend('<a href="https://en.wikipedia.org/wiki/Sinterklaas" target="_blank">Sinterklaas</a> edition | ');

    $("#sinterklaas").css("top", $("div#mainmenu").position().top + $("div#mainmenu").outerHeight() - $("#sinterklaas").height() -1);
    $("#sinterklaas").css("left", $("#mainmenu").position().left - $("#sinterklaas").outerWidth()-10);
});
