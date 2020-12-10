// was also doing on page load because I was storing the location as well.  But if I don't need that, this will bust the cache on update
function updateInAdapt () {
  let iframe = document.createElement('iframe')
  let uniqueString = 'MyUNiQueId'
  document.body.appendChild(iframe)
  iframe.style.display = 'none'
  iframe.contentWindow.name = uniqueString

  // construct a form with hidden inputs, targeting the iframe
  let form = document.createElement('form')
  form.target = uniqueString
  form.action = 'https://dev.adapt.libretexts.org/api/mind-touch-events/update'
  form.method = 'POST'

  // repeat for each parameter
  let input1 = document.createElement('input')
  input1.type = 'hidden'
  input1.name = 'page_id'

  let input2 = document.createElement('input')
  input2.type = 'hidden'
  input2.name = 'action'

  let data = JSON.parse($('#mt-global-settings').html())
  input1.value = data.pageId
  input2.value = 'saved'
  form.appendChild(input1)
  form.appendChild(input2)
  document.body.appendChild(form)
  form.submit()
}

$(document).on('click', '#cke_18', function () {
  console.log('Sent to Adapt')
  updateInAdapt()
})

window.tagObserverSet = false
// More Details https://developer.mozilla.org/en-US/docs/Web/API/MutationObserver
$(document).on('click', '#mt-summary', function () {
  try {
    if (!window.tagObserverSet) {
      let target = document.querySelector('#live-tag-entries')
      let observer = new MutationObserver(function (mutations) {
        console.log('tags changed')
        updateInAdapt()
      })

      let config = { attributes: true, childList: true, characterData: true }
      observer.observe(target, config)
      window.tagObserverSet = true
      console.log('created tag observer')
    }
  } catch (e) {
    console.error(e.message)
  }
})
