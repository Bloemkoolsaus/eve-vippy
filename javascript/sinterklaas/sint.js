$(document).ready(function() {
    $("#maincontainer").append("<div id='zwarte-piet-1'><img src='/images/sinterklaas/zwartepiet.png'></div>");
    $("#maincontainer").append("<div id='zwarte-piet-2'><img src='/images/sinterklaas/zwartepiet2.png'></div>");
    $("div#mainmenu").append("<div id='sinterklaas'><img src='/images/sinterklaas/sint.png'></div>");

    $("div#mainmenu>ul").append('<li class="mainmenu right" rel="Sinterklaas"><a href="https://en.wikipedia.org/wiki/Sinterklaas" target="_blank">Sinterklaas</a></li>');

    positionZwarePiet();
    $(document).scroll(function() {
        positionZwarePiet();
    });
});

function positionZwarePiet()
{
    $("#sinterklaas").css("top", $("div#mainmenu").position().top + $("div#mainmenu").outerHeight() - $("#sinterklaas").height() -1);
    $("#sinterklaas").css("left", $("#mainmenu").position().left - $("#sinterklaas").outerWidth()-10);


    $("#zwarte-piet-1").css("top", $(window).height()-$("#zwarte-piet-1").height()+$(window).scrollTop());
    $("#zwarte-piet-1").css("left", 0);

    $("#zwarte-piet-2").css("top", 200+$(window).scrollTop());
    $("#zwarte-piet-2").css("right", 0);
}