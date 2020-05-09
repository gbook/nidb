// xMenu1A r1, Copyright 2001-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xMenu1A(triggerId, menuId, mouseMargin, slideTime, openEvent)
{
  var isOpen = false;
  var trg = xGetElementById(triggerId);
  var mnu = xGetElementById(menuId);
  if (trg && mnu) {
    mnu.style.visibility = 'hidden';
    xAddEventListener(trg, openEvent, onOpen, false);
  }
  function onOpen()
  {
    if (!isOpen) {
      xMoveTo(mnu, xPageX(trg), xPageY(trg));
      mnu.style.visibility = 'visible';
      xSlideTo(mnu, xPageX(trg), xPageY(trg) + xHeight(trg), slideTime);
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
      xRemoveEventListener(document, 'mousemove', onMousemove, false);
      xSlideTo(mnu, xPageX(trg), xPageY(trg), slideTime);
      setTimeout("xGetElementById('" + menuId + "').style.visibility='hidden'", slideTime);
      isOpen = false;
    }
  }
} // end xMenu1A
