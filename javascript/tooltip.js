function Tooltip(id) {
    console.log("New tooltip: "+id);
    this.id = id;
    this.position = {
        top: mousePosY-40,
        left: mousePosX,
        leftOffset: 20
    };
    this.width = 400;
    console.log(this);
}

Tooltip.prototype.getAlign = function() {
    if (($(window).width()-this.position.left) < 380)
        return "right";
    else
        return "left";
};
Tooltip.prototype.getPosition = function() {
    var posLeft = this.position.left + this.position.leftOffset;
    if (this.getAlign() == "right")
        posLeft -= (this.width*1.2) - (this.position.leftOffset*2);

    return {
        top: this.position.top,
        left: posLeft
    };
};

Tooltip.prototype.render = function(content) {
    console.log("render");
    var html = "<div class='tooltip' id='tooltip"+this.id+"' style='width: "+this.width+"'>";
    html += "<div id='tooltip-"+this.id+"-Header' class='header"+this.getAlign()+"'></div>";
    html += "<div id='tooltip-"+this.id+"-Details' class='content"+this.getAlign()+"'>"+content+"</div>";
    html += "<div id='tooltip-"+this.id+"-Footer' class='footer"+this.getAlign()+"'></div>";
    html += "</div>";
    $("body").append(html);
};

Tooltip.prototype.show = function() {
    console.log("show");
    $("#tooltip"+this.id).css("position","absolute");
    $("#tooltip"+this.id).css("left", this.getPosition().left);
    $("#tooltip"+this.id).css("top", this.getPosition().top);
    $("#tooltip"+this.id).fadeIn();
};

function closeTooltip(tooltipID)
{
    $("#tooltip"+tooltipID).remove();
}