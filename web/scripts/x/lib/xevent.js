// xEvent r11, Copyright 2001-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xEvent(evt) // object prototype
{
  var e = evt || window.event;
  if (!e) return;
  this.type = e.type;
  this.target = e.target || e.srcElement;
  this.relatedTarget = e.relatedTarget;
  /*@cc_on if (e.type == 'mouseover') this.relatedTarget = e.fromElement;
  else if (e.type == 'mouseout') this.relatedTarget = e.toElement; @*/
  if (xDef(e.pageX)) { this.pageX = e.pageX; this.pageY = e.pageY; }
  else if (xDef(e.clientX)) { this.pageX = e.clientX + xScrollLeft(); this.pageY = e.clientY + xScrollTop(); }
  if (xDef(e.offsetX)) { this.offsetX = e.offsetX; this.offsetY = e.offsetY; }
  else if (xDef(e.layerX)) { this.offsetX = e.layerX; this.offsetY = e.layerY; }
  else { this.offsetX = this.pageX - xPageX(this.target); this.offsetY = this.pageY - xPageY(this.target); }
  this.keyCode = e.keyCode || e.which || 0;
  this.shiftKey = e.shiftKey; this.ctrlKey = e.ctrlKey; this.altKey = e.altKey;
  if (typeof e.type == 'string') {
    if (e.type.indexOf('click') != -1) {this.button = 0;}
    else if (e.type.indexOf('mouse') != -1) {
      this.button = e.button;
      /*@cc_on if (e.button & 1) this.button = 0;
      else if (e.button & 4) this.button = 1;
      else if (e.button & 2) this.button = 2; @*/
    }
  }
}
