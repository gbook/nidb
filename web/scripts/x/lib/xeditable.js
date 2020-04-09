// xEditable r3, Copyright 2005-2007 Jerod Venema
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xEditable(container,trigger)
{
var editElement = null;
var container = xGetElementById(container);
var trigger = xGetElementById(trigger);
var newID = container.id + "_edit";
xAddEventListener(container, 'click', BeginEdit);

function BeginEdit(){
  if(!editElement){
    // create the input box
    editElement = document.createElement('input');
    editElement.setAttribute('id', newID);
    editElement.setAttribute('name', newID);
    // prep the inputbox with the current value
    editElement.setAttribute('value', container.innerHTML);
    // kills small gecko bug
    editElement.setAttribute('autocomplete','OFF');
    // setup events that occur when editing is done
    xAddEventListener(editElement, 'blur', EndEditClick);
    xAddEventListener(editElement, 'keypress', EndEditKey);
    // make room for the inputbox, then add it
    container.innerHTML = '';
    container.appendChild(editElement);
    editElement.select();
    editElement.focus();
  }else{
    editElement.select();
    editElement.focus();
  }
}
function EndEditClick(){
  // save the entered value, and kill the input field
  container.innerHTML = editElement.value;
  editElement = null;
}
function EndEditKey(evt){
  // save the entered value, and kill the input field, but ONLY on an enter
  var e = new xEvent(evt);
  if(e.keyCode == 13){
    container.innerHTML = editElement.value;
    editElement = null;
  }
}
}
