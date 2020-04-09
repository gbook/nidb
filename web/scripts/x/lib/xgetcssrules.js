// xGetCSSRules r1, Copyright 2006-2007 Ivan Pepelnjak (www.zaplana.net)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
// xGetCSSRules - extracts CSS rules from the style sheet object (IE vs. DOM CSS level 2)
function xGetCSSRules(ss) { return ss.rules ? ss.rules : ss.cssRules; }
