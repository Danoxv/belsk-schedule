/*
 * Common helpers
 */

function O(elementId) { return typeof elementId === 'object' ? elementId : document.getElementById(elementId) }
function S(elementId) { return O(elementId).style }
function C(className) { return document.getElementsByClassName(className) }
function byName(name) { return document.getElementsByName(name) }
function getHtmlTag() { return document.documentElement }
function debounce(func, timeout = 250){
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => { func.apply(this, args); }, timeout);
  };
}
function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}