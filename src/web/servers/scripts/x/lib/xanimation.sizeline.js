// xAnimation.sizeLine r1, Copyright 2009-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
/**
 @param e ID string or object reference of the element to be animated.
 @param s A "sequence" array.
 @param a Acceleration type.
 @param b Number of bounces.
 @param oe onEnd handler. Return true to repeat sequence.
*/
xAnimation.prototype.sizeLine = function(e,s,a,b,oe)
{
  var i = this, si = 0;
  i.axes(4); // axes: x, y, w and h
  prep();
  i.run();
  function h(i) { // onRun and onTarget
    // set position
    i.e.style.left = Math.round(i.a[0].v) + 'px';
    i.e.style.top = Math.round(i.a[1].v) + 'px';
    // set size
    xWidth(i.e, Math.round(i.a[2].v));
    xHeight(i.e, Math.round(i.a[3].v));
  }
  // onEnd handler
  function end(i) {
    if (++si < s.length) {
      prep();
      return true; // start next animation in sequence
    }
    else {
      if (oe && oe(i)) {
        si = 0;
        prep();
        return true; // restart sequence
      }
      return false; // stop
    }
  }
  // prepare for the next animation in the sequence
  function prep() {
    // initial position
    i.a[0].i = xLeft(e);
    i.a[1].i = xTop(e);
    // target position
    i.a[0].t = Math.round(s[si][0]);
    i.a[1].t = Math.round(s[si][1]);
    // initial size
    i.a[2].i = xWidth(e);
    i.a[3].i = xHeight(e);
    // target size
    i.a[2].t = Math.round(s[si][2]);
    i.a[3].t = Math.round(s[si][3]);
    i.init(e,s[si][4],h,h,end,a,b);
  }
};
