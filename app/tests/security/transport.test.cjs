const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const assert = require('node:assert/strict');
const calls = [], xhrHeaders = [], events = {};
function XHR() {}
XHR.prototype.open = function() {};
XHR.prototype.send = function() {};
XHR.prototype.setRequestHeader = function(k,v) { xhrHeaders.push([k,v]); };
const context = {URL, Headers, Request, location: {href:'https://fixture.invalid/app/',origin:'https://fixture.invalid'},
 document:{ querySelector:()=>({content:'fixture-csrf'}), addEventListener:(name, fn)=>{events[name]=fn;}, querySelectorAll:()=>[] },
 XMLHttpRequest:XHR, window:{ fetch:(input, options)=>{calls.push(options); return Promise.resolve();} }};
vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../../script/security-transport.js'),'utf8'), context);
context.window.fetch('/api/change',{method:'POST'});
assert.equal(calls.at(-1).headers.get('X-CSRF-Token'),'fixture-csrf');
context.window.fetch('https://outside.invalid/',{method:'POST'});
assert.equal(calls.at(-1).headers,undefined);
context.window.fetch('/api/read',{method:'GET'});
assert.equal(calls.at(-1).headers,undefined);
context.window.fetch(new Request('https://fixture.invalid/api/change',{method:'POST',headers:{'X-Custom':'kept'}}));
assert.equal(calls.at(-1).headers.get('X-Custom'),'kept');
assert.equal(calls.at(-1).headers.get('X-CSRF-Token'),'fixture-csrf');
const xhr = new XHR(); xhr.open('POST','/api/change'); xhr.send();
assert.deepEqual(xhrHeaders.pop(),['X-CSRF-Token','fixture-csrf']);
xhr.open('POST','https://outside.invalid/'); xhr.send(); assert.equal(xhrHeaders.length,0);
const input = {value:''};
events.submit({target:{tagName:'FORM',method:'post',action:'https://fixture.invalid/change',querySelector:()=>input}});
assert.equal(input.value,'fixture-csrf');
console.log(JSON.stringify({total:8,failed:0,coverage:['same-origin fetch','external fetch exclusion','safe GET exclusion','Request headers preserved','Request CSRF','same-origin XHR','external XHR exclusion','form CSRF']},null,2));
