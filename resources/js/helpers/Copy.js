export function doCopy (copyId, message = 'Successfully copied!') {
  try {
    let copyText = document.getElementById(copyId)
    let elem = document.createElement('input')
    document.body.appendChild(elem)
    elem.value = copyText.innerText
    elem.select()
    elem.focus()
    document.execCommand('copy', false)
    elem.remove()
    this.$noty.success(message)
  } catch (error) {
    this.$noty.error(`We were not able to copy the text to your clipboard: ${error.message}`)
  }
}
