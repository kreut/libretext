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


  let input2 = document.createElement("input");
  input2.type = "hidden";
  input2.name = "location";
  input2.value = $('#mt-summary').attr('data-page-uri');

    let input3 = document.createElement("input");
    input3.type = "hidden";
    input3.name = "tags";
    let tags = {};
    if ($('.live-tag-entry').length) {
      $('.live-tag-entry').each(function (i) {
        tags[i] = $(this).find('a').html()
      })
    }
    input3.value = JSON.stringify(tags)
    console.log(tags);

  form.appendChild(input1);
  form.appendChild(input2);

  form.appendChild(input3);


  document.body.appendChild(form);
  form.submit();


}

$(document).ready(function(){
  updateInAdapt();///for a name change
});
window.tagObserverSet = false;
//More Details https://developer.mozilla.org/en-US/docs/Web/API/MutationObserver
$(document).on('click', '#mt-summary', function() {
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
});

//handle changes in the editor
//Don't actually need this but I'm going to save it just in case.
$(document).on('click', '#page-options-menu-edit', function () {
  CKEDITOR.on('instanceReady', function () {
    console.log('ready');
    let save = $('a[class*="mindtouchsave"]').first();
    let onclick = save.attr('onclick');
    save.attr('onclick', 'updateInAdapt();' + onclick);
  });
});
