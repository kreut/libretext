import $ from 'jquery'

export function addHighlights (highlightedText, responses) {
  for (let j = 0; j < responses.length; j++) {
    let response = responses[j]
    console.log(response)
    let highlightedItem = `[${response.text}]`
    let highlightedCss = 'color:black;cursor: pointer;background-color: #FEFDC9;padding: 2px 5px;line-height: 15px;height: 20px;'
    let highlightedClass = 'response'
    if (response.selected) {
      highlightedCss += 'border-color:black;border-width:2px;border-style:solid'
      highlightedClass += ' selected'
    }
    highlightedText = highlightedText.replace(highlightedItem, `<a onclick="return false" tabindex="0" style="color:black"><span id="${response.identifier}" style="${highlightedCss}" class="${highlightedClass}">${response.text}</span></a>`)
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
  'border-width': '2px',
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
