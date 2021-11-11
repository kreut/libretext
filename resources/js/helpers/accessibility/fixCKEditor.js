export async function fixCKEditor (vm) {
  vm.$nextTick(() => {
    let ckeVoiceLabels = document.getElementsByClassName('cke_voice_label')
    console.log('sdf')
    for (let i = 0; i < ckeVoiceLabels.length; i++) {
      console.log(ckeVoiceLabels[i].innerHTML)
      if (ckeVoiceLabels[i].innerHTML === 'Press ALT 0 for help') {
        ckeVoiceLabels[i].innerHTML = ckeVoiceLabels[i].innerHTML + ' (Use the OPTION key instead ALT on a Mac)'
      }
    }
  })
}
