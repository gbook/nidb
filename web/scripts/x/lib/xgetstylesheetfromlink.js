// xGetStyleSheetFromLink r1, Copyright 2006-2007 Ivan Pepelnjak (www.zaplana.net)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
// xGetStyleSheetFromLink - extracts style sheet object from the HTML LINK object (IE vs. DOM CSS level 2)
function xGetStyleSheetFromLink(cl) { return cl.styleSheet ? cl.styleSheet : cl.sheet; }

