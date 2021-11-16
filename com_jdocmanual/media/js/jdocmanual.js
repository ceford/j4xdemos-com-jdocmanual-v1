
//===== Cookie handlers ===================================

function setCookie(name, value, days) {
	var expires = "";
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		expires = "; expires="+date.toGMTString();
	}
	// is the siteroot set for this template
	var paths = Joomla.getOptions(["system.paths"], 'No good');
	var root = paths.root;
	if (typeof root === undefined) {
		path = "; path=/";
	} else {
		path = "; path=" + root;
	}
	let samesite = "; samesite=None; secure=true"
	let baseFull = paths.baseFull; // "http:\/\/localhost\/j4ops\/"
	document.cookie = name + "=" + value + expires + path + samesite;
}

//=========================================================

function getCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	var c;
	var i;
	for(i=0; i < ca.length; i += 1) {
		c = ca[i];
		while (c.charAt(0) === ' ') { c = c.substring(1,c.length); }
		if (c.indexOf(nameEQ) === 0) { return c.substring(nameEQ.length,c.length); }
	}
	return null;
}

//=========================================================

function eraseCookie(name) {
	setCookie(name,"",-1);
}

let languages = document.getElementsByClassName("set-language");

let setLanguage = function() {
	let language_code = this.innerText;
	let task = document.getElementById('task');
	if (this.classList.contains('index')) {
		task.value = 'content.selectindexlanguage';
	} else {
		task.value = 'content.selectlanguage';
	}
	let jform_language = document.getElementById('jform_language');
	jform_language.value = language_code;
	let form = document.getElementById('adminForm');
	form.submit();
}

for (var i = 0; i < languages.length; i++) {
	languages[i].addEventListener('click', setLanguage, false);
}

let toggle = document.getElementById('toggle-joomla-menu');
if(toggle) {
	toggle.addEventListener('click', function() {
		let wrapper = document.getElementById('sidebar-wrapper');
		style = getComputedStyle(wrapper);
		if (style.display == 'none') {
			wrapper.classList.remove('d-none');
		} else {
			wrapper.classList.add('d-none');
		}
	})
}

let contents = document.getElementsByClassName("content-link");
let anchors = null;

let getPage = function() {
	let link_id = this.getAttribute('data-content-id');
	setPanelContent(link_id, this.innerText);
}

for (var i = 0; i < contents.length; i++) {
	contents[i].addEventListener('click', getPage, false);
}

async function setPanelContent(itemId, title) {
	let document_title = document.getElementById('document-title');
	if (!document_title) {
		return;
	}
	let document_panel = document.getElementById('document-panel');
	let main_panel = document.getElementById('jdocmanual-main');
	let jdocmanual_original = document.getElementById('jdocmanual-original');

	const d = new Date();
	setCookie('jdocmanualItemId', itemId, 10);
	setCookie('jdocmanualTitle', title, 10)
	const token = Joomla.getOptions('csrf.token', '');
	let url = 'index.php?option=com_jdocmanual&task=content.fillpanel';
	let data = new URLSearchParams();
	data.append(`itemId`, itemId);
	data.append(token, 1);
	const options = {
		method: 'POST',
		body: data
	}
	let response = await fetch(url, options);
	if (!response.ok) {
		document_panel.innerHTML = response.status;
		throw new Error (Joomla.Text._('COM_MYCOMPONENT_JS_ERROR_STATUS') + `${response.status}`);
	} else {
		let result = await response.text();

		document_title.innerText = title;
		document_panel.innerHTML = result;
		// jdocmanual_active_url is in the page at load time
		jdocmanual_original.href = jdocmanual_active_url + itemId;
		jdocmanual_original.classList.remove('d-none');

		if(document.querySelectorAll(("#scroll-panel h2, #scroll-panel h3")).length > 0) {
			let html = '<div class="h3 mt-3">' + Joomla.Text._('COM_JDOCMANUAL_JDOCMANUAL_TOC_IN_THIS_PAGE') + '</div><ul>';
			document.querySelectorAll("#scroll-panel h2, #scroll-panel h3").forEach(function(element) {
				html += '<li class="toc-link toc-link-' + element.localName + '">' + element.textContent + '</li>';
			});
			html += '</ul>';
			document.querySelector("#toc-panel").innerHTML = html;
			/* toc */
			document.querySelectorAll(".toc-link").forEach(function(element, index) {
				element.setAttribute('data-index', index);
				element.addEventListener('click', function() {
					document.querySelectorAll("#scroll-panel h2, #scroll-panel h3")[this.getAttribute('data-index')].scrollIntoView({ 
						behavior: 'smooth', block: 'start' },
					);
					window.scrollTo(0, 0);
				});
			});
		}
	}
}

document.addEventListener('DOMContentLoaded', function(event) {
	// has a jdocmanualReset cookie been set
	if (getCookie('jdocmanualReset')) {
		eraseCookie('jdocmanualItemId');
		eraseCookie('jdocmanualTitle');
		eraseCookie('jdocmanualLastHeading');
	} else {
		// if cookies exist - jdocmanualItemId and jdocmanualTitle
		if (getCookie('jdocmanualItemId')) {
			let itemId = getCookie('jdocmanualItemId');
			let title = getCookie('jdocmanualTitle');
			setPanelContent(itemId, title);
		}
	}

	let collapses = document.getElementsByClassName("accordion-collapse");
	if (collapses) {
		for (var i = 0; i < collapses.length; i++) {
			collapses[i].addEventListener('show.bs.collapse', function(e){
				setCookie('jdocmanualLastHeading', this.id, 10)
			}, false);
		}
	}

	let collapse = document.getElementById('collapse_1');
	let lastHeading = getCookie('jdocmanualLastHeading');
	if (lastHeading) {
		collapse = document.getElementById(lastHeading);
	}
	collapse.classList.add('show');
});
