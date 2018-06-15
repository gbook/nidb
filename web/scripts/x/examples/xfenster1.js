document.write("<style type='text/css'>.xFenster {display:none}</style>");
var args = [
  { title: '<i>xf1</i>: Independence', x:100, y:100, w:420, h:200, minW: 150, conBor: 1, conPad: 1, cliBor: 0, noStatus: false, noResize: false, noMinimize: false, noMaximize: true,  noClose: false },
  { title: "<i>xf2</i>: Kepler",       x:125, y:130, w:420, h:210, minW: 120, conBor: 1, conPad: 2, cliBor: 1, noStatus: false, noResize: false, noMinimize: false, noMaximize: false, noClose: true },
  { title: '<i>xf3</i>: Algorithms',   x:150, y:160, w:420, h:220, minW: 150, conBor: 0, conPad: 0, cliBor: 1, noStatus: true,  noResize: true,  noMinimize: true,  noMaximize: true,  noClose: false },
  { title: '<i>xf4</i>: About',        x:175, y:190, w:420, h:250, minW: 120, conBor: 1, conPad: 0, cliBor: 0, noStatus: true,  noResize: true,  noMinimize: true,  noMaximize: true,  noClose: true }
];
xAddEventListener(window, 'load',
  function() {
    for (var i = 0; i < args.length; ++i) {
      args[i].clientId = 'xf' + (i + 1);
      new xFenster(args[i]);
    }
    xGetElementById('clk1').onclick=function(){xFenster.paintAll();};
    xGetElementById('clk2').onclick=function(){xFenster.showAll();};
    xGetElementById('clk3').onclick=function(){xFenster.hideAll();};
    xGetElementById('clk4').onclick=function(){xFenster.minimizeAll();};
    xGetElementById('clk5').onclick=function(){xFenster.maximizeAll();};
    xGetElementById('clk6').onclick=function(){xFenster.restoreAll();};
    xf4Load();
    setInterval(ticker, 1000);
  }, false
);
function ticker()
{
  xFenster.instances.xf1.status(new Date().toString());
}
function xf4Load()
{
  xFenster.instances.xf4.cli.innerHTML =
    "<div class='fenster-content'>" +
    "<h4>xF" + "enster<\/h4>" +
    "<p><i>focus<\/i> me by clicking anywhere on me.<\/p>" +
    "<p><i>move<\/i> me by dragging on the title bar.<\/p>" +
    "<p><i>minimize<\/i> me by clicking on the <img src='../../images/xf_minimize_icon.gif'> icon.<\/p>" +
    "<p><i>maximize<\/i> me by clicking on the <img src='../../images/xf_maximize_icon.gif'> icon or by double-clicking on the title bar.<\/p>" +
    "<p><i>restore<\/i> me by clicking on the <img src='../../images/xf_restore_icon.gif'> icon.<\/p>" +
    "<p><i>close<\/i> me by clicking on the <img src='../../images/xf_close_icon.gif'> icon.<\/p>" +
    "<p><i>resize<\/i> me by dragging on the <img src='../../images/xf_resize_icon.gif'> icon.<\/p>" +
    "<\/div>";
}
