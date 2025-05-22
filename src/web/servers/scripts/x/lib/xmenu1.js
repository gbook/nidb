// xMenu1 r1, Copyright 2001-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xMenu1(triggerId, menuId, mouseMargin, openEvent)
{
  var isOpen = false;
  var trg = xGetElementById(triggerId);
  var mnu = xGetElementById(menuId);
  if (trg && mnu) {
    xAddEventListener(trg, openEvent, onOpen, false);
  }
  function onOpen()
  {
    if (!isOpen) {
      xMoveTo(mnu, xPageX(trg), xPageY(trg) + xHeight(trg));
      mnu.style.visibility = 'visible';
      xAddEventListener(document, 'mousemove', onMousemove, false);
      isOpen = true;
    }
  }
  function onMousemove(ev)
  {
    var e = new xEvent(ev);
    if (!xHasPoint(mnu, e.pageX, e.pageY, -mouseMargin) &&
        !xHasPoint(trg, e.pageX, e.pageY, -mouseMargin))
    {
      mnu.style.visibility = 'hidden';
      xRemoveEventListener(document, 'mousemove', onMousemove, false);
      isOpen = false;
    }
  }
} // end xMenu1
