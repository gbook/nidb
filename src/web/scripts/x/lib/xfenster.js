// xFenster r21, Copyright 2004-2011 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xFenster(xfArgs)
{
  var _i = this; // private reference to the instance object

  // Public Methods

  _i.paint = function(dw, dh) { xFenster._paint(_i, dw, dh); };
  _i.focus = function() { xFenster._focus(_i); };
  _i.href = function(s) { return xFenster._href(_i, s); };
  _i.hide = function(e) { xFenster._hide(_i, e); };
  _i.show = function() { xFenster._show(_i); };
  _i.status = function(s) { return xFenster._status(_i, s); };
  _i.title = function(s) { return xFenster._title(_i, s); };
  _i.destroy = function() { xFenster._destroy(_i); };
  _i.minimize = function() { return xFenster._minimize(_i); };
  _i.maximize = function() { return xFenster._maximize(_i); };
  _i.restore = function() { return xFenster._restore(_i); };
  _i.resize = function(w, h) { _i.a.w = w; _i.a.h = h; xFenster._paint(_i, 0, 0); };
  _i.mnuHide = function() { xFenster._mnuHide(_i); }; //r21/MENU
  // Event listeners
  _i.drgSL = function() { xFenster._drgSH(_i); }; // Drag start listener
  _i.drgEL = function() { xFenster._drgEH(_i); }; // Drag end listener
  _i.movDL = function(e, mdx, mdy) { xFenster._movDH(_i, e, mdx, mdy); }; // Move drag listener
  _i.rszDL = function(e, mdx, mdy) { xFenster._rszDH(_i, e, mdx, mdy); }; // Resize drag listener
  _i.maxCL = function() { xFenster._maxCH(_i); }; // Maximize click listener
  _i.minCL = function() { xFenster._minCH(_i); }; // Minimize click listener
  _i.mnuCL = function() { xFenster._mnuCH(_i); }; // Menu click listener //r21/MENU
  _i.mnuOL = function(e) { xFenster._mnuOH(_i, this, e); }; // Menu mouseout listener; "this" points to con or mnu //r21/MENU

  // Public Properties

  _i.a = {};
  xFenster._iA(_i, xfArgs);
  // elements
  _i.con = null; // outer container
  _i.cli = xGetElementById(_i.a.clientId); // client element
  _i.msk = null; // iframe mask
  _i.mnu = null; // menu element //r21/MENU
  _i.tB = null; // title bar
  _i.sB = null; // status bar
  _i.rI = null; // resize icon
  _i.nI = null; // minimize icon
  _i.mI = null; // maximize icon
  _i.cI = null; // close icon
  _i.uI = null; // menu icon //r21/MENU
  // bool
  _i.min = false; // true if minimized
  _i.max = false; // true if maximized
  _i.hid = false; // true if hidden
  _i.ifr = (xStr(_i.a.url) && _i.a.url.length); // true if client is iframe
  _i.drg = false; // true if currently being moved or resized
  // restore values
  _i.rX = _i.a.x;
  _i.rY = _i.a.y;
  _i.rW = _i.a.w;
  _i.rH = _i.a.h;
  // misc
  _i.P2 = 2 * _i.a.conPad;
  _i.oB2 = 2 * _i.a.conBor;
  _i.iB2 = 2 * _i.a.cliBor;

  // Constructor
  xFenster._ctor(_i);

} // end xFenster object prototype

/*--------------------- Static Properties and Methods ------------------------*/

xFenster.first = true;  // first instantiation
xFenster.nextZ = 100;   // z-index for next focus
xFenster.rszTmr = null; // window resize timer
xFenster.focused = null;// currently focused fenster
xFenster.instances = {};// all xFenster instances
xFenster.minimized = [];// currently minimized fensters

// Misc Static Methods ---------------------------------------------------------

/* Finish a minimize or a maximize. */
xFenster._finMM = function(xf, b, x, y, w, h, noR)
{
  if (!noR) {
    xf.rW = xf.con.offsetWidth;
    xf.rH = xf.con.offsetHeight;
    if (!xf.a.noFixed) {
      xf.rX = xf.con.offsetLeft;
      xf.rY = xf.con.offsetTop;
    }
    else {
      xf.rX = xPageX(xf.con);
      xf.rY = xPageY(xf.con);
    }
  }
  if (x != -1) {xMoveTo(xf.con, x, y);}
  if (b) {
    b.className = xf.a.clsRstI;
    b.title = xf.a.ttRestore;
    b.onclick = xf.restore;
  }
  if (!xf.a.noMove) {
    xf.tB.style.cursor = 'default';
    xf.tB.xDragEnabled = false;
  }
  if (!xf.a.noResize) {
    xf.rI.style.display = 'none';
    xf.rI.xDragEnabled = false;
  }
  xf.a.w = w;
  xf.a.h = h;
  xFenster._paint(xf, 0, 0);
};

/* Initialize the a (args) object. */
xFenster._iA = function(xf, o)
{
  var p, a;
  // Copy the xfArgs properties
  for (p in o) {
    if (o.hasOwnProperty(p)) {
      xf.a[p] = o[p];
    }
  }
  a = xf.a;
  // Set defaults
  if (!a.noFixed) a.fenceId = false; // fence not supported for fixed fensters
  if (!xDef(a.conBor)) a.conBor = 1;
  if (!xDef(a.conPad)) a.conPad = 1;
  if (!a.cliBor) a.cliBor = 0;
  if (!a.x) a.x = 0;
  if (!a.y) a.y = 0;
  if (!a.w) a.w = 200;
  if (!a.h) a.h = 200;
  if (!a.minW) a.minW = 100;
  if (!a.clsSB) a.clsSB = 'xfSBar';
  if (!a.clsSBF) a.clsSBF = 'xfSBarF';
  if (!a.clsTB) a.clsTB = 'xfTBar';
  if (!a.clsTBF) a.clsTBF = 'xfTBarF';
  if (!a.clsCli) a.clsCli = 'xfClient';
  if (!a.clsCon) a.clsCon = 'xfContainer';
  if (!a.clsMsk) a.clsMsk = 'xfMask';
  if (!a.clsRszI) a.clsRszI = 'xfRszIco';
  if (!xDef(a.ttResize)) a.ttResize = 'Resize';
//[r21/MENU
  if (!a.clsMnuI) a.clsMnuI = 'xfMnuIco';
  if (!a.clsMnu) a.clsMnu = 'xfMenu';
  if (!xDef(a.noMenu)) a.noMenu = !xDef(a.fnMenu);
//r21/MENU]
  if (!a.clsMinI) a.clsMinI = 'xfMinIco';
  if (!xDef(a.ttMinimize)) a.ttMinimize = 'Minimize';
  if (!a.clsMaxI) a.clsMaxI = 'xfMaxIco';
  if (!xDef(a.ttMaximize)) a.ttMaximize = 'Maximize';
  if (!a.clsCloI) a.clsCloI = 'xfCloIco';
  if (!xDef(a.ttClose)) a.ttClose = 'Close';
  if (!a.clsRstI) a.clsRstI = 'xfRstIco';
  if (!xDef(a.ttRestore)) a.ttRestore = 'Restore';
  if (!xDef(a.title)) a.title = '';
};

/* Constructor */
xFenster._ctor = function(xf)
{
  xFenster.instances[xf.a.clientId] = xf;
  // Create elements
  if (!xf.cli) {
    xf.cli = document.createElement( xf.ifr ? 'iframe' : 'div');
    xf.cli.id = xf.a.clientId;
  }
  xf.cli.className += ' ' + xf.a.clsCli;
  xf.con = document.createElement('div');
  xf.con.className = xf.a.clsCon;
  if (xf.ifr) {
    xf.msk = document.createElement('div');
    xf.msk.className = xf.a.clsMsk;
  }
  if (!xf.a.noResize) {
    xf.rI = document.createElement('div');
    xf.rI.className = xf.a.clsRszI;
    xf.rI.title = xf.a.ttResize;
  }
  if (!xf.a.noMinimize) {
    xf.nI = document.createElement('div');
    xf.nI.className = xf.a.clsMinI;
    xf.nI.title = xf.a.ttMinimize;
  }
  if (!xf.a.noMaximize) {
    xf.mI = document.createElement('div');
    xf.mI.className = xf.a.clsMaxI;
    xf.mI.title = xf.a.ttMaximize;
  }
  if (!xf.a.noClose) {
    xf.cI = document.createElement('div');
    xf.cI.className = xf.a.clsCloI;
    xf.cI.title = xf.a.ttClose;
  }
//[r21/MENU
  if (!xf.a.noMenu) {
    xf.uI = document.createElement('div');
    xf.uI.className = xf.a.clsMnuI;
    if (xf.a.ttMenu) xf.uI.title = xf.a.ttMenu;
    xf.mnu = document.createElement('div');
    xf.mnu.className = xf.a.clsMnu;
  }
//r21/MENU]
  xf.tB = document.createElement('div');
  xf.tB.className = xf.a.clsTB;
  xf.title(xf.a.title);
  if (!xf.a.noStatus) {
    xf.sB = document.createElement('div');
    xf.sB.className = xf.a.clsSB;
    xFenster._status(xf, '&nbsp;');
  }
  // Append elements
  xf.con.appendChild(xf.tB);
  if (xf.nI) xf.con.appendChild(xf.nI);
  if (xf.mI) xf.con.appendChild(xf.mI);
  if (xf.cI) xf.con.appendChild(xf.cI);
  if (xf.uI) xf.con.appendChild(xf.uI);//r21/MENU
  xf.con.appendChild(xf.cli);
  if (xf.sB) xf.con.appendChild(xf.sB);
  if (xf.rI) xf.con.appendChild(xf.rI);
  if (xf.msk) xf.con.appendChild(xf.msk);
  if (xf.mnu) xf.con.appendChild(xf.mnu);//r21/MENU
  document.body.appendChild(xf.con);
  // Position and paint the fenster
  /*@cc_on
  @if (@_jscript_version <= 5.6) // IE6 or down
      xf.a.noFixed = true;
    @else @*/
      if (!xf.a.noFixed) xf.con.style.position = 'fixed';
  /*@end @*/
  xf.con.style.borderWidth = xf.a.conBor + 'px';
  xf.cli.style.borderWidth = xf.a.cliBor + 'px';
  xf.cli.style.display = 'block'; // do this before paint
  xf.cli.style.visibility = 'visible';
  xf.tB.style.left = xf.tB.style.right = xf.tB.style.top = xf.a.conPad + 'px';
  if (xf.sB) {xf.sB.style.left = xf.sB.style.right = xf.sB.style.bottom = xf.a.conPad + 'px';}
  xf.cli.style.left = xf.a.conPad + 'px';
  if (xf.msk) xf.msk.style.left = xf.cli.style.left;
  if (xf.mnu) xf.mnu.style.left = (xf.a.conPad + xf.a.cliBor) + 'px'; //r21/MENU
  xMoveTo(xf.con, xf.a.x, xf.a.y);
  xFenster._paint(xf, 0, 0);
  // Position the icons
  var t = xf.a.conPad + xf.a.conBor, r = t;
  if (xf.cI) {
    xf.cI.style.top = t + 'px';
    xf.cI.style.right = r + 'px';
    r += xf.cI.offsetWidth + 2;
  }
  if (xf.mI) {
    xf.mI.style.top = t + 'px';
    xf.mI.style.right = r + 'px';
    r += xf.mI.offsetWidth + 2;
  }
  if (xf.nI) {
    xf.nI.style.top = t + 'px';
    xf.nI.style.right = r + 'px';
  }
//[r21/MENU
  if (xf.uI) {
    xf.uI.style.top = t + 'px';
    xf.uI.style.left = t + 'px';
  }
//r21/MENU]
  if (xf.rI) {
    xf.rI.style.right = xf.rI.style.bottom = t + 'px';
  }
  // Register the event listeners
  if (xf.ifr) {
    xFenster._href(xf, xf.a.url);
    if (xf.a.fnLoad) {xAddEventListener(xf.cli, 'load', function(){xf.a.fnLoad(xf);}, false);}
    xf.cli.name = xf.a.clientId;
  }
  if (!xf.a.noMove) xEnableDrag(xf.tB, xf.drgSL, xf.movDL, xf.drgEL);
  if (!xf.a.noResize) xEnableDrag(xf.rI, xf.drgSL, xf.rszDL, xf.drgEL);
  xf.con.onmousedown = xf.focus;
  if (!xf.a.noMenu) xf.uI.onclick = xf.mnuCL;//r21/MENU
  if (!xf.a.noMinimize) xf.nI.onclick = xf.minCL;
  if (!xf.a.noMaximize) xf.mI.onclick = xf.tB.ondblclick = xf.maxCL;
  if (!xf.a.noClose) {
    xf.cI.onclick = xf.hide;
    xf.cI.onmousedown = xStopPropagation;
  }
  if (xFenster.first) {
    xFenster.first = false;
    xAddEventListener(window, 'unload', xFenster._wUL, false);
    xAddEventListener(window, 'resize', xFenster._wRL, false);
  }
  // Make sure the fenster fits its boundaries
  xFenster.fitToBounds(xf);
  // Show the fenster
  xf.con.style.visibility = 'visible';
  xFenster._focus(xf);
};

// Static window event listeners -----------------------------------------------

/* Window resize event listener. */
xFenster._wRL = function()
{
  if (!xFenster.rszTmr) xFenster.rszTmr = setTimeout(xFenster._wRTL, 500);
};

/* Window resize timer listener. Resizes maximized fensters. Calls fitToBounds
   for each fenster. Repositions minimized fensters. */
xFenster._wRTL = function()
{
  var p, o = xFenster.instances;
  if (o) {
    for (p in o) {
      if (o.hasOwnProperty(p)) {
        if (o[p].max) { xFenster._maxCH(o[p], true); }
        xFenster.fitToBounds(o[p]);
      }
    }
    xFenster.fitMinToBounds();
  }
  xFenster.rszTmr = null;
};

/* Window unload event listener. */
xFenster._wUL = function()
{
  var p, o = xFenster.instances;
  if (o) {
    for (p in o) {
      if (o.hasOwnProperty(p)) {
        xFenster._destroy(o[p]);
      }
    }
    xFenster.focused = xFenster.instances = xFenster.minimized = null;
  }
  xRemoveEventListener(window, 'unload', xFenster._wUL, false);
  xRemoveEventListener(window, 'resize', xFenster._wRL, false);
};

// Static handlers for event listener methods ----------------------------------

/* Drag start handler. */
xFenster._drgSH = function(xf)
{
  xf.drg = true;
  xFenster.mask(true);
};

/* Drag end handler. */
xFenster._drgEH = function(xf)
{
  if (xf.msk) { xf.msk.style.display = 'none'; }
  if (xf.a.fnDragEnd) { xf.a.fnDragEnd(xf); }
  xf.drg = false;
};

/* Move drag handler. */
xFenster._movDH = function(xf, e, mdx, mdy)
{
  var x = xf.con.offsetLeft + mdx, y = xf.con.offsetTop + mdy;
  if (xFenster.inBounds(xf, mdx, mdy, 0, 0) && (!xf.a.fnMove || xf.a.fnMove(xf, x, y))) {
    xf.con.style.left = x + 'px';
    xf.con.style.top = y + 'px';
  }
};

/* Resize drag handler. */
xFenster._rszDH = function(xf, e, mdx, mdy)
{
  var w = xf.con.offsetWidth + mdx, h = xf.con.offsetHeight + mdy;
  if (w >= xf.a.minW && h >= 50) { // minimum size allowed
    if (xFenster.inBounds(xf, 0, 0, mdx, mdy) && (!xf.a.fnResize || xf.a.fnResize(xf, xf.cli.offsetWidth + mdx, xf.cli.offsetHeight + mdy))) {
      xFenster._paint(xf, mdx, mdy);
    }
  }
};

/* Maximize click handler. If noCb is true then the fnMaximize callback is not called. */
xFenster._maxCH = function(xf, noCb)
{
  var f, o, tw, th, w, h, x, y;
  if (!xf.a.noFixed) {
    x = y = 0;
    w = xClientWidth();
    h = xClientHeight();
  }
  else if (xf.a.fenceId) {
    f = xGetElementById(xf.a.fenceId);
    x = xPageX(f);
    y = xPageY(f);
    w = f.offsetWidth;
    h = f.offsetHeight;
  }
  else {
    x = y = 0;
    o = xDocSize();
    w = xClientWidth();
    //w = o.w;
    h = o.h;
  }
  tw = w - xf.P2 - xf.oB2; // target size of client element
  th = h - xf.tB.offsetHeight - (xf.sB ? xf.sB.offsetHeight : 0) - xf.P2 - xf.oB2;
  if (noCb || (!xf.a.fnMaximize || xf.a.fnMaximize(xf, tw, th))) {
    if (!xf.max) {xFenster._restore(xf);}
    xFenster._finMM(xf, xf.mI, x, y, w, h, xf.max);
    xf.max = true;
  }
};

/* Minimize click handler. */
xFenster._minCH = function(xf)
{
  if (xf.a.fnMinimize && !xf.a.fnMinimize(xf)) {
    return;
  }
  xFenster._restore(xf);
  xFenster.minimized[xFenster.minimized.length] = xf;
  xf.min = true;
  xf.cli.style.display = 'none';
  if (xf.sB) xf.sB.style.display = 'none';
  xFenster._finMM(xf, xf.nI, xf.con.offsetLeft, xf.con.offsetTop, xf.a.minW, xf.tB.offsetHeight + xf.P2 + xf.oB2);
  xFenster.fitMinToBounds();
};

//[r21/MENU
/* Menu icon click handler. */
xFenster._mnuCH = function(xf)
{
  if (xf.a.fnMenu && !xf.a.fnMenu(xf)) return;
  xf.mnu.style.display = 'block';
  xf.mnu.onmouseout = xf.con.onmouseout = xf.mnuOL;
};

/* Menu mouseout handler. Handles mouseout events on "o", which is either xf.con or xf.mnu. */
xFenster._mnuOH = function(xf, o, ne)
{
  var e = new xEvent(ne);
  var t = e.relatedTarget;
  while (t && t != o) t = t.parentNode;
  if (t != o) xf.mnuHide();
}
//r21/MENU]

// Static handlers for public methods ------------------------------------------

/* dw and dh are added to the existing container size. */
xFenster._paint = function(xf, dw, dh)
{
  xf.a.w += (dw || 0);
  xf.a.h += (dh || 0);
  xf.con.style.width = (xf.a.w - xf.oB2) + 'px';
  xf.con.style.height = (xf.a.h - xf.oB2) + 'px';
  /*@cc_on
  @if (@_jscript)
    xWidth(xf.tB, xf.a.w - xf.P2 - xf.oB2);
    if (!xf.min && xf.sB) {
      xWidth(xf.sB, xf.a.w - xf.P2 - xf.oB2);
      xf.sB.style.top = xf.a.h - xf.sB.offsetHeight - xf.a.conPad - xf.oB2;
    }
  @end @*/
  if (!xf.min) {
    xf.cli.style.top = (xf.a.conPad + xf.tB.offsetHeight) + 'px';
    xf.cli.style.width = (xf.a.w - xf.P2 - xf.oB2 - xf.iB2) + 'px';
    xf.cli.style.height = (xf.a.h - xf.tB.offsetHeight - (xf.sB ? xf.sB.offsetHeight : 0) - xf.P2 - xf.oB2 - xf.iB2) + 'px';
    if (xf.msk) {
      xf.msk.style.top = xf.cli.style.top;
      xf.msk.style.width = xf.cli.style.width;
      xf.msk.style.height = xf.cli.style.height;
    }
  }
  if (xf.mnu) xf.mnu.style.top = (xf.a.conPad + xf.a.cliBor + xf.tB.offsetHeight) + 'px'; //r21/MENU
  if (xf.a.fnPaint) xf.a.fnPaint(xf);
};

xFenster._focus = function(xf)
{
  if (xFenster.focused != xf && (!xf.a.fnFocus || xf.a.fnFocus(xf))) {
    xf.con.style.zIndex = xFenster.nextZ++;
    if (xFenster.focused) {
      xFenster.focused.tB.className = xFenster.focused.a.clsTB;
      if (xFenster.focused.sB) xFenster.focused.sB.className = xFenster.focused.a.clsSB;
    }
    xf.tB.className = xf.a.clsTBF;
    if (xf.sB) xf.sB.className = xf.a.clsSBF;
    xFenster.focused = xf;
    if (!xf.drg) { xFenster.mask(true, xf); }
  }
};

xFenster._href = function(xf, s)
{
  var h = s;
  if (xf.ifr) {
    if (xf.cli.contentWindow) {
      if (s) {xf.cli.contentWindow.location = s;}
      else h = xf.cli.contentWindow.location.href;
    }
    else if (typeof xf.cli.src == 'string') { // for Safari/Apollo/WebKit on Windows (old comment, may be different now?)
      if (s) {xf.cli.src = s;}
      else h = xf.cli.src;
    }
  }
  return h;
};

xFenster._hide = function(xf, e)
{
  var p, o = xFenster.instances, z = 0, hz = 0, f = null;
  if (!xf.a.fnClose || xf.a.fnClose(xf)) {
    xf.con.style.display = 'none';
    xf.hid = true;
    xStopPropagation(e);
    if (xf == xFenster.focused) {
      for (p in o) { // find the next appropriate fenster to focus
        if (o.hasOwnProperty(p) && o[p] && !o[p].hid && o[p] != xf) {
          z = parseInt(o[p].con.style.zIndex);
          if (z > hz) {
            hz = z;
            f = o[p];
          }
        }
      }
      if (f) {xFenster._focus(f);}
    }
  }
};

xFenster._show = function(xf)
{
  xf.con.style.display = 'block';
  xf.hid = false;
  xFenster._focus(xf);
};

xFenster._status = function(xf, s)
{
  if (xf.sB) {
    if (s) {xf.sB.innerHTML = s;}
    else return xf.sB.innerHTML;
  }
};

xFenster._title = function(xf, s)
{
  if (s) xf.tB.innerHTML = s;
  else return xf.tB.innerHTML;
};

xFenster._destroy = function(xf)
{
  if (xf) {
    xFenster._hide(xf);
    xf.con.onmousedown = xf.con.onclick = null;
    if (xf.nI) xf.nI.onclick = null;
    if (xf.mI) xf.mI.onclick = xf.tB.ondblclick = null;
    if (xf.cI) xf.cI.onclick = xf.cI.onmousedown = null;
    xFenster.instances[xf.a.clientId] = null;
    xf.con.parentNode.removeChild(xf.con);
  }
};

xFenster._minimize = function(xf)
{
  if (!xf.min && !xf.hid) {
    xFenster._minCH(xf);
    return true;
  }
  return false;
};

xFenster._maximize = function(xf)
{
  if (!xf.max && !xf.hid) {
    xFenster._maxCH(xf);
    return true;
  }
  return false;
};

xFenster._restore = function(xf)
{
  var b, c, j = -1, l, t;
  if (xf.max) {
    b = xf.mI;
    l = xf.maxCL;
    c = xf.a.clsMaxI;
    t = xf.a.ttMaximize;
    xf.max = false;
  }
  else if (xf.min) {
    b = xf.nI;
    l = xf.minCL;
    c = xf.a.clsMinI;
    t = xf.a.ttMinimize;
    j = xFenster.fitMinToBounds(xf);
    if (j > -1) { xFenster.minimized.splice(j, 1); }
    xf.min = false;
    xf.cli.style.display = 'block';
    if (xf.sB) xf.sB.style.display = 'block';
  }
  else {
    return false;
  }
  xMoveTo(xf.con, xf.rX, xf.rY);
  if (b) {
    b.className = c;
    b.title = t;
    b.onclick = l;
  }
  if (!xf.a.noMove) {
    xf.tB.style.cursor = 'move';
    xf.tB.xDragEnabled = true;
  }
  if (!xf.a.noResize) {
    xf.rI.style.display = 'block';
    xf.rI.xDragEnabled = true;
  }
  xf.a.w = xf.rW;
  xf.a.h = xf.rH;
  xFenster._paint(xf, 0, 0);
  if (xf.a.fnRestore) xf.a.fnRestore(xf);
  return true;
};

//[r21/MENU
xFenster._mnuHide = function(xf)
{
  xf.mnu.style.display = 'none';
  xf.mnu.onmouseout = null;
  xf.con.onmouseout = null;
};
//r21/MENU]

// Static "user" methods -------------------------------------------------------

/* Call a method of all fensters. */
xFenster.all = function(mth)
{
  var p, o = xFenster.instances;
  for (p in o) {
    if (o.hasOwnProperty(p)) {
      o[p][mth]();
    }
  }
};

xFenster.paintAll = function() { xFenster.all('paint'); };
xFenster.showAll = function() { xFenster.all('show'); };
xFenster.hideAll = function() { xFenster.all('hide'); };
xFenster.minimizeAll = function() { xFenster.all('minimize'); };
xFenster.maximizeAll = function() { xFenster.all('maximize'); };
xFenster.restoreAll = function() { xFenster.all('restore'); };

/* Mask (if on is true) or unmask (if on is false) all iframe fensters
   except for xf. */
xFenster.mask = function(on, xf)
{
  var p, o = xFenster.instances;
  for (p in o) {
    //if (o.hasOwnProperty(p) && o[p] && o[p].ifr && !o[p].hid && !o[p].min) {
    if (o.hasOwnProperty(p) && o[p] && o[p].ifr) { // r21
      o[p].msk.style.display = (!on || o[p] == xf) ? 'none' : 'block';
    }
  }
};

/* Return an object whose properties give the position and size of xf and its boundary. */
xFenster.getBounds = function(xf)
{
  var d, f, mm=(xf.max||xf.min), x, y, w, h, bx=0, by=0, bw, bh;
  if (!xf.a.noFixed) { // fixed
    bw = xClientWidth();
    bh = xClientHeight();
    x = xf.con.offsetLeft;
    y = xf.con.offsetTop;
  }
  else { // absolute
    x = xPageX(xf.con);
    y = xPageY(xf.con);
    if (!xf.a.fenceId) { // no fence
      d = xDocSize();
      bw = xClientWidth();
      bh = d.h;
    }
    else { // has fence
      f = xGetElementById(xf.a.fenceId);
      bx = xPageX(f);
      by = xPageY(f);
      bw = f.offsetWidth;
      bh = f.offsetHeight;
    }
  }
  // If minimized or maximized use xf's "restore" values instead of actual values.
  if (mm) {
    x = xf.rX;
    y = xf.rY;
    w = xf.rW;
    h = xf.rH;
  }
  else {
    w = xf.con.offsetWidth;
    h = xf.con.offsetHeight;
  }
  return {x:x, y:y, w:w, h:h, bx:bx, by:by, bw:bw, bh:bh};
};

/* Return true if xf, modified by (dx,dy,dw,dh), is within its boundary. */
xFenster.inBounds = function(xf, dx, dy, dw, dh)
{
  var o = xFenster.getBounds(xf);
  o.x += dx;
  o.y += dy;
  o.w += dw;
  o.h += dh;
  return (!(o.x <= o.bx || o.x + o.w > o.bx + o.bw || o.y <= o.by || o.y + o.h > o.by + o.bh));
};

/* Change xf's position and/or size to fit its boundary. Only decreases.
   This method is still experimental. */
xFenster.fitToBounds = function(xf)
{
  var o, mm=(xf.max||xf.min), xch=false, ych=false, pad = 10;
  // Get position and size of fenster and boundary.
  o = xFenster.getBounds(xf);
  // Find new x and w.
  if (o.x + o.w + pad > o.bx + o.bw) {
    o.x = o.bx + o.bw - o.w - pad;
    xch = true;
  }
  if (o.x < o.bx + pad) {
    o.x = o.bx + pad;
    if (xch) {o.w = o.bw - 2 * pad;}
    xch = true;
  }
  // Find new y and h.
  if (o.y + o.h + pad > o.by + o.bh) {
    o.y = o.by + o.bh - o.h - pad;
    ych = true;
  }
  if (o.y < o.by + pad) {
    o.y = o.by + pad;
    if (ych) {o.h = o.bh - 2 * pad;}
    ych = true;
  }
  // if a change is needed.
  if (xch || ych) {
    if (mm) { // change "restore" values
      xf.rX = o.x;
      xf.rY = o.y;
      xf.rW = o.w;
      xf.rH = o.h;
    }
    else { // change actual values
      xMoveTo(xf.con, o.x, o.y);
      xf.a.w = o.w;
      xf.a.h = o.h;
      xFenster._paint(xf, 0, 0);
    }
  }
};

/* Reposition all minimized fensters. If xf, omit it from the repositioning
   and return its index in the minimized array, else return -1. */
xFenster.fitMinToBounds = function(xf)
{
  var a=[], h=0, i, j=-1, o, p=2, r=1, x, y, xfi=-1, mz=xFenster.minimized;
  if (!mz.length) { return; }
  // First get largest height, and x position and row number for each
  o = xFenster.getBounds(mz[0]); // assumes all fensters have the same bounds
  x = o.bx + p;
  for (i = 0; i < mz.length; ++i) {
    if (xf && xf == mz[i]) {
      xfi = i;
      a[a.length] = null;
    }
    else {
      if (mz[i].con.offsetHeight > h) {
        h = mz[i].con.offsetHeight;
      }
      a[a.length] = {x:x, r:r};
      x += mz[i].con.offsetWidth + p;
      j = i + 1;
      if ((j < mz.length) && (mz[j] != xf) && (x + mz[j].con.offsetWidth + p) >= (o.bx + o.bw)) { // yuck, maybe rethink this method
        ++r; // next row
        x = o.bx + p;
      }
    }
  }
  // Now reposition them
  for (i = 0; i < a.length; ++i) {
    if (a[i]) {
      y = (o.by + o.bh - (( r - a[i].r + 1) * (h + p)));
      xMoveTo(mz[i].con, a[i].x, y);
    }
  }
  return xfi;
};

// end xFenster
