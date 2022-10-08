import $ from 'jquery'

export function addHighlights (highlightedText, responses) {
  for (let j = 0; j < responses.length; j++) {
    let response = responses[j]
    console.log(response)
    let highlightedItem = `[${response.text}]`
    let highlightedCss = 'background-color: #FEFDC9;padding:5px'
    let highlightedClass = 'response'
    if (response.selected) {
      highlightedCss += ';border-color:black;border-width:1px;border-style:solid'
      highlightedClass += ' selected'
    }
    highlightedText = highlightedText.replace(highlightedItem, `<span id="${response.identifier}" style="${highlightedCss}" class="${highlightedClass}">${response.text}</span>`)
  }
  return highlightedText
}

const notSelectedCss = {
  'border-color': 'none',
  'border-width': '1px',
  'border-style': 'none'
}
const selectedCss = {
  'border-color': 'black',
  'border-width': '1px',
  'border-style': 'solid'
}

export function toggleSelected () {
  $('.response').on('click', function () {
    if ($(this).hasClass('selected')) {
      $(this).removeClass('selected')
      $(this).css(notSelectedCss)
    } else {
      $(this).addClass('selected')
      $(this).css(selectedCss)
    }
  })
}
