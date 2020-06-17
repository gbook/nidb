// xDialog r2, Adapted from xPopup by Aaron Throckmorton
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xDialog(sPos1, sPos2, sPos3, sStyle, sId, sUrl, bHidden)
{
  if (document.getElementById && document.createElement &&
    document.body && document.body.appendChild) {
    // create popup element
    //var e = document.createElement('DIV');
    var e = document.createElement('IFRAME');
    this.ele = e;
    e.id = sId;
    e.name = sId;
    e.style.position = 'absolute';
    e.style.zIndex = '1000';
    e.className = sStyle;
    //e.innerHTML = sHtml;
    e.src = sUrl;
    document.body.appendChild(e);
    e.style.visibility = 'visible';
    this.open = false;
    this.margin = 10;
    this.pos1 = sPos1;
    this.pos2 = sPos2;
    this.pos3 = sPos3;
    this.slideTime = 400; // slide time in ms
    if (bHidden) xGetElementById(sId).style.visibility = 'hidden';
    else this.show();
  }
} // end xDialog

// methods
xDialog.prototype.show = function() {
  if (!this.open) {
    var e = this.ele;
    var pos = xCardinalPosition(e, this.pos1, this.margin, true);
    xMoveTo(e, pos.x, pos.y);
    e.style.visibility = 'visible';
    pos = xCardinalPosition(e, this.pos2, this.margin, false);
    xSlideTo(e, pos.x, pos.y, this.slideTime);
    this.open = true;
  }
};

xDialog.prototype.hide = function() {
  if (this.open) {
    var e = this.ele;
    var pos = xCardinalPosition(e, this.pos3, this.margin, true);
    xSlideTo(e, pos.x, pos.y, this.slideTime);
    setTimeout("xGetElementById('" + e.id + "').style.visibility = 'hidden'", this.slideTime);
    //setTimeout("xMoveTo('" + e.id + "', 1 , 1)", this.slideTime);
    this.open = false;
  }
};

/*
xDialog.prototype.destroy = function(sobj) {
  this.hide();
  //setTimeout("document.body.removeChild(getElementById('" + sobj + "'))", this.slideTime);
  setTimeout(sobj + " = ''", this.slideTime);
  //document.body.removeChild(this.ele);
  //delete this ;
};*/

xDialog.prototype.setUrl = function(sUrl) {
  this.ele.src = sUrl;
};

xDialog.prototype.resize = function(w, h) {
  xResizeTo(this.ele, w, h);
  if (this.open) {
    var pos = xCardinalPosition(this.ele, this.pos2, this.margin, true);
    xSlideTo(this.ele, pos.x, pos.y, this.slideTime);
  }
};
