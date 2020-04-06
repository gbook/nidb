// xDragInFence r2, Copyright 2007-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xDragInFence(id,fS,fD,fE,x1,y1,x2,y2)
{
  var b = null; // boundary element
  if (typeof x1 != 'undefined' && !x2) {
    b = xGetElementById(x1);
  }
  xEnableDrag(id,
    function (el, x, y, ev) { // dragStart
      if (b) { // get rect from current size of ele
        x1 = xPageX(b);
        y1 = xPageY(b);
        x2 = x1 + b.offsetWidth;
        y2 = y1 + b.offsetHeight;
      }
      if (fS) fS(el, x, y, ev);
    },
    function (el, dx, dy, ev) { // drag
      var x = xPageX(el) + dx; // absolute coords of target
      var y = xPageY(el) + dy;
      var mx = ev.pageX; // absolute coords of mouse
      var my = ev.pageY;
      if  (!(x < x1 || x + el.offsetWidth > x2) && !(mx < x1 || mx > x2)) {
        el.style.left = (el.offsetLeft + dx) + 'px';
      }
      if (!(y < y1 || y + el.offsetHeight > y2) && !(my < y1 || my > y2)) {
        el.style.top = (el.offsetTop + dy) + 'px';
      }
      if (fD) fD(el, dx, dy, ev);
    },
    function (el, x, y, ev) { // dragEnd
      if (fE) fE(el, x, y, ev);
    }
  );
}
