export async function fixDatePicker (id, ariaLabel) {
  let element = document.getElementById(id)
  if (element) {
    element.style.opacity = '0'
    element.style.width = '0'
    element.style.padding = '5px'
    element.ariaLabel = ariaLabel
  }
}
