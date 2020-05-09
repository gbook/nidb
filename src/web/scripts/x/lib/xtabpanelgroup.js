// xTabPanelGroup r12, Copyright 2005-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xTabPanelGroup(id, w, h, th, clsTP, clsTG, clsTD, clsTS) // object prototype
{
  // Private Methods

  function onClick()
  {
    paint(this);
    return false;
  }
  function onFocus()
  {
    paint(this);
  }
  function paint(tab)
  {
    tab.className = clsTS;
    tab.style.zIndex = highZ++;
    panels[tab.xTabIndex].style.display = 'block';
    if (selectedIndex != tab.xTabIndex) {
      panels[selectedIndex].style.display = 'none';
      tabs[selectedIndex].className = clsTD;
      selectedIndex = tab.xTabIndex;
    }
  }

  // Private Properties

  var panelGrp, tabGrp, panels, tabs, highZ, selectedIndex;
  
  // Public Methods

  this.select = function(n)
  {
    if (n && n <= tabs.length) {
      var t = tabs[n-1];
      if (t.focus) t.focus();
      else t.onclick();
    }
  };
  this.onResize = function(newW, newH)
  {
    var x = 0, i;
    if (newW) {
      w = newW;
      xWidth(panelGrp, w);
    }
    else w = xWidth(panelGrp);
    if (newH) {
      h = newH;
      xHeight(panelGrp, h);
    }
    else h = xHeight(panelGrp);
    xResizeTo(tabGrp[0], w, th);
    xMoveTo(tabGrp[0], 0, 0);
    w -= 2; // remove border widths
    var tw = w / tabs.length;
    for (i = 0; i < tabs.length; ++i) {
      xResizeTo(tabs[i], tw, th); 
      xMoveTo(tabs[i], x, 0);
      x += tw;
      tabs[i].xTabIndex = i;
      tabs[i].onclick = onClick;
      tabs[i].onfocus = onFocus;
      panels[i].style.display = 'none';
      xResizeTo(panels[i], w, h - th - 2); // -2 removes border widths
      xMoveTo(panels[i], 0, th);
    }
    highZ = i;
    tabs[selectedIndex].onclick();
  };

  // Constructor Code

  xTabPanelGroup.instances[id] = this;
  panelGrp = xGetElementById(id);
  if (!panelGrp) { return null; }
  panels = xGetElementsByClassName(clsTP, panelGrp);
  tabs = xGetElementsByClassName(clsTD, panelGrp);
  tabGrp = xGetElementsByClassName(clsTG, panelGrp);
  if (!panels || !tabs || !tabGrp || panels.length != tabs.length || tabGrp.length != 1) { return null; }
  selectedIndex = 0;
  this.onResize(w, h);
  xAddEventListener(window, 'unload',
    function () {
      for (var i = 0; i < tabs.length; ++i) {
        tabs[i].onfocus = tabs[i].onclick = null;
      }
      xTabPanelGroup.instances[id] = null;
    }, false
  );
}

xTabPanelGroup.instances = {}; // static property
