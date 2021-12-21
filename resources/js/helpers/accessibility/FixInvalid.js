export function fixInvalid () {
  let isInvalids = document.getElementsByClassName('is-invalid')
  console.log(isInvalids)
  for (let i = 0; i < isInvalids.length; i++) {
    isInvalids[i].setAttribute('aria-invalid', 'true')
  }
  let ariaInvalids = document.querySelectorAll('[aria-invalid="true"]')
  for (let i = 0; i < ariaInvalids.length; i++) {
    let ariaInvalid = ariaInvalids[i]
    let labelledById = ariaInvalid.getAttribute('id') + '-error'
    ariaInvalid.setAttribute('aria-labelledby', labelledById)
    try {
      let helpBlock = ariaInvalid.nextSibling.nextSibling
      if (helpBlock && helpBlock.classList.length && helpBlock.classList.contains('invalid-feedback')) {
        helpBlock.setAttribute('id', labelledById)
      }
    } catch (error) {
      console.log('Fix invalid error')
      console.log(ariaInvalid)
    }
  }
}
