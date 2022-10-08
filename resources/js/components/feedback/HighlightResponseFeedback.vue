<template>
  <div>
    <span v-for="(item,index) in highlightedTextArr" :key="`html-${index}`">
      <span v-html="item"/>
      <span v-if="getId(item) &&showResponseFeedback">
        <b-icon-check-circle-fill v-if="isCorrect(getId(item)) === 'correct'"
                                  class="text-success"
        />
        <b-icon-x-circle-fill v-if="isCorrect(getId(item)) === 'not correct'"
                              class="text-danger"
        />
      </span>
    </span>
  </div>
</template>

<script>
export default {
  name: 'HighlightResponseFeedback',
  props: {
    highlightedText: {
      type: String,
      default: ''
    },
    responses: {
      type: Array,
      default: () => {
      }
    },
    showResponseFeedback: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
    highlightedTextArr: []
  }),
  mounted () {
    this.highlightedTextArr = []
    let result
    const regex = /<span(.|\n)*?<\/span>/
    let highlightedText = this.highlightedText
    let count = (this.highlightedText.match(/<span /g) || []).length
    for (let i = 0; i < count + 2; i++) {
      result = regex.exec(highlightedText)
      try {
        this.highlightedTextArr.push(highlightedText.substring(0, result.index).replace('<p>', '').replace('</p>', ''))
        highlightedText = highlightedText.substring(result.index)
        this.highlightedTextArr.push(highlightedText.substring(0, result[0].length))
        highlightedText = highlightedText.substring(result[0].length)
      } catch (e) {

      }
    }
    if (highlightedText) {
      // this.highlightedText at the end
      this.highlightedTextArr.push(highlightedText)
    }
    this.$forceUpdate()
  },
  methods: {
    isCorrect (identifier) {
      let response = this.responses.find(item => item.identifier === identifier)
      if (response.correctResponse && response.selected) {
        return 'correct'
      }
      if (response.correctResponse && response.selected === false) {
        return 'not correct'
      }
      if (!response.correctResponse && response.selected) {
        return 'not correct'
      }
      if (!response.correctResponse && response.selected === false) {
        return 'correct'
      }
      return null
    },
    getId (item) {
      //console.log(item)
      const regex = /id=".*?"/gm
      let idWithExtras = regex.exec(item)
      let id = null
      if (idWithExtras) {
        id = idWithExtras[0].substring(4)
        id = id.substring(0, id.length - 1)
      }
      return id
    }
  }
}
</script>

<style scoped>

</style>
