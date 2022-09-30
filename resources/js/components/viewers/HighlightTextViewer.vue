<template>
  <div>
    <span v-html="highlightedText"/>
  </div>
</template>

<script>
import $ from 'jquery'

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

export default {
  name: 'HighlightTextViewer',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    highlightedText: ''
  }),
  mounted () {
    if (this.qtiJson.responses) {
      this.highlightedText = this.qtiJson.prompt
      for (let i = 0; i < this.qtiJson.responses.length; i++) {
        let response = this.qtiJson.responses[i]
        let highlightedItem = `[${response.text}]`
        let highlightedCss = 'background-color: #FEFDC9;padding:5px'
        let highlightedClass = 'response'
        if (response.selected) {
          highlightedCss += ';border-color:black;border-width:1px;border-style:solid'
          highlightedClass += ' selected'
        }
        this.highlightedText = this.highlightedText.replace(highlightedItem, `<span id="${response.identifier}" style="${highlightedCss}" class="${highlightedClass}">${response.text}</span>`)
      }
    }
    this.$forceUpdate()
    $(document).ready(function () {
      $('.response').on('click', function () {
        if ($(this).hasClass('selected')) {
          $(this).removeClass('selected')
          $(this).css(notSelectedCss)
        } else {
          $(this).addClass('selected')
          $(this).css(selectedCss)
        }
      })
    })
  }
}
</script>

