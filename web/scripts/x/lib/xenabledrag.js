// xEnableDrag r8, Copyright 2002-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xEnableDrag(id,fS,fD,fE)
{
  var mx = 0, my = 0, el = xGetElementById(id);
  if (el) {
    el.xDragEnabled = true;
    xAddEventListener(el, 'mousedown', dragStart, false);
  }
  // Private Functions
  function dragStart(e)
  {
    if (el.xDragEnabled) {
      var ev = new xEvent(e);
      xPreventDefault(e);
      mx = ev.pageX;
      my = ev.pageY;
      xAddEventListener(document, 'mousemove', drag, false);
      xAddEventListener(document, 'mouseup', dragEnd, false);
      if (fS) {
        fS(el, ev.pageX, ev.pageY, ev);
      }
    }
  }
  function drag(e)
  {
    var ev, dx, dy;
    xPreventDefault(e);
    ev = new xEvent(e);
    dx = ev.pageX - mx;
    dy = ev.pageY - my;
    mx = ev.pageX;
    my = ev.pageY;
    if (fD) {
      fD(el, dx, dy, ev);
    }
    else {
      xMoveTo(el, xLeft(el) + dx, xTop(el) + dy);
    }
  }
  function dragEnd(e)
  {
    var ev = new xEvent(e);
    xPreventDefault(e);
    xRemoveEventListener(document, 'mouseup', dragEnd, false);
    xRemoveEventListener(document, 'mousemove', drag, false);
    if (fE) {
      fE(el, ev.pageX, ev.pageY, ev);
    }
    if (xEnableDrag.drop) {
      xEnableDrag.drop(el, ev);
    }
  }
}

xEnableDrag.drops = []; // static property
