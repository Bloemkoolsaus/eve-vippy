var canvas = null;
var context = null;
var stage = null;
var layer = null;
var mapZoom = 100;

var x1 = 0;
var y1 = 0;
var x2 = 0;
var y2 = 0;

var maxWidth = 0;
var maxHeight = 0;

var totalWidth = 0;
var totalHeight = 0;

var whDefaultWidth = 130;
var whDefaultHeight = 50;

var massDeleteMode = false;
var blockMapRefresh = false;



function mapRendered()
{
	if (stage == null)
		return false;
	else
		return true;
}

function mapZoomIn()
{
	mapZoom += 10;
	$("#mapZoom").attr("value",mapZoom);
}
function mapZoomOut()
{
	mapZoom -= 10;
	if (mapZoom < 50)
		mapZoom = 50;
	$("#mapZoom").attr("value",mapZoom);
}

function generateMap(data)
{
	if (blockMapRefresh)
		return true;

	if (!mapRendered())
	{
		maxWidth = $("#maincontainer").width()-30;
		maxHeight = $("#signatureMap").height()-20;

	    stage = new Kinetic.Stage({
	        container: "signatureMap",
	        width: maxWidth,
	        height: maxHeight,
	        id: "sigmap"
		});
	}
	else
	{
		layer.removeChildren();
		stage.remove(layer);
		stage.clear();
	}

	var curHeight = maxHeight;

	layer = new Kinetic.Layer();
	generateConnections(data.connections);
	generateSystems(data.wormholes);	

	resizeMap();
	$(window).resize(function() {
		resizeMap();
	});

    // add the layer to the stage
    stage.add(layer);
}

function resizeMap()
{
	var stageWidth = maxWidth;
	var stageHeight = maxHeight;
	
	if (stageWidth < (totalWidth + 100))
		stageWidth = totalWidth + 100;
	
	if (stageHeight < totalHeight)
		stageHeight = totalHeight + 20;

	stage.setSize(stageWidth, stageHeight);
	$("#signatureMap").width(stageWidth);
	$("#signatureMapContainer").width(stageWidth);
	
	if ($(window).width() > stageWidth)
	{
		var left = Math.round(($(window).width() - stageWidth) / 2);
		$("#signatureMapContainer").css("left", left);
	}
	else
		$("#signatureMapContainer").css("left", 0);

	var top = $("#mapHeader").position().top + $("#mapHeader").height() + 14;
	$("#signatureMapContainer").css("top", top);

	$("#signatureMapContainer").height(maxHeight+20);
	$("#filler").height($("#signatureMapContainer").height()-15);
}

function generateConnections(data)
{
	var i = 0;
	for (i=0; i<data.length; i++)
	{
		var connectionColor = "#338844";

		if (data[i].attributes.kspacejumps != null)
			connectionColor = (data[i].attributes.normalgates != null) ? "#0088FF" : "#CCCCCC";
		else 
		{
			if (data[i].attributes.mass != null)
				connectionColor = (data[i].attributes.mass == 1) ? "#FFAA22" : "#BB2222";
		}

		var connGroup = new Kinetic.Group();
		var outlineColor = connectionColor;
//			outlineColor = "#AA44CC";				// Route plotter



		// Bereken de positie van de connection
		var x1 = (((data[i].from.position.x-0)+60)/100)*mapZoom;
		var x2 = (((data[i].to.position.x-0)+60)/100)*mapZoom;
		var y1 = (((data[i].from.position.y-0)+20)/100)*mapZoom;
		var y2 = (((data[i].to.position.y-0)+20)/100)*mapZoom;

		// Bereken het midden van de lijn
		var jPosX = Math.round((x1+x2)/2);
		var jPosY = Math.round((y1+y2)/2);


		var connectionBase = new Kinetic.Line({
			points: [x1,y1,x2,y2],
			stroke: "#222222",
			strokeWidth: 6,
			lineCap: "butt",
			lineJoin: "butt"
		});
		var connection = new Kinetic.Line({
			points: [x1,y1,x2,y2],
			stroke: connectionColor,
			strokeWidth: 8,
			lineCap: "butt",
			lineJoin: "butt",
			detectionType: "pixel"
		}); 
		var connectionBorder = new Kinetic.Line({
			points: [x1,y1,x2,y2],
			stroke: connectionColor,
			strokeWidth: 10,
			lineCap: "butt",
			lineJoin: "butt"
		});

		if (data[i].attributes.eol != null)
			connection.setDashArray([5,5,0,0]);

		var connectionJumps = null;
		var connectionJumpsTxt = null;
		var connectionJumpsImg = null;
		
		if (data[i].attributes.kspacejumps != null || 
			data[i].attributes.frigate != null || 
			data[i].attributes.capital != null)
		{
			// Positie voor plaatje
			var jTxtPosX = jPosX-6;
			var jTxtPosY = jPosY-4;
			
			if (data[i].attributes.kspacejumps == null)
				jTxtPosX += 3;

			if (data[i].attributes.frigate > 0)
			{
				connectionJumps = new Kinetic.Circle({
					x: jPosX,
					y: jPosY,
					radius: 14,
					fill: '#000000',
			        stroke: outlineColor,
			        strokeWidth: 2
				});
				connectionJumpsImg = new Kinetic.Image({
					x: jTxtPosX-12,
					y: jTxtPosY-3,
					image: rifterIcon,
					width: 32,
					height: 16
				});
			}
			else if (data[i].attributes.capital > 0)
			{
				connectionJumps = new Kinetic.Circle({
					x: jPosX,
					y: jPosY,
					radius: 7,
					fill: '#000000',
			        stroke: outlineColor,
			        strokeWidth: 1
				});
				connectionJumpsImg = new Kinetic.Image({
					x: jTxtPosX-3,
					y: jTxtPosY-1,
					image: cynoIcon,
					width: 12,
					height: 12
				});
			}
			else
			{
				connectionJumps = new Kinetic.Circle({
					x: jPosX,
					y: jPosY,
					radius: 10,
					fill: '#DDDDDD',
			        stroke: '#CCCCCC',
			        strokeWidth: 2
				});
				connectionJumpsTxt = new Kinetic.Text({
					x: jTxtPosX,
					y: jTxtPosY,
		            text: data[i].attributes.kspacejumps,
		            fontSize: 8,
		            fontFamily: "Calibri",
		            textFill: "#555555"
		        });
			}
		}

		var x1 = (data[i].from.position.x-0)+60-5;
		var y1 = (data[i].from.position.y-0)+20-5;
		var x2 = (data[i].from.position.x-0)+60+5;
		var y2 = (data[i].from.position.y-0)+20+5;

		var x3 = (data[i].to.position.x-0)+60-5;
		var y3 = (data[i].to.position.y-0)+20-5;
		var x4 = (data[i].to.position.x-0)+60+5;
		var y4 = (data[i].to.position.y-0)+20+5;

		x1 -= 10;
		x2 += 10;
		y2 -= 10;

		x3 -= 10;
		x4 += 10;
		y4 -= 10;

		var diffy = y1-y3;
		if (diffy < 0)
			diffy = diffy*-1;		
		if (diffy < 100) {
			y1 += 10;
			y3 += 10;
		}

		x1 = (x1/100)*mapZoom;
		x2 = (x2/100)*mapZoom;
		x3 = (x3/100)*mapZoom;
		x4 = (x4/100)*mapZoom;
		y1 = (y1/100)*mapZoom;
		y2 = (y2/100)*mapZoom;
		y3 = (y3/100)*mapZoom;
		y4 = (y4/100)*mapZoom;

		var connectionBox = new Kinetic.Polygon({
			points: [x1,y1,x2,y2,x4,y4,x3,y3],
			stroke: "transparent",
			strokeWidth: 1,
			listening: true,
			name: data[i].from.system+","+data[i].to.system,
		});

		connectionBox.on("mouseover", function() {
        	document.body.style.cursor = "pointer";
        	openConnectionDetails(this.getName(),mousePosX,mousePosY);
		});
		connectionBox.on("mouseout", function() {
        	document.body.style.cursor = "default";
        	closeConnectionDetails(this.getName());
        });
		connectionBox.on("click", function() {
			var fromto = this.getName().split(",");
			editConnection(fromto[0],fromto[1]);
		});

		connGroup.add(connectionBorder);
		connGroup.add(connectionBase);
		connGroup.add(connection);

		if (connectionJumps != null)
		{
			connGroup.add(connectionJumps);
			if (connectionJumpsTxt !== null)
				connGroup.add(connectionJumpsTxt);			
			if (connectionJumpsImg !== null)
				connGroup.add(connectionJumpsImg);
		}

        layer.add(connGroup);
        layer.add(connectionBox);
	}
}

function generateSystems(data)
{	
	var i = 0;
	for (i=0; i<data.length; i++)
	{
    	var j = 0;
    	var lineHeight = 11;
        var whClass = "??";
        var whColor = "#000000";
    	var whTextColor = "#222222";
    	var whTextBold = false;
		var whBGcolor = "#FFFFFF";
    	var whBorderColor = "#555555";
    	var whWidth = whDefaultWidth;
    	var whHeight = whDefaultHeight;
    	var homeSystem = false;
        var startContentWidth = 5;
    	
    	if (data[i].solarsystem != null) 
    	{
	    	if (data[i].solarsystem.class.name == "WH") 
	    	{
	    		whClass = data[i].whsystem.class;
	    		whColor = data[i].solarsystem.class.color;

				if (data[i].statics != null)
			    	whHeight += (lineHeight * data[i].statics.length-1);	// reserveer ruimte voor static(s) 

	    		if (data[i].whsystem.effect != null)
	        		whHeight += lineHeight;									// reserveer ruimte voor effect
	    	} 
	    	else 
	    	{
	    		whClass = data[i].solarsystem.class.name;
	    		whColor = data[i].solarsystem.class.color;
        		whHeight += lineHeight;										// reserveer ruimte voor region-name
	    	}	    	
    	}
    	
    	if (data[i].whsystem != null && data[i].whsystem.homesystem != null)
    		homeSystem = data[i].whsystem.homesystem;

		if (data[i].characters != null) {
			var charLength = data[i].characters.length;
			if (charLength > 5)
				charLength = 5;
			whHeight += (lineHeight * charLength);
		}
    	whHeight += 5;
   	
    	
    	if (data[i].known != null) {
			if (data[i].known.type > 2) {
				whTextBold = true;
				whTextColor = "#0066FF";
			}
        }

        if (data[i].status-0 == 2) {
        	whBorderColor = "#00CC00";
        } else if (data[i].status-0 == 3) {
        	whBorderColor = "#FFAA00";
        } else if (data[i].status-0 == 4) {
        	whBorderColor = "#CC0000";
        } else if (homeSystem) {
        	whBorderColor = "#0066FF";
			whTextColor = "#0066FF";
			whTextBold = true;
        }

		var x1 = data[i].position.x-0;
		var y1 = data[i].position.y-0;
		var fx1 = data[i].position.x-0;
		var fy1 = data[i].position.y-0;

		whWidth = (whWidth/100)*mapZoom;
		whHeight = (whHeight/100)*mapZoom;

		x1 = (x1/100)*mapZoom;
		y1 = (y1/100)*mapZoom;
		fx1 = (fx1/100)*mapZoom;
		fy1 = (fy1/100)*mapZoom;

		if (totalWidth < (x1 + whWidth)) {
			totalWidth = x1 + whWidth;
		}
		if (totalHeight < (y1 + whHeight)) {
			totalHeight = y1 + whHeight;
		}

		var wormholeFade = new Kinetic.Group({
			x: x1,
			y: y1,
			draggable: true
		});
		var wormholeBoxFade = new Kinetic.Rect({
			width: whWidth,
			height: whHeight,
			fill: "#666666",
			stroke: "#444444",
			strokeWidth: 2,
			draggable: true
		});
        var wormholeClassFade = new Kinetic.Text({
        	x: 4,
            y: 4,
            text: whClass,
            fontSize: 9,
            fontFamily: "Calibri",
            textFill: "#444444"
        });
        var wormholeSystemFade = new Kinetic.Text({
            x: 20,
            y: 4,
            text: data[i].name,
            fontSize: 9,
            fontFamily: "Calibri",
            textFill: "#444444"
        });

		if ((data[i].position.y-0)+whHeight+10 > maxHeight)
			maxHeight = (data[i].position.y-0)+whHeight+10;


		var wormhole = new Kinetic.Group({
			x: x1,
          	y: y1,
			draggable: true,
			name: data[i].id,
		});

		var wormholeBox = new Kinetic.Rect({
			width: whWidth,
			height: whHeight,
			fill: whBGcolor,
			stroke: whBorderColor,
			strokeWidth: 3,
			draggable: true
		});

		var wormholeSystemName = data[i].name;
		var wormholeTitleBar = null;
		var wormholeUnscannedBar = null;

		if (data[i].solarsystem == null)
		{
			// Unknown system
			wormholeUnscannedBar = new Kinetic.Rect({
				x: 2,
				y: 2,
				width: 17,
				height: whHeight-4,
				fill: "#AAAAAA"
			});

			whColor = "#000000";
			whClass = "??";
		}
		else
		{
			if (data[i].solarsystem != null && data[i].solarsystem.class.name == "WH")
			{
				// Wspace-system				
		        if (data[i].fullyscanned == null)
		        {
		        	wormholeUnscannedBar = new Kinetic.Rect({
						x: 2,
						y: 15,
						width: 17,
						height: whHeight-17,
						fill: "#AAAAAA"
					});	
		        	startContentWidth = 20;	        	
		        }
		        else if (data[i].fullyscanned >= 1)
		        {
		        	wormholeUnscannedBar = new Kinetic.Rect({
						x: 2,
						y: (Math.round(whHeight/3)*2),
						width: 17,
						height: whHeight-(Math.round(whHeight/3)*2)-2,
						fill: "#AAAAAA"
					});	
		        	startContentWidth = 20;
		        }
			}
			else
			{
				// Kspace system
				wormholeTitleBar = new Kinetic.Rect({
					x: 2,
					y: 2,
					width: 17,
					height: whHeight-4,
					fill: whColor
				});

		        if (data[i].fullyscanned == null)
		        {
		        	wormholeUnscannedBar = new Kinetic.Rect({
						x: 2,
						y: 25,
						width: 17,
						height: whHeight-27,
						fill: "#AAAAAA"
					});
		        } 
		        else if (data[i].fullyscanned >= 1)
		        {
		        	wormholeUnscannedBar = new Kinetic.Rect({
						x: 2,
						y: (Math.round(whHeight/3)*2),
						width: 17,
						height: whHeight-(Math.round(whHeight/3)*2)-2,
						fill: "#AAAAAA"
					});
		        }
		        
				whColor = "#FFFFFF";
	        	startContentWidth = 20;
			}
		}

        var wormholeClass = new Kinetic.Text({
        	x: 4,
            y: 4,
            text: whClass,
            fontSize: 9,
            fontFamily: "Calibri",
            fontStyle: "bold",
            textFill: whColor
        });

		var wormholeSystem = new Kinetic.Text({
            x: 20,
            y: 4,
            text: wormholeSystemName,
            fontSize: 9,
            fontFamily: "Calibri",
            fontStyle: (whTextBold) ? "bold" : "normal",
            textFill: whTextColor
        });

        wormhole.on("dragstart", function() {
        	if (!mapIsMassDeleteMode()) {
    			closeWormholeDetails(this.getName());
        		loadingSigMap = true;
        	}
        });
        wormhole.on("dragend", function() {
        	if (!mapIsMassDeleteMode()) {
	        	loadingSigMap = false;
	        	var zoomLvl = 100+(100-mapZoom);
	        	var newX = ((this.getAbsolutePosition().x/100)*zoomLvl);
	        	var newY = ((this.getAbsolutePosition().y/100)*zoomLvl);
	        	loadSignatureMap("&move="+this.getName()+"&x="+newX+"&y="+newY);
        	}
        });
        
        if (data[i].solarsystem != null)
        {
	        wormhole.on("mouseover", function() {
	        	document.body.style.cursor = "pointer";
	        	if (!massDeleteMode) {
	        		loadingSigMap = true;
	        		openWormholeDetails(this.getName(),this.getAbsolutePosition().x,this.getAbsolutePosition().y);
	        	}
	        });
	        wormhole.on("mouseout", function() {
	        	document.body.style.cursor = "default";
				closeWormholeDetails(this.getName());
	        });
        }
        wormhole.on("click", function(evt) {
        	closeContextMenu();
			closeWormholeDetails(this.getName());
			var rightClick = evt.which ? evt.which == 3 : evt.button == 2;
			if (rightClick) {
				openContextMenu(this.getName(),mousePosX,mousePosY);
				return false;
			} else {
				if (mapIsMassDeleteMode())
					deleteWormhole(this.getName());
				else
					switchSystem(this.getName());
			}
        });

        wormhole.add(wormholeBox);
        if (wormholeTitleBar !== null)
        	wormhole.add(wormholeTitleBar);
        if (wormholeUnscannedBar !== null)
        	wormhole.add(wormholeUnscannedBar);
        wormhole.add(wormholeClass);
        wormhole.add(wormholeSystem);
        
                

        // Extra text toevoegen..?
		if (mapZoom > 80)
		{
			var extraTxtHeight = 14;
			var activePilotHeight = 20;


			// Wormhole titles
			if (data[i].whsystem.titles != null)
			{
				for (var t=0; t<data[i].whsystem.titles.length; t++)
				{
					var titleName = data[i].whsystem.titles[t].name;
					var titleColor = "#444444";
					
					if (data[i].whsystem.titles[t].color != null)
						titleColor = data[i].whsystem.titles[t].color;
	
					var systemTitle = new Kinetic.Text({
			            x: 20,
			            y: extraTxtHeight+1,
			            text: titleName,
			            fontSize: 8,
			            fontFamily: "Calibri",
			            fontStyle: "bold",
			            textFill: titleColor
			        });
					wormhole.add(systemTitle);
					extraTxtHeight += lineHeight;
					activePilotHeight += lineHeight;
				}
			}

			
			var extraText = false;
			
			// Tradhubs			
			if (data[i].tradehub != null && data[i].tradehub.jumps != null)
				extraText = data[i].tradehub.jumps + " jumps to " + data[i].tradehub.name;
			
			// WH-effects
			if (data[i].whsystem.effect != null)
				extraText = data[i].whsystem.effect;

			if (extraText != false)
			{
				var whExtraText = new Kinetic.Text({
					x: 20,
					y: extraTxtHeight,
					text: extraText,
					fontSize: 8,
					fontFamily: "Calibri",
					fontStyle: "normal",
					textFill: "#888888"
				});
				wormhole.add(whExtraText);
				extraTxtHeight += lineHeight;
			}			

			// Statics van dit gat.
			var j = 0;
			var bottomHeight = whHeight-lineHeight-4;

			if (data[i].whsystem.statics != null)
			{
				for (j=1; j<=data[i].whsystem.statics.length; j++) 
				{
					var wormholeStatic = new Kinetic.Text({
						x: 65,
						y: bottomHeight-((lineHeight-1)*(j-1)),
						text: data[i].whsystem.statics[j-1],
						fontSize: 8,
						fontFamily: "Calibri",
						fontStyle: "normal",
						textFill: "#888888"
					});
					wormhole.add(wormholeStatic);
				}
			}
				

			// Piloten in dat systeem.
			if (data[i].characters != null)
			{
				var nrOfCharLines = data[i].characters.length;
				if (nrOfCharLines > 5)
					nrOfCharLines = 5;

				var j = 0;
				for (j=1; j<=nrOfCharLines; j++) 
				{
					var characterName = data[i].characters[j-1].name;
					if (j == 5 && data[i].characters.length > 5)
						characterName = "  + " + (data[i].characters.length-j+1) + " others";

					var charLocations = new Kinetic.Text({
						x: startContentWidth,
						y: activePilotHeight+(j*10),
						text: characterName,
						fontSize: 8,
						fontFamily: "Calibri",
						textFill: "#666666"
					});
					wormhole.add(charLocations);
				}
				bottomHeight -= 3;
			}

			var xtraIconX = startContentWidth;
			
			// Heb ik een toon in dit systeem?
			if (data[i].insystem != null && data[i].insystem > 0)
			{
				var img = new Kinetic.Image({
					x: 5,
					y: 16,
					image: starIcon,
					width: 12,
					height: 12
				});
				wormhole.add(img);
			}

			if (data[i].attributes != null)
			{
				// Faction?
				if (data[i].attributes.factionid != null)
				{
					if (factionIcons[data[i].attributes.factionid] != null)
					{
						var img = new Kinetic.Image({
							x: whWidth-25,
							y: bottomHeight-lineHeight,
							image: factionIcons[data[i].attributes.factionid],
							width: 24,
							height: 24
						});
						wormhole.add(img);
					}
				}

				// Station system?			
				if (data[i].attributes.stations != null)
				{
					var img = new Kinetic.Image({
						x: xtraIconX,
						y: bottomHeight,
						image: stationIcon,
						width: 12,
						height: 12
					});
					wormhole.add(img);
					xtraIconX += 12;
				}
				
				// Caps in range?
				if (data[i].attributes.cyno != null)
				{
					var img = new Kinetic.Image({
						x: xtraIconX,
						y: bottomHeight,
						image: cynoIcon,
						width: 12,
						height: 12
					});
					wormhole.add(img);
					xtraIconX += 12;			
				}
				
				// HS-Island?
				if (data[i].attributes.hsisland != null)
				{
					var img = new Kinetic.Image({
						x: xtraIconX,
						y: bottomHeight,
						image: hsIslandIcon,
						width: 13,
						height: 12
					});
					wormhole.add(img);
					xtraIconX += 14;
				}
				
				// Direct-HS
				if (data[i].attributes.direcths != null)
				{
					var img = new Kinetic.Image({
						x: xtraIconX,
						y: bottomHeight,
						image: directHsIcon,
						width: 13,
						height: 12
					});
					wormhole.add(img);
					xtraIconX += 14;
				}
				
				
				// Contested / faction warfare
				if (data[i].attributes.contested != null)
				{
					var img = new Kinetic.Image({
						x: xtraIconX,
						y: bottomHeight,
						image: fwContestedIcon,
						width: 13,
						height: 12
					});
					wormhole.add(img);
					xtraIconX += 14;
				}
				else if (data[i].attributes.fwsystem)
				{
					var img = new Kinetic.Image({
						x: xtraIconX,
						y: bottomHeight,
						image: fwIcon,
						width: 13,
						height: 12
					});
					wormhole.add(img);
					xtraIconX += 14;
				}
			}


			if (whClass != "HS" && data[i].kills != null)
			{				
				// Recente pvp kills
				if (data[i].kills.pvp > 0)
				{
					var img = new Kinetic.Image({
						x: xtraIconX,
						y: bottomHeight,
						image: pvpIcon,
						width: 12,
						height: 12
					});
					wormhole.add(img);
					xtraIconX += 12;
				}
				// Recente pve kills
				if (data[i].kills.pve > 0)
				{
					var img = new Kinetic.Image({
						x: xtraIconX,
						y: bottomHeight,
						image: pveIcon,
						width: 12,
						height: 12
					});
					wormhole.add(img);
					xtraIconX += 12;
				}
			}
		}

        wormholeFade.add(wormholeBoxFade);
        wormholeFade.add(wormholeClassFade);
        wormholeFade.add(wormholeSystemFade);
        
        layer.add(wormholeFade);
        layer.add(wormhole);
	}
}

function openContextMenu(whName, mouseX, mouseY)
{
	$("#maincontent").append("<div id='disabledPageTransparent'></div>");
	$("#disabledPageTransparent").css("height",$(document).height());
	$("#disabledPageTransparent").show();
	$("#disabledPageTransparent").click(function() { closeContextMenu(); });

	var contextContainer = "<div id='wormholeContext' style='top: "+mousePosY+"px; left: "+mousePosX+"px;'>";
	contextContainer += "<div id='wormholeContextHeader'>&nbsp;</div>";
	contextContainer += "<div id='wormholeContextMenu'><div style='padding: 10px;'><img src='images/loading.gif'> &nbsp; Loading context menu</div></div>";
	contextContainer += "<div id='wormholeContextFooter'>&nbsp;</div>";
	contextContainer += "</div>";
	$("#maincontent").append(contextContainer);

	$.ajax({
		url: "index.php?module=scanning&section=map&action=contextmenu&ajax=1&id=" + whName,
		success: function(data) {
			$("#wormholeContextMenu").html(data);
		}
	});
	return false;
}
function closeContextMenu()
{
	$("#disabledPageTransparent").remove();
	$("#wormholeContext").fadeOut("fast", $("#wormholeContext").remove());
}

function mapIsMassDeleteMode()
{
	return massDeleteMode;
}
function mapSetMassDelete()
{
	massDeleteMode = true;
}
function mapUnSetMassDelete()
{
	massDeleteMode = false;
}

var popupActive = false;
function openWormholeDetails(system,x,y)
{
	if (isContextOpen())
		return false;

	popupActive = true;
	loadingSigMap = true;
	blockMapRefresh = true;

	var className = "";
	var posLeft = x + whDefaultWidth + $("#signatureMap").position().left;
	var posTop = y + $("#signatureMap").position().top - 20;

	if (($(window).width()-posLeft) < 380) 
	{
		posLeft = posLeft - whDefaultWidth - 400;
		className += "right";
	}
	else
		className += "left";

	var html = "<div class='sigInfo' id='whInfo"+system+"'>";
	html += "<div id='whInfo"+system+"Header' class='header"+className+"'></div>";
	html += "<div id='whInfo"+system+"Details' class='content"+className+"'>";
	html += "<img src='images/loading.gif'> &nbsp; Loading system data";
	html += "</div>";
	html += "<div id='whInfo"+system+"Footer' class='footer"+className+"'></div>";
	html += "</div>";

	$("#signatureMapContainer").append(html);
	$("#whInfo"+system).css("position","absolute");
	$("#whInfo"+system).css("left", posLeft);
	$("#whInfo"+system).css("top", posTop);
	$("#whInfo"+system).fadeIn();
	fetchWormholeDetails(system);
}
function closeWormholeDetails(system)
{
	$("#whInfo"+system).remove();
	popupActive = true;		
	loadingSigMap = false;
	blockMapRefresh = false;
}
function fetchWormholeDetails(systemID)
{
	if (isContextOpen())
		return false;

	$.ajax({
		url: "index.php?module=scanning&section=getwhdetails&ajax=1",
		data: {
			system: systemID
		},
		success: function(data) {
			if (isContextOpen())
				return false;

			$("#whInfo"+systemID+"Details").html(data);
			fetchWormholeDetailsActivity(systemID);
		}
	});
}
function fetchWormholeDetailsActivity(systemID)
{
	if (isContextOpen())
		return false;

	$.ajax({
		url: "index.php?module=scanning&section=getwhdetails&action=getactivity&ajax=1",
		data: {
			system: systemID
		},
		success: function(data) {
			if (isContextOpen())
				return false;

			data = $.parseJSON(data);
			$("#whActivityDate").html(data.date);
			$("#whInfoActivity").html("<img src='"+data.url+"'/>");
		}
	});
}

function openConnectionDetails(who, x, y)
{
	popupActive = true;
	loadingSigMap = true;
	blockMapRefresh = true;

	var className = "";
	var posLeft = mousePosX+15;
	var posTop = mousePosY-50;

	if (($(window).width()-posLeft) < 380)
		className += "right";
	else
		className += "left";

	var popupID = "connInfo"+who.replace(",","-");
	var html = "<div class='sigInfo' id='"+popupID+"'>";
	html += "<div class='header"+className+"'></div>";
	html += "<div class='content"+className+"' id='conndetailsinfo' style='padding-left: 30px;'>";
	html += "<img src='images/loading.gif'> &nbsp; Loading wormhole data";
	html += "</div>";
	html += "<div class='footer"+className+"'></div>";
	html += "</div>";

	$("#maincontainer").append(html);
	
	if (className == "right")
		posLeft = posLeft - 20 - $("#"+popupID).width();

	$("#"+popupID).css("position","absolute");
	$("#"+popupID).css("left", posLeft);
	$("#"+popupID).css("top", posTop);
	$("#"+popupID).fadeIn();

	$("#connInfo"+who).fadeIn();
	fetchConnectionInfo(who);
}
function closeConnectionDetails(who)
{
	var popupID = "connInfo"+who.replace(",","-");
	$("#"+popupID).remove();
	popupActive = true;		
	loadingSigMap = false;
	blockMapRefresh = false;
}
function fetchConnectionInfo(who)
{
	$.ajax({
		url: "index.php?module=scanning&section=getconndetails&ajax=1&connection="+who,
		success: function(data) {
			$("#conndetailsinfo").html(data);
			$("#jumplogsummary").html("<img src='images/loading.gif'> &nbsp; Loading jump log");			
			// Jumplog halen
			$.ajax({
				url: "index.php?module=scanning&section=getconndetails&action=jumplog&ajax=1&connection="+who,
				success: function(data) {
					$("#jumplogsummary").html(data);
				}
			});
		}
	});
}
function isContextOpen()
{
	if ($("#wormholeContext").length > 0)
		return true;
	else
		return false;
}
