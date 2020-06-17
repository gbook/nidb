// xFlWrite r1, Copyright 2011 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xFlWrite(id, w, h, swf, par)
{
  var p, ie = false, s = '<object id="' + id + '" width="' + w + '" height="' + h + '" ';
  /*@cc_on
  @if (@_jscript)
    ie = true;
  @end @*/
  if (ie) s += 'classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"><param name="movie" value="' + swf + '">';
  else s += 'data="' + swf + '" type="application\/x-shockwave-flash">';
  s += '<param name="quality" value="high"><param name="wmode" value="opaque"><param name="scale" value="noborder">';
  if (par) {
    for (p in par) {
      if (!par.hasOwnProperty || par.hasOwnProperty(p)) {
        s += '<param name="' + p + '" value="' + par[p] + '">';
      }
    }
  }
  s += '<\/object>\n';
  document.write(s);
}
