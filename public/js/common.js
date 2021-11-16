/*
 * Common helpers
 */

function O(elementId) { return typeof elementId === 'object' ? elementId : document.getElementById(elementId) }
function S(elementId) { return O(elementId).style }
function C(className) { return document.getElementsByClassName(className) }
function byName(name) { return document.getElementsByName(name) }
function getHtmlTag() { return document.documentElement }