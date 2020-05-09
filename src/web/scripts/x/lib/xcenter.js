// xCenter r1, Copyright 2009 Arthur Blake (http://arthur.blake.name)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
/**
 * Center a positioned element within the current client window space.
 * 
 * If w,h not specified, then the existing width and height of e are used.
 * 
 * @param e an existing absolutely positioned dom element (or an id to such an element)
 * @param w (optional) width to resize element to
 * @param h (optional) height  to resize element to
 */
function xCenter(e, w, h)
{
  var ww=xClientWidth(),wh=xClientHeight(),x=0,y=0;
  e = xGetElementById(e);
  if (e)
  {
    w = w || xWidth(e);
    h = h || xHeight(e);

    if (ww < w)
    {
      w = ww;
    }
    else
    {
      x = (ww - w) / 2;
    }
    if (wh < h)
    {
      h = wh;
    }
    else
    {
      y = (wh - h) / 2;
    }

    // adjust for any scrolling
    x += xScrollLeft();
    y += xScrollTop();
    
    xResizeTo(e, w, h);
    xMoveTo(e, x, y);
  }
}
