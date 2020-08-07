@php
$config = [
    'appName' => config('app.name'),
    'locale' => $locale = app()->getLocale(),
    'locales' => config('app.locales'),
    'githubAuth' => config('services.github.client_id'),
];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="shortcut icon" type="image/png" href="assets/img/favicon.png"/>
  <title>{{ config('app.name') }}</title>

  <link rel="stylesheet" href="{{ mix('dist/css/app.css') }}">
  <script>
    var flowy=function(e,t,l,i,o,n,r){t||(t=function(){}),l||(l=function(){}),i||(i=function(){return!0}),o||(o=function(){return!1}),n||(n=20),r||(r=80),Element.prototype.matches||(Element.prototype.matches=Element.prototype.msMatchesSelector||Element.prototype.webkitMatchesSelector),Element.prototype.closest||(Element.prototype.closest=function(e){var t=this;do{if(Element.prototype.matches.call(t,e))return t;t=t.parentElement||t.parentNode}while(null!==t&&1===t.nodeType);return null});var d=!1;function c(e,t,l){return i(e,t,l)}function a(e,t){return o(e,t)}flowy.load=function(){if(!d){d=!0;var i,o,s,p,u,g,w=[],f=[],h=e,y=!1,C=n,v=r,m=0,x=!1,B=!1,R=0,S=document.createElement("DIV");S.classList.add("indicator"),S.classList.add("invisible"),h.appendChild(S),flowy.import=function(e){h.innerHTML=e.html;for(var t=0;t<e.blockarr.length;t++){var l={childwidth:parseFloat(e.blockarr[t].childwidth),parent:parseFloat(e.blockarr[t].parent),id:parseFloat(e.blockarr[t].id),x:parseFloat(e.blockarr[t].x),y:parseFloat(e.blockarr[t].y),width:parseFloat(e.blockarr[t].width),height:parseFloat(e.blockarr[t].height)};w.push(l)}w.length>1&&q()},flowy.output=function(){var e={html:h.innerHTML,blockarr:w,blocks:[]};if(w.length>0){for(var t=0;t<w.length;t++){e.blocks.push({id:w[t].id,parent:w[t].parent,data:[],attr:[]});var l=document.querySelector(".blockid[value='"+w[t].id+"']").parentNode;l.querySelectorAll("input").forEach(function(l){var i=l.getAttribute("name"),o=l.value;e.blocks[t].data.push({name:i,value:o})}),Array.prototype.slice.call(l.attributes).forEach(function(l){var i={};i[l.name]=l.value,e.blocks[t].attr.push(i)})}return e}},flowy.deleteBlocks=function(){w=[],h.innerHTML="<div class='indicator invisible'></div>"},flowy.beginDrag=function(e){if(e.targetTouches?(u=e.changedTouches[0].clientX,g=e.changedTouches[0].clientY):(u=e.clientX,g=e.clientY),3!=e.which&&e.target.closest(".create-flowy")){p=e.target.closest(".create-flowy");var l=e.target.closest(".create-flowy").cloneNode(!0);e.target.closest(".create-flowy").classList.add("dragnow"),l.classList.add("block"),l.classList.remove("create-flowy"),0===w.length?(l.innerHTML+="<input type='hidden' name='blockid' class='blockid' value='"+w.length+"'>",document.body.appendChild(l),i=document.querySelector(".blockid[value='"+w.length+"']").parentNode):(l.innerHTML+="<input type='hidden' name='blockid' class='blockid' value='"+(Math.max.apply(Math,w.map(e=>e.id))+1)+"'>",document.body.appendChild(l),i=document.querySelector(".blockid[value='"+(parseInt(Math.max.apply(Math,w.map(e=>e.id)))+1)+"']").parentNode),n=e.target.closest(".create-flowy"),t(n),i.classList.add("dragging"),y=!0,o=u-e.target.closest(".create-flowy").getBoundingClientRect().left,s=g-e.target.closest(".create-flowy").getBoundingClientRect().top,i.style.left=u-o+"px",i.style.top=g-s+"px"}var n},document.addEventListener("mousedown",b,!1),document.addEventListener("touchstart",b,!1),document.addEventListener("mouseup",b,!1),flowy.touchDone=function(){B=!1},document.addEventListener("mousedown",flowy.beginDrag),document.addEventListener("touchstart",flowy.beginDrag),flowy.endDrag=function(e){if(3!=e.which&&(y||x))if(B=!1,l(),document.querySelector(".indicator").classList.contains("invisible")||document.querySelector(".indicator").classList.add("invisible"),y&&(p.classList.remove("dragnow"),i.classList.remove("dragging")),0===parseInt(i.querySelector(".blockid").value)&&x){i.classList.remove("dragging"),x=!1;for(var t=0;t<f.length;t++)if(f[t].id!=parseInt(i.querySelector(".blockid").value)){const e=document.querySelector(".blockid[value='"+f[t].id+"']").parentNode,l=document.querySelector(".arrowid[value='"+f[t].id+"']").parentNode;e.style.left=e.getBoundingClientRect().left+window.scrollX-(h.getBoundingClientRect().left+window.scrollX)+h.scrollLeft-1+"px",e.style.top=e.getBoundingClientRect().top+window.scrollY-(h.getBoundingClientRect().top+window.scrollY)+h.scrollTop-1+"px",l.style.left=l.getBoundingClientRect().left+window.scrollX-(h.getBoundingClientRect().left+window.scrollX)+h.scrollLeft-1+"px",l.style.top=l.getBoundingClientRect().top+window.scrollY-h.getBoundingClientRect().top+h.scrollTop-1+"px",h.appendChild(e),h.appendChild(l),f[t].x=e.getBoundingClientRect().left+window.scrollX+parseInt(e.offsetWidth)/2+h.scrollLeft-1,f[t].y=e.getBoundingClientRect().top+window.scrollY+parseInt(e.offsetHeight)/2+h.scrollTop-1}f.filter(e=>0==e.id)[0].x=i.getBoundingClientRect().left+window.scrollX+parseInt(window.getComputedStyle(i).width)/2+h.scrollLeft,f.filter(e=>0==e.id)[0].y=i.getBoundingClientRect().top+window.scrollY+parseInt(window.getComputedStyle(i).height)/2+h.scrollTop,w=w.concat(f),f=[]}else if(y&&0==w.length&&i.getBoundingClientRect().top+window.scrollY>h.getBoundingClientRect().top+window.scrollY&&i.getBoundingClientRect().left+window.scrollX>h.getBoundingClientRect().left+window.scrollX)c(i,!0,void 0),y=!1,i.style.top=i.getBoundingClientRect().top+window.scrollY-(h.getBoundingClientRect().top+window.scrollY)+h.scrollTop+"px",i.style.left=i.getBoundingClientRect().left+window.scrollX-(h.getBoundingClientRect().left+window.scrollX)+h.scrollLeft+"px",h.appendChild(i),w.push({parent:-1,childwidth:0,id:parseInt(i.querySelector(".blockid").value),x:i.getBoundingClientRect().left+window.scrollX+parseInt(window.getComputedStyle(i).width)/2+h.scrollLeft,y:i.getBoundingClientRect().top+window.scrollY+parseInt(window.getComputedStyle(i).height)/2+h.scrollTop,width:parseInt(window.getComputedStyle(i).width),height:parseInt(window.getComputedStyle(i).height)});else if(y&&0==w.length)h.appendChild(document.querySelector(".indicator")),i.parentNode.removeChild(i);else if(y)for(var o=i.getBoundingClientRect().left+window.scrollX+parseInt(window.getComputedStyle(i).width)/2+h.scrollLeft,n=i.getBoundingClientRect().top+window.scrollY+h.scrollTop,r=w.map(e=>e.id),d=0;d<w.length;d++){if(o>=w.filter(e=>e.id==r[d])[0].x-w.filter(e=>e.id==r[d])[0].width/2-C&&o<=w.filter(e=>e.id==r[d])[0].x+w.filter(e=>e.id==r[d])[0].width/2+C&&n>=w.filter(e=>e.id==r[d])[0].y-w.filter(e=>e.id==r[d])[0].height/2&&n<=w.filter(e=>e.id==r[d])[0].y+w.filter(e=>e.id==r[d])[0].height){y=!1,c(i,!1,document.querySelector(".blockid[value='"+r[d]+"']").parentNode)?L(i,d,r):(y=!1,h.appendChild(document.querySelector(".indicator")),i.parentNode.removeChild(i));break}d==w.length-1&&(y=!1,h.appendChild(document.querySelector(".indicator")),i.parentNode.removeChild(i))}else if(x)for(o=i.getBoundingClientRect().left+window.scrollX+parseInt(window.getComputedStyle(i).width)/2+h.scrollLeft,n=i.getBoundingClientRect().top+window.scrollY+h.scrollTop,r=w.map(e=>e.id),d=0;d<w.length;d++){if(o>=w.filter(e=>e.id==r[d])[0].x-w.filter(e=>e.id==r[d])[0].width/2-C&&o<=w.filter(e=>e.id==r[d])[0].x+w.filter(e=>e.id==r[d])[0].width/2+C&&n>=w.filter(e=>e.id==r[d])[0].y-w.filter(e=>e.id==r[d])[0].height/2&&n<=w.filter(e=>e.id==r[d])[0].y+w.filter(e=>e.id==r[d])[0].height){y=!1,i.classList.remove("dragging"),L(i,d,r);break}if(d==w.length-1){if(a(i,w.filter(e=>e.id==r[d])[0])){y=!1,i.classList.remove("dragging"),L(i,r.indexOf(R),r);break}x=!1,f=[],y=!1,h.appendChild(document.querySelector(".indicator")),i.parentNode.removeChild(i);break}}},document.addEventListener("mouseup",flowy.endDrag,!1),document.addEventListener("touchend",flowy.endDrag,!1),flowy.moveBlock=function(e){if(e.targetTouches?(u=e.targetTouches[0].clientX,g=e.targetTouches[0].clientY):(u=e.clientX,g=e.clientY),B){x=!0,i.classList.add("dragging");var t=parseInt(i.querySelector(".blockid").value);R=w.filter(e=>e.id==t)[0].parent,f.push(w.filter(e=>e.id==t)[0]),w=w.filter(function(e){return e.id!=t}),0!=t&&document.querySelector(".arrowid[value='"+t+"']").parentNode.remove();for(var l=w.filter(e=>e.parent==t),n=!1,r=[],d=[];!n;){for(var c=0;c<l.length;c++)if(l[c]!=t){f.push(w.filter(e=>e.id==l[c].id)[0]);const e=document.querySelector(".blockid[value='"+l[c].id+"']").parentNode,t=document.querySelector(".arrowid[value='"+l[c].id+"']").parentNode;e.style.left=e.getBoundingClientRect().left+window.scrollX-(i.getBoundingClientRect().left+window.scrollX)+"px",e.style.top=e.getBoundingClientRect().top+window.scrollY-(i.getBoundingClientRect().top+window.scrollY)+"px",t.style.left=t.getBoundingClientRect().left+window.scrollX-(i.getBoundingClientRect().left+window.scrollX)+"px",t.style.top=t.getBoundingClientRect().top+window.scrollY-(i.getBoundingClientRect().top+window.scrollY)+"px",i.appendChild(e),i.appendChild(t),r.push(l[c].id),d.push(l[c].id)}0==r.length?n=!0:(l=w.filter(e=>r.includes(e.parent)),r=[])}for(c=0;c<w.filter(e=>e.parent==t).length;c++){var a=w.filter(e=>e.parent==t)[c];w=w.filter(function(e){return e.id!=a})}for(c=0;c<d.length;c++){a=d[c];w=w.filter(function(e){return e.id!=a})}w.length>1&&q(),B=!1}if(y?(i.style.left=u-o+"px",i.style.top=g-s+"px"):x&&(i.style.left=u-o-(h.getBoundingClientRect().left+window.scrollX)+h.scrollLeft+"px",i.style.top=g-s-(h.getBoundingClientRect().top+window.scrollY)+h.scrollTop+"px",f.filter(e=>e.id==parseInt(i.querySelector(".blockid").value)).x=i.getBoundingClientRect().left+window.scrollX+parseInt(window.getComputedStyle(i).width)/2+h.scrollLeft,f.filter(e=>e.id==parseInt(i.querySelector(".blockid").value)).y=i.getBoundingClientRect().left+window.scrollX+parseInt(window.getComputedStyle(i).height)/2+h.scrollTop),y||x){u>h.getBoundingClientRect().width+h.getBoundingClientRect().left-10&&u<h.getBoundingClientRect().width+h.getBoundingClientRect().left+10?h.scrollLeft+=10:u<h.getBoundingClientRect().left+10&&u>h.getBoundingClientRect().left-10?h.scrollLeft-=10:g>h.getBoundingClientRect().height+h.getBoundingClientRect().top-10&&g<h.getBoundingClientRect().height+h.getBoundingClientRect().top+10?h.scrollTop+=10:g<h.getBoundingClientRect().top+10&&g>h.getBoundingClientRect().top-10&&(h.scrollLeft-=10);var p=i.getBoundingClientRect().left+window.scrollX+parseInt(window.getComputedStyle(i).width)/2+h.scrollLeft,v=i.getBoundingClientRect().top+window.scrollY+h.scrollTop,m=w.map(e=>e.id);for(c=0;c<w.length;c++){if(p>=w.filter(e=>e.id==m[c])[0].x-w.filter(e=>e.id==m[c])[0].width/2-C&&p<=w.filter(e=>e.id==m[c])[0].x+w.filter(e=>e.id==m[c])[0].width/2+C&&v>=w.filter(e=>e.id==m[c])[0].y+w.filter(e=>e.id==m[c])[0].height/2&&v<=w.filter(e=>e.id==m[c])[0].y+w.filter(e=>e.id==m[c])[0].height){document.querySelector(".blockid[value='"+m[c]+"']").parentNode.appendChild(document.querySelector(".indicator")),document.querySelector(".indicator").style.left=document.querySelector(".blockid[value='"+m[c]+"']").parentNode.offsetWidth/2-5+"px",document.querySelector(".indicator").style.top=document.querySelector(".blockid[value='"+m[c]+"']").parentNode.offsetHeight+"px",document.querySelector(".indicator").classList.remove("invisible");break}c==w.length-1&&(document.querySelector(".indicator").classList.contains("invisible")||document.querySelector(".indicator").classList.add("invisible"))}}},document.addEventListener("mousemove",flowy.moveBlock,!1),document.addEventListener("touchmove",flowy.moveBlock,!1)}function L(e,t,l){x||h.appendChild(e);for(var i=0,o=0,n=0;n<w.filter(e=>e.parent==l[t]).length;n++){(u=w.filter(e=>e.parent==l[t])[n]).childwidth>u.width?i+=u.childwidth+C:i+=u.width+C}i+=parseInt(window.getComputedStyle(e).width);for(n=0;n<w.filter(e=>e.parent==l[t]).length;n++){(u=w.filter(e=>e.parent==l[t])[n]).childwidth>u.width?(document.querySelector(".blockid[value='"+u.id+"']").parentNode.style.left=w.filter(e=>e.id==l[t])[0].x-i/2+o+u.childwidth/2-u.width/2+"px",u.x=w.filter(e=>e.parent==l[t])[0].x-i/2+o+u.childwidth/2,o+=u.childwidth+C):(document.querySelector(".blockid[value='"+u.id+"']").parentNode.style.left=w.filter(e=>e.id==l[t])[0].x-i/2+o+"px",u.x=w.filter(e=>e.parent==l[t])[0].x-i/2+o+u.width/2,o+=u.width+C)}if(e.style.left=w.filter(e=>e.id==l[t])[0].x-i/2+o-(h.getBoundingClientRect().left+window.scrollX)+h.scrollLeft+"px",e.style.top=w.filter(e=>e.id==l[t])[0].y+w.filter(e=>e.id==l[t])[0].height/2+v-(h.getBoundingClientRect().top+window.scrollY)+"px",x){f.filter(t=>t.id==parseInt(e.querySelector(".blockid").value))[0].x=e.getBoundingClientRect().left+window.scrollX+parseInt(window.getComputedStyle(e).width)/2+h.scrollLeft,f.filter(t=>t.id==parseInt(e.querySelector(".blockid").value))[0].y=e.getBoundingClientRect().top+window.scrollY+parseInt(window.getComputedStyle(e).height)/2+h.scrollTop,f.filter(t=>t.id==e.querySelector(".blockid").value)[0].parent=l[t];for(n=0;n<f.length;n++)if(f[n].id!=parseInt(e.querySelector(".blockid").value)){const e=document.querySelector(".blockid[value='"+f[n].id+"']").parentNode,t=document.querySelector(".arrowid[value='"+f[n].id+"']").parentNode;e.style.left=e.getBoundingClientRect().left+window.scrollX-(h.getBoundingClientRect().left+window.scrollX)+h.scrollLeft+"px",e.style.top=e.getBoundingClientRect().top+window.scrollY-(h.getBoundingClientRect().top+window.scrollY)+h.scrollTop+"px",t.style.left=t.getBoundingClientRect().left+window.scrollX-(h.getBoundingClientRect().left+window.scrollX)+h.scrollLeft+20+"px",t.style.top=t.getBoundingClientRect().top+window.scrollY-(h.getBoundingClientRect().top+window.scrollY)+h.scrollTop+"px",h.appendChild(e),h.appendChild(t),f[n].x=e.getBoundingClientRect().left+window.scrollX+parseInt(window.getComputedStyle(e).width)/2+h.scrollLeft,f[n].y=e.getBoundingClientRect().top+window.scrollY+parseInt(window.getComputedStyle(e).height)/2+h.scrollTop}w=w.concat(f),f=[]}else w.push({childwidth:0,parent:l[t],id:parseInt(e.querySelector(".blockid").value),x:e.getBoundingClientRect().left+window.scrollX+parseInt(window.getComputedStyle(e).width)/2+h.scrollLeft,y:e.getBoundingClientRect().top+window.scrollY+parseInt(window.getComputedStyle(e).height)/2+h.scrollTop,width:parseInt(window.getComputedStyle(e).width),height:parseInt(window.getComputedStyle(e).height)});var r=w.filter(t=>t.id==parseInt(e.querySelector(".blockid").value))[0],d=r.x-w.filter(e=>e.id==l[t])[0].x+20,c=parseFloat(r.y-r.height/2-(w.filter(e=>e.parent==l[t])[0].y+w.filter(e=>e.parent==l[t])[0].height/2)+h.scrollTop);if(d<0?(h.innerHTML+='<div class="arrowblock"><input type="hidden" class="arrowid" value="'+e.querySelector(".blockid").value+'"><svg preserveaspectratio="none" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M'+(w.filter(e=>e.id==l[t])[0].x-r.x+5)+" 0L"+(w.filter(e=>e.id==l[t])[0].x-r.x+5)+" "+v/2+"L5 "+v/2+"L5 "+c+'" stroke="#C5CCD0" stroke-width="2px"/><path d="M0 '+(c-5)+"H10L5 "+c+"L0 "+(c-5)+'Z" fill="#C5CCD0"/></svg></div>',document.querySelector('.arrowid[value="'+e.querySelector(".blockid").value+'"]').parentNode.style.left=r.x-5-(h.getBoundingClientRect().left+window.scrollX)+h.scrollLeft+"px"):(h.innerHTML+='<div class="arrowblock"><input type="hidden" class="arrowid" value="'+e.querySelector(".blockid").value+'"><svg preserveaspectratio="none" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 0L20 '+v/2+"L"+d+" "+v/2+"L"+d+" "+c+'" stroke="#C5CCD0" stroke-width="2px"/><path d="M'+(d-5)+" "+(c-5)+"H"+(d+5)+"L"+d+" "+c+"L"+(d-5)+" "+(c-5)+'Z" fill="#C5CCD0"/></svg></div>',document.querySelector('.arrowid[value="'+parseInt(e.querySelector(".blockid").value)+'"]').parentNode.style.left=w.filter(e=>e.id==l[t])[0].x-20-(h.getBoundingClientRect().left+window.scrollX)+h.scrollLeft+"px"),document.querySelector('.arrowid[value="'+parseInt(e.querySelector(".blockid").value)+'"]').parentNode.style.top=w.filter(e=>e.id==l[t])[0].y+w.filter(e=>e.id==l[t])[0].height/2+"px",-1!=w.filter(e=>e.id==l[t])[0].parent){for(var a=!1,s=l[t];!a;)if(-1==w.filter(e=>e.id==s)[0].parent)a=!0;else{var p=0;for(n=0;n<w.filter(e=>e.parent==s).length;n++){var u;(u=w.filter(e=>e.parent==s)[n]).childwidth>u.width?n==w.filter(e=>e.parent==s).length-1?p+=u.childwidth:p+=u.childwidth+C:n==w.filter(e=>e.parent==s).length-1?p+=u.width:p+=u.width+C}w.filter(e=>e.id==s)[0].childwidth=p,s=w.filter(e=>e.id==s)[0].parent}w.filter(e=>e.id==s)[0].childwidth=i}x&&(x=!1,e.classList.remove("dragging")),q(),function(){m=w.map(e=>e.x);var e=w.map(e=>e.width),t=m.map(function(t,l){return t-e[l]/2});if((m=Math.min.apply(Math,t))<h.getBoundingClientRect().left+window.scrollX){!0;for(var l=w.map(e=>e.id),i=0;i<w.length;i++)if(document.querySelector(".blockid[value='"+w.filter(e=>e.id==l[i])[0].id+"']").parentNode.style.left=w.filter(e=>e.id==l[i])[0].x-w.filter(e=>e.id==l[i])[0].width/2-m+20+"px",-1!=w.filter(e=>e.id==l[i])[0].parent){var o=w.filter(e=>e.id==l[i])[0],n=o.x-w.filter(e=>e.id==w.filter(e=>e.id==l[i])[0].parent)[0].x;document.querySelector('.arrowid[value="'+l[i]+'"]').parentNode.style.left=n<0?o.x-m+20-5+"px":w.filter(e=>e.id==w.filter(e=>e.id==l[i])[0].parent)[0].x-20-m+20+"px"}for(var i=0;i<w.length;i++)w[i].x=document.querySelector(".blockid[value='"+w[i].id+"']").parentNode.getBoundingClientRect().left+window.scrollX+(h.getBoundingClientRect().left+h.scrollLeft)-parseInt(window.getComputedStyle(document.querySelector(".blockid[value='"+w[i].id+"']").parentNode).width)/2-40;m}}()}function b(e){if(B=!1,k(e.target,"block")){var t=e.target.closest(".block");e.targetTouches?(u=e.targetTouches[0].clientX,g=e.targetTouches[0].clientY):(u=e.clientX,g=e.clientY),"mouseup"!==e.type&&k(e.target,"block")&&3!=e.which&&(y||x||(B=!0,o=u-((i=t).getBoundingClientRect().left+window.scrollX),s=g-(i.getBoundingClientRect().top+window.scrollY)))}}function k(e,t){return!!(e.className&&e.className.split(" ").indexOf(t)>=0)||e.parentNode&&k(e.parentNode,t)}function q(){for(var e=w.map(e=>e.parent),t=0;t<e.length;t++){-1==e[t]&&t++;for(var l=0,i=0,o=0;o<w.filter(l=>l.parent==e[t]).length;o++){var n=w.filter(l=>l.parent==e[t])[o];0==w.filter(e=>e.parent==n.id).length&&(n.childwidth=0),n.childwidth>n.width?o==w.filter(l=>l.parent==e[t]).length-1?l+=n.childwidth:l+=n.childwidth+C:o==w.filter(l=>l.parent==e[t]).length-1?l+=n.width:l+=n.width+C}-1!=e[t]&&(w.filter(l=>l.id==e[t])[0].childwidth=l);for(o=0;o<w.filter(l=>l.parent==e[t]).length;o++){n=w.filter(l=>l.parent==e[t])[o];const a=document.querySelector(".blockid[value='"+n.id+"']").parentNode,s=w.filter(l=>l.id==e[t]);a.style.top=s.y+v+"px",s.y=s.y+v,n.childwidth>n.width?(a.style.left=s[0].x-l/2+i+n.childwidth/2-n.width/2-(h.getBoundingClientRect().left+window.scrollX)+"px",n.x=s[0].x-l/2+i+n.childwidth/2,i+=n.childwidth+C):(a.style.left=s[0].x-l/2+i-(h.getBoundingClientRect().left+window.scrollX)+"px",n.x=s[0].x-l/2+i+n.width/2,i+=n.width+C);var r=w.filter(e=>e.id==n.id)[0],d=r.x-w.filter(e=>e.id==n.parent)[0].x+20,c=r.y-r.height/2-(w.filter(e=>e.id==n.parent)[0].y+w.filter(e=>e.id==n.parent)[0].height/2);document.querySelector('.arrowid[value="'+n.id+'"]').parentNode.style.top=w.filter(e=>e.id==n.parent)[0].y+w.filter(e=>e.id==n.parent)[0].height/2-(h.getBoundingClientRect().top+window.scrollY)+"px",d<0?(document.querySelector('.arrowid[value="'+n.id+'"]').parentNode.style.left=r.x-5-(h.getBoundingClientRect().left+window.scrollX)+"px",document.querySelector('.arrowid[value="'+n.id+'"]').parentNode.innerHTML='<input type="hidden" class="arrowid" value="'+n.id+'"><svg preserveaspectratio="none" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M'+(w.filter(e=>e.id==n.parent)[0].x-r.x+5)+" 0L"+(w.filter(e=>e.id==n.parent)[0].x-r.x+5)+" "+v/2+"L5 "+v/2+"L5 "+c+'" stroke="#C5CCD0" stroke-width="2px"/><path d="M0 '+(c-5)+"H10L5 "+c+"L0 "+(c-5)+'Z" fill="#C5CCD0"/></svg>'):(document.querySelector('.arrowid[value="'+n.id+'"]').parentNode.style.left=w.filter(e=>e.id==n.parent)[0].x-20-(h.getBoundingClientRect().left+window.scrollX)+"px",document.querySelector('.arrowid[value="'+n.id+'"]').parentNode.innerHTML='<input type="hidden" class="arrowid" value="'+n.id+'"><svg preserveaspectratio="none" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 0L20 '+v/2+"L"+d+" "+v/2+"L"+d+" "+c+'" stroke="#C5CCD0" stroke-width="2px"/><path d="M'+(d-5)+" "+(c-5)+"H"+(d+5)+"L"+d+" "+c+"L"+(d-5)+" "+(c-5)+'Z" fill="#C5CCD0"/></svg>')}}}},flowy.load()};
  </script>
  <script>
    document.addEventListener("DOMContentLoaded", function(){

    });
  </script>
  <style>
    body, html {
      margin: 0px;
      padding: 0px;
      overflow: hidden;
      background-image: url(assets/tile.png);
      background-repeat: repeat;
      background-size: 30px 30px;
      background-color: #FBFBFB;
    }
    #navigation {
      height: 71px;
      background-color: #FFF;
      border: 1px solid #E8E8EF;
      width: 100%;
      display: table;
      box-sizing: border-box;
      position: fixed;
      top: 0;
      z-index: 9
    }
    #back {
      width: 40px;
      height: 40px;
      border-radius: 100px;
      background-color: #F1F4FC;
      text-align: center;
      display: inline-block;
      vertical-align: top;
      margin-top: 12px;
      margin-right: 10px
    }
    #back img {
      margin-top: 13px;
    }
    #names {
      display: inline-block;
      vertical-align: top;
    }
    #title {
      font-family: Roboto;
      font-weight: 500;
      font-size: 16px;
      color: #393C44;
      margin-bottom: 0px;
    }
    #subtitle {
      font-family: Roboto;
      color: #808292;
      font-size: 14px;
      margin-top: 5px;
    }
    #leftside {
      display: inline-block;
      vertical-align: middle;
      margin-left: 20px;
    }
    #centerswitch {
      position: absolute;
      width: 222px;
      left: 50%;
      margin-left: -111px;
      top: 15px;
    }
    #leftswitch {
      border: 1px solid #E8E8EF;
      background-color: #FBFBFB;
      width: 111px;
      height: 39px;
      line-height: 39px;
      border-radius: 5px 0px 0px 5px;
      font-family: Roboto;
      color: #393C44;
      display: inline-block;
      font-size: 14px;
      text-align: center;
    }
    #rightswitch {
      font-family: Roboto;
      color: #808292;
      border-radius: 0px 5px 5px 0px;
      border: 1px solid #E8E8EF;
      height: 39px;
      width: 102px;
      display: inline-block;
      font-size: 14px;
      line-height: 39px;
      text-align: center;
      margin-left: -5px;
    }
    #discard {
      font-family: Roboto;
      font-weight: 500;
      font-size: 14px;
      color: #A6A6B3;
      width: 95px;
      height: 38px;
      border: 1px solid #E8E8EF;
      border-radius: 5px;
      text-align: center;
      line-height: 38px;
      display: inline-block;
      vertical-align: top;
      transition: all .2s cubic-bezier(.05,.03,.35,1);
    }
    #discard:hover {
      cursor: pointer;
      opacity: .7;
    }
    #publish {
      font-family: Roboto;
      font-weight: 500;
      font-size: 14px;
      color: #FFF;
      background-color: #217CE8;
      border-radius: 5px;
      width: 143px;
      height: 38px;
      margin-left: 10px;
      display: inline-block;
      vertical-align: top;
      text-align: center;
      line-height: 38px;
      margin-right: 20px;
      transition: all .2s cubic-bezier(.05,.03,.35,1);
    }
    #publish:hover {
      cursor: pointer;
      opacity: .7;
    }
    #buttonsright {
      float: right;
      margin-top: 15px;
    }
    #leftcard {
      width: 363px;
      background-color: #FFF;
      border: 1px solid #E8E8EF;
      box-sizing: border-box;
      padding-top: 85px;
      padding-left: 20px;
      height: 100%;
      position: absolute;
      z-index: 2;
    }
    #search input {
      width: 318px;
      height: 40px;
      background-color: #FFF;
      border: 1px solid #E8E8EF;
      box-sizing: border-box;
      box-shadow: 0px 2px 8px rgba(34,34,87,0.05);
      border-radius: 5px;
      text-indent: 35px;
      font-family: Roboto;
      font-size: 16px;
    }
    ::-webkit-input-placeholder { /* Edge */
      color: #C9C9D5;
    }

    :-ms-input-placeholder { /* Internet Explorer 10-11 */
      color: #C9C9D5
    }

    ::placeholder {
      color: #C9C9D5;
    }
    #search img {
      position: absolute;
      margin-top: 10px;
      width: 18px;
      margin-left: 12px;
    }
    #header {
      font-size: 20px;
      font-family: Roboto;
      font-weight: bold;
      color: #393C44;
    }
    #subnav {
      border-bottom: 1px solid #E8E8EF;
      width: calc(100% + 20px);
      margin-left: -20px;
      margin-top: 10px;
    }
    .navdisabled {
      transition: all .3s cubic-bezier(.05,.03,.35,1);
    }
    .navdisabled:hover {
      cursor: pointer;
      opacity: .5;
    }
    .navactive {
      color: #393C44!important;
    }
    #triggers {
      margin-left: 20px;
      font-family: Roboto;
      font-weight: 500;
      font-size: 14px;
      text-align: center;
      color: #808292;
      width: calc(88% / 3);
      height: 48px;
      line-height: 48px;
      display: inline-block;
      float: left;
    }
    .navactive:after {
      display: block;
      content: "";
      width:  100%;
      height: 4px;
      background-color: #217CE8;
      margin-top: -4px;
    }
    #actions {
      display: inline-block;
      font-family: Roboto;
      font-weight: 500;
      color: #808292;
      font-size: 14px;
      height: 48px;
      line-height: 48px;
      width: calc(88% / 3);
      text-align: center;
      float: left;
    }
    #loggers {
      width: calc(88% / 3);
      display: inline-block;
      font-family: Roboto;
      font-weight: 500;
      color: #808292;
      font-size: 14px;
      height: 48px;
      line-height: 48px;
      text-align: center;
    }
    #footer {
      position: absolute;
      left: 0;
      padding-left: 20px;
      line-height: 40px;
      bottom: 0;
      width: 362;
      border: 1px solid #E8E8EF;
      height: 67px;
      box-sizing: border-box;
      background-color: #FFF;
      font-family: Roboto;
      font-size: 14px;
    }
    #footer a {
      text-decoration: none;
      color: #393C44;
      transition: all .2s cubic-bezier(.05,.03,.35,1);
    }
    #footer a:hover {
      opacity: .5;
    }
    #footer span {
      color: #808292;
    }
    #footer p {
      display: inline-block;
      color: #808292;
    }
    #footer img {
      margin-left: 5px;
      margin-right: 5px;
    }
    .blockelem:first-child {
      margin-top: 20px
    }
    .blockelem {
      padding-top: 10px;
      width: 318px;
      border: 1px solid transparent;
      transition-property: box-shadow, height;
      transition-duration: .2s;
      transition-timing-function: cubic-bezier(.05,.03,.35,1);
      border-radius: 5px;
      box-shadow: 0px 0px 30px rgba(22, 33, 74, 0);
      box-sizing: border-box;
    }
    .blockelem:hover {
      box-shadow: 0px 4px 30px rgba(22, 33, 74, 0.08);
      border-radius: 5px;
      background-color: #FFF;
      cursor: pointer;
    }
    .grabme, .blockico {
      display: inline-block;
    }
    .grabme {
      margin-top: 10px;
      margin-left: 10px;
      margin-bottom: -14px;
      width: 15px;
    }
    #blocklist {
      height: calc(100% - 220px);
      overflow-y: auto;
    }
    #proplist {
      height: calc(100% - 305px);
      overflow: auto;
      margin-top: -30px;
      padding-top: 30px;
    }
    .blockin {
      display: inline-block;
      vertical-align: top;
      margin-left: 12px;
    }
    .blockico {
      width: 36px;
      height: 36px;
      background-color: #F1F4FC;
      border-radius: 5px;
      text-align: center;
      white-space: nowrap;
    }
    .blockico span {
      height: 100%;
      width: 0px;
      display: inline-block;
      vertical-align: middle;
    }
    .blockico img {
      vertical-align: middle;
      margin-left: auto;
      margin-right: auto;
      display: inline-block;
    }
    .blocktext {
      display: inline-block;
      width: 220px;
      vertical-align: top;
      margin-left: 12px
    }
    .blocktitle {
      margin: 0px!important;
      padding: 0px!important;
      font-family: Roboto;
      font-weight: 500;
      font-size: 16px;
      color: #393C44;
    }
    .blockdesc {
      margin-top: 5px;
      font-family: Roboto;
      color: #808292;
      font-size: 14px;
      line-height: 21px;
    }
    .blockdisabled {
      background-color: #F0F2F9;
      opacity: .5;
    }
    #closecard {
      position: absolute;
      margin-left: 340px;
      background-color: #FFF;
      border-radius: 0px 5px 5px 0px;
      border-bottom: 1px solid #E8E8EF;
      border-right: 1px solid #E8E8EF;
      border-top: 1px solid #E8E8EF;
      width: 53px;
      height: 53px;
      text-align: center;
      z-index: 10;
    }
    #closecard img {
      margin-top: 15px
    }
    #canvas {
      position: absolute;
      width: calc(100% - 361px);
      height: calc(100% - 71px);
      top: 71px;
      left: 361px;
      z-index: 0;
      overflow: auto;
    }
    #propwrap {
      position: absolute;
      right: 0;
      top: 0;
      width: 311px;
      height: 100%;
      padding-left: 20px;
      overflow: hidden;
      z-index: -2;
    }
    #properties {
      position: absolute;
      height: 100%;
      width: 311px;
      background-color: #FFF;
      right: -150;
      opacity: 0;
      z-index: 2;
      top: 0;
      box-shadow: -4px 0px 40px rgba(26, 26, 73, 0);
      padding-left: 20px;
      transition: all .25s cubic-bezier(.05,.03,.35,1);
    }
    .itson {
      z-index: 2!important;
    }
    .expanded {
      right: 0!important;
      opacity: 1!important;
      box-shadow: -4px 0px 40px rgba(26, 26, 73, 0.05);
      z-index: 2;
    }
    #header2 {
      font-size: 20px;
      font-family: Roboto;
      font-weight: bold;
      color: #393C44;
      margin-top: 101px;
    }
    #close {
      margin-top: 100px;
      position: absolute;
      right: 20px;
      z-index: 9999;
      transition: all .25s cubic-bezier(.05,.03,.35,1);
    }
    #close:hover {
      cursor: pointer;
      opacity: .7;
    }
    #propswitch {
      border-bottom: 1px solid #E8E8EF;
      width: 331px;
      margin-top: 10px;
      margin-left: -20px;
      margin-bottom: 30px;
    }
    #dataprop {
      font-family: Roboto;
      font-weight: 500;
      font-size: 14px;
      text-align: center;
      color: #393C44;
      width: calc(88% / 3);
      height: 48px;
      line-height: 48px;
      display: inline-block;
      float: left;
      margin-left: 20px;
    }
    #dataprop:after {
      display: block;
      content: "";
      width: 100%;
      height: 4px;
      background-color: #217CE8;
      margin-top: -4px;
    }
    #alertprop {
      display: inline-block;
      font-family: Roboto;
      font-weight: 500;
      color: #808292;
      font-size: 14px;
      height: 48px;
      line-height: 48px;
      width: calc(88% / 3);
      text-align: center;
      float: left;
    }
    #logsprop {
      width: calc(88% / 3);
      display: inline-block;
      font-family: Roboto;
      font-weight: 500;
      color: #808292;
      font-size: 14px;
      height: 48px;
      line-height: 48px;
      text-align: center;
    }
    .inputlabel {
      font-family: Roboto;
      font-size: 14px;
      color: #253134;
    }
    .dropme {
      background-color: #FFF;
      border-radius: 5px;
      border: 1px solid #E8E8EF;
      box-shadow: 0px 2px 8px rgba(34, 34, 87, 0.05);
      font-family: Roboto;
      font-size: 14px;
      color: #253134;
      text-indent: 20px;
      height: 40px;
      line-height: 40px;
      width: 287px;
      margin-bottom: 25px;
    }
    .dropme img {
      margin-top: 17px;
      float: right;
      margin-right: 15px;
    }
    .checkus {
      margin-bottom: 10px;
    }
    .checkus img {
      display: inline-block;
      vertical-align: middle;
    }
    .checkus p {
      display: inline-block;
      font-family: Roboto;
      font-size: 14px;
      vertical-align: middle;
      margin-left: 10px;
    }
    #divisionthing {
      height: 1px;
      width: 100%;
      background-color: #E8E8EF;
      position: absolute;
      right: 0;
      bottom: 80;
    }
    #removeblock {
      border-radius: 5px;
      position: absolute;
      bottom: 20;
      font-family: Roboto;
      font-size: 14px;
      text-align: center;
      width: 287px;
      height: 38px;
      line-height: 38px;
      color: #253134;
      border: 1px solid #E8E8EF;
      transition: all .3s cubic-bezier(.05,.03,.35,1);
    }
    #removeblock:hover {
      cursor: pointer;
      opacity: .5;
    }
    .noselect {
      -webkit-touch-callout: none; /* iOS Safari */
      -webkit-user-select: none; /* Safari */
      -khtml-user-select: none; /* Konqueror HTML */
      -moz-user-select: none; /* Old versions of Firefox */
      -ms-user-select: none; /* Internet Explorer/Edge */
      user-select: none; /* Non-prefixed version, currently
                                  supported by Chrome, Opera and Firefox */
    }
    .blockyname {
      font-family: Roboto;
      font-weight: 500;
      color: #253134;
      display: inline-block;
      vertical-align: middle;
      margin-left: 8px;
      font-size: 16px;
    }
    .blockyleft img {
      display: inline-block;
      vertical-align: middle;
    }
    .blockyright {
      display: inline-block;
      float: right;
      vertical-align: middle;
      margin-right: 20px;
      margin-top: 10px;
      width: 28px;
      height: 28px;
      border-radius: 5px;
      text-align: center;
      background-color: #FFF;
      transition: all .3s cubic-bezier(.05,.03,.35,1);
      z-index: 10;
    }
    .blockyright:hover {
      background-color: #F1F4FC;
      cursor: pointer;
    }
    .blockyright img {
      margin-top: 12px;
    }
    .blockyleft {
      display: inline-block;
      margin-left: 20px;
    }
    .blockydiv {
      width: 100%;
      height: 1px;
      background-color: #E9E9EF;
    }
    .blockyinfo {
      font-family: Roboto;
      font-size: 14px;
      color: #808292;
      margin-top: 15px;
      text-indent: 20px;
      margin-bottom: 20px;
    }
    .blockyinfo span {
      color: #253134;
      font-weight: 500;
      display: inline-block;
      border-bottom: 1px solid #D3DCEA;
      line-height: 20px;
      text-indent: 0px;
    }
    .block {
      background-color: #FFF;
      margin-top: 0px!important;
      box-shadow: 0px 4px 30px rgba(22, 33, 74, 0.05);
    }
    .selectedblock {
      border: 2px solid #217CE8;
      box-shadow: 0px 4px 30px rgba(22, 33, 74, 0.08);
    }

    @media only screen and (max-width: 832px) {
      #centerswitch {
        display: none;
      }
    }
    @media only screen and (max-width: 560px) {
      #names {
        display: none;
      }
    }
    .dragging{z-index:111!important}.block{position:absolute;z-index:9}.indicator{width:12px;height:12px;border-radius:60px;background-color:#217ce8;margin-top:-5px;opacity:1;transition:all .3s cubic-bezier(.05,.03,.35,1);transform:scale(1);position:absolute;z-index:2}.invisible{opacity:0!important;transform:scale(0)}.indicator:after{content:"";display:block;width:12px;height:12px;background-color:#217ce8;transform:scale(1.7);opacity:.2;border-radius:60px}.arrowblock{position:absolute;width:100%;overflow:visible;pointer-events:none}.arrowblock svg{width: -webkit-fill-available;overflow: visible;}
  </style>
</head>
<body>
  <div id="app"></div>

  {{-- Global configuration object --}}
  <script>
    window.config = @json($config);
  </script>

  {{-- Load the application scripts --}}
  <script src="{{ mix('dist/js/app.js') }}"></script>
</body>
</html>
