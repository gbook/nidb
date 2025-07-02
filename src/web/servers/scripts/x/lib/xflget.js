// xFlGet r2, Copyright 2011 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xFlGet(id, win)
{
  var f, w = win ? win : window;
  if (w.document.embeds && w.document.embeds[id]) f = w.document.embeds[id];
  else if (w.document[id]) f = w.document[id];
  else f = w.document.getElementById(id);
  return f;
}
