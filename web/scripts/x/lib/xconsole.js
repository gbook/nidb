// xConsole r2, Copyright 2009 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
xConsole = {
  log : function (s, w) {
    w = w || window;
    if (w.console && w.console.log) {
      w.console.log(s);
    }
    else if (w.opera && w.opera.postError) {
      w.opera.postError(s);
    }
    else if (null != (e = xGetElementById('xConsoleElement'))) {
        e.innerHTML += s + '<br>';
    }
    else {
      w.status = s;
    }
  }
};
