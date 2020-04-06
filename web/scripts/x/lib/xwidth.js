// xWidth r8, Copyright 2001-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xWidth(e,w)
{
  var css, pl=0, pr=0, bl=0, br=0;
  if(!(e=xGetElementById(e))) return 0;
  if (xNum(w)) {
    if (w<0) w = 0;
    else w=Math.round(w);
  }
  else w=-1;
  css=xDef(e.style);
  if (e == document || e.tagName.toLowerCase() == 'html' || e.tagName.toLowerCase() == 'body') {
    w = xClientWidth();
  }
  else if(css && xDef(e.offsetWidth) && xStr(e.style.width)) {
    if(w>=0) {
      if (document.compatMode=='CSS1Compat') {
        pl=xGetComputedStyle(e,'padding-left',1);
        if (pl !== null) {
          pr=xGetComputedStyle(e,'padding-right',1);
          bl=xGetComputedStyle(e,'border-left-width',1);
          br=xGetComputedStyle(e,'border-right-width',1);
        }
        // Should we try this as a last resort?
        // At this point getComputedStyle and currentStyle do not exist.
        else if(xDef(e.offsetWidth,e.style.width)){
          e.style.width=w+'px';
          pl=e.offsetWidth-w;
        }
      }
      w-=(pl+pr+bl+br);
      if(isNaN(w)||w<0) return;
      else e.style.width=w+'px';
    }
    w=e.offsetWidth;
  }
  else if(css && xDef(e.style.pixelWidth)) {
    if(w>=0) e.style.pixelWidth=w;
    w=e.style.pixelWidth;
  }
  return w;
}
