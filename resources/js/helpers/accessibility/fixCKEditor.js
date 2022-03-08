export async function fixCKEditor (vm) {
  vm.$nextTick(() => {
    let ckeVoiceLabels = document.getElementsByClassName('cke_voice_label')
    for (let i = 0; i < ckeVoiceLabels.length; i++) {
      if (ckeVoiceLabels[i].innerHTML === 'Press ALT 0 for help') {
        ckeVoiceLabels[i].innerHTML = ckeVoiceLabels[i].innerHTML + ' (Use the OPTION key instead ALT on a Mac)'
      }
    }
  })
}

export function updateModalToggleIndex (modalId) {
  // ckeditor fix for input type text --- wasn't able to click
  // https://stackoverflow.com/questions/58482267/ckeditor-i-cant-fill-any-fields-no-focus-on-inputs
  let modal = document.querySelectorAll('*[id="' + modalId + '___BV_modal_content_"]')[0]
  modal.removeAttribute('tabindex')
}
