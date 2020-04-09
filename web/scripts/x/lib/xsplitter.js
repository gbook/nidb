// xSplitter r5, Copyright 2006-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xSplitter(sSplId, uSplX, uSplY, uSplW, uSplH, bHorizontal, uBarW, uBarPos, uBarLimit1, uBarLimit2, bBarEnabled, uSplBorderW, oSplChild1, oSplChild2)
{
  // Private

  var pane1, pane2, splW, splH, splEle, barPos, barLim1, barLim2, barEle, deFn;

  function barOnDrag(ele, dx, dy)
  {
    var bp;
    iFrameVis(false);
    if (bHorizontal) {
        bp = barPos + dx;
        if (bp < barLim1 || bp > splW - barLim2) { return; }
        xWidth(pane1, xWidth(pane1) + dx);
        xLeft(barEle, xLeft(barEle) + dx);
        xWidth(pane2, xWidth(pane2) - dx);
        xLeft(pane2, xLeft(pane2) + dx);
        barPos = bp;
    }
    else {
        bp = barPos + dy;
        if (bp < barLim1 || bp > splH - barLim2) { return; }
        xHeight(pane1, xHeight(pane1) + dy);
        xTop(barEle, xTop(barEle) + dy);
        xHeight(pane2, xHeight(pane2) - dy);
        xTop(pane2, xTop(pane2) + dy);
        barPos = bp;
    }
    if (oSplChild1) { oSplChild1.paint(xWidth(pane1), xHeight(pane1)); }
    if (oSplChild2) { oSplChild2.paint(xWidth(pane2), xHeight(pane2)); }
  }

  function barOnDragEnd(ele)
  {
    iFrameVis(true);
    if (deFn) deFn(splEle, barPos);
  }

  function iFrameVis(show)
  {
    var i;
    i = xFirstChild(pane1, 'iframe');
    if (i) {
      i.style.display = show ? 'block' : 'none';
    }
    i = xFirstChild(pane2, 'iframe');
    if (i) {
      i.style.display = show ? 'block' : 'none';
    }
  }

  // Public

  this.setDragEnd = function(fn) { deFn = fn; };

  this.paint = function(uNewW, uNewH, uNewBarPos, uNewBarLim1, uNewBarLim2) // uNewBarPos and uNewBarLim are optional
  {
    var w1, h1, w2, h2;
    if (uNewW == 0) { return; }
    iFrameVis(false);
    splW = uNewW;
    splH = uNewH;
    barPos = uNewBarPos || barPos;
    barLim1 = uNewBarLim1 || barLim1;
    barLim2 = uNewBarLim2 || barLim2;
    xMoveTo(splEle, uSplX, uSplY);
    xResizeTo(splEle, uNewW, uNewH);
    if (bHorizontal) {
      w1 = barPos;
      h1 = uNewH - 2 * uSplBorderW;
      w2 = uNewW - w1 - uBarW - 2 * uSplBorderW;
      h2 = h1;
      xMoveTo(pane1, 0, 0);
      xResizeTo(pane1, w1, h1);
      xMoveTo(barEle, w1, 0);
      xResizeTo(barEle, uBarW, h1);
      xMoveTo(pane2, w1 + uBarW, 0);
      xResizeTo(pane2, w2, h2);
    }
    else {
      w1 = uNewW - 2 * uSplBorderW;;
      h1 = barPos;
      w2 = w1;
      h2 = uNewH - h1 - uBarW - 2 * uSplBorderW;
      xMoveTo(pane1, 0, 0);
      xResizeTo(pane1, w1, h1);
      xMoveTo(barEle, 0, h1);
      xResizeTo(barEle, w1, uBarW);
      xMoveTo(pane2, 0, h1 + uBarW);
      xResizeTo(pane2, w2, h2);
    }
    if (oSplChild1) {
      pane1.style.overflow = 'hidden';
      oSplChild1.paint(w1, h1);
    }
    if (oSplChild2) {
      pane2.style.overflow = 'hidden';
      oSplChild2.paint(w2, h2);
    }
    iFrameVis(true);
  };

  // Constructor

  splEle = xGetElementById(sSplId); // we assume the splitter has 3 DIV children and in this order:
  pane1 = xFirstChild(splEle, 'DIV');
  pane2 = xNextSib(pane1, 'DIV');
  barEle = xNextSib(pane2, 'DIV');
  //  --- slightly dirty hack
  pane1.style.zIndex = 2;
  pane2.style.zIndex = 2;
  barEle.style.zIndex = 1;
  // ---
  barPos = uBarPos;
  barLim1 = uBarLimit1;
  barLim2 = uBarLimit2;
  this.paint(uSplW, uSplH);
  if (bBarEnabled) {
    xEnableDrag(barEle, null, barOnDrag, barOnDragEnd);
    barEle.style.cursor = bHorizontal ? 'e-resize' : 'n-resize';
  }
  splEle.style.visibility = 'visible';

} // end xSplitter
