/*
  demo1.js
  Collapsible/Expandable Sections
  This is the Javascript for the tutorial.
*/

xAddEventListener(window, 'load', initializeCollapsible, false);

function initializeCollapsible()
{
  var i, icon, headings = xGetElementsByClassName('collapsible');
  for (i = 0; i < headings.length; i++) {
    icon = document.createElement('div');
    icon.collapsibleSection = xNextSib(headings[i]);
    icon.onclick = iconOnClick;
    icon.onclick();
    icon.onmouseover = iconOnMouseover;
    icon.onmouseout = iconOnMouseout;
    headings[i].appendChild(icon);
  }
}

function iconOnClick()
{
  var section = this.collapsibleSection;
  if (section.style.display != 'block') {
    section.style.display = 'block';
    this.className = 'CollapseIcon';
    this.title = 'Click to collapse';
  }
  else {
    section.style.display = 'none';
    this.className = 'ExpandIcon';
    this.title = 'Click to expand';
  }
}

function iconOnMouseover()
{
  this.collapsibleSection.style.backgroundColor = '#cccccc';
}

function iconOnMouseout()
{
  this.collapsibleSection.style.backgroundColor = 'transparent';
}
