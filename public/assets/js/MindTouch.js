function updateInAdapt() {
  let iframe = document.createElement("iframe");
  let uniqueString = "MyUNiQueId";
  document.body.appendChild(iframe);
  iframe.style.display = "none";
  iframe.contentWindow.name = uniqueString;

// construct a form with hidden inputs, targeting the iframe
  let form = document.createElement("form");
  form.target = uniqueString;
  form.action = "https://dev.adapt.libretexts.org/api/mind-touch-events/update";
  form.method = "POST";

// repeat for each parameter
  let input1 = document.createElement("input");
  input1.type = "hidden";
  input1.name = "page_id";

  let data = JSON.parse($('#mt-global-settings').html());
  input1.value = data.pageId;
  form.appendChild(input1);
  document.body.appendChild(form);
  form.submit();
}

$(document).ready(function () {
  updateInAdapt();///for a name change
});
window.tagObserverSet = false;
//More Details https://developer.mozilla.org/en-US/docs/Web/API/MutationObserver
$(document).on('click', '#mt-summary', function () {
  try {
    if (!window.tagObserverSet) {
      let target = document.querySelector('#live-tag-entries')
      let observer = new MutationObserver(function (mutations) {
        console.log('tags changed');
        updateInAdapt();
      });

      let config = {attributes: true, childList: true, characterData: true};
      observer.observe(target, config);
      window.tagObserverSet = true;
      console.log('created tag observer');
    }
  } catch (e) {

    console.error(e.message);

  }
});

