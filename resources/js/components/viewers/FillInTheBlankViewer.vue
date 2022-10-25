<template>
  <div class="pb-2">
    <form class="form-inline">
      <div v-for="(item,index) in fillInTheBlankArray" :key="`fill-in-the-blank-${index}`">
        <span v-if="index %2 === 0" v-html="removeUnderline(item)"/>
        <span v-if="index %2 !== 0" class="p-1">
          <input type="text" :class="`response_${index} fill-in-the-blank form-control form-control-sm`"
                 :value="qtiJson.studentResponse ? qtiJson.studentResponse[Math.round(index/2)-1].value : ''"
          >
          <span v-if="qtiJson.studentResponse
          && qtiJson.studentResponse[Math.round(index / 2) - 1].hasOwnProperty('answeredCorrectly')
          && showResponseFeedback"
          >
            <b-icon-check-circle-fill v-if="isCorrect(Math.round(index / 2) - 1)"
                                      class="text-success"
            />
            <b-icon-x-circle-fill v-if="!isCorrect(Math.round(index / 2) - 1)"
                                  class="text-danger"
            /></span>
        </span>
      </div>
    </form>
  </div>
</template>

<script>
import $ from 'jquery'

export default {
  name: 'FillInTheBlankViewer',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    },
    showResponseFeedback: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
    fillInTheBlankArray: []
  }),
  computed: {
    uTags () {
      if (this.qtiJson.itemBody.textEntryInteraction) {
        const regex = /(<u>.*?<\/u>)/
        let matches = String(this.qtiJson.itemBody.textEntryInteraction).split(regex).filter(Boolean)
        let uTags = []
        if (matches && matches.length) {
          for (let i = 0; i < matches.length; i++) {
            let match = matches[i]
            if (match.includes('<u>') && match.includes('</u>')) {
              uTags.push(match.replace('<u>', '').replace('</u>', ''))
            }
          }
        }
        if (!uTags.length) {
          uTags = null
        }
        console.log(uTags)
        return uTags
      } else {
        return []
      }
    }
  },
  mounted () {
    const reg = /(<u>.*?<\/u>)/
    this.fillInTheBlankArray = this.qtiJson.itemBody.textEntryInteraction.split(reg)
    if (this.qtiJson.studentResponse) {
      let studentResponse = this.qtiJson.studentResponse
      for (let i = 0; i < studentResponse.length; i++) {
        $('#question').find(`.response_${i + 1}`).val(studentResponse[i].value)
      }
    }
    if (this.showResponseFeedback && this.qtiJson.responseDeclaration.correctResponse) {
      for (let i = 0; i < this.qtiJson.responseDeclaration.correctResponse.length; i++) {
        let correctResponse = this.qtiJson.responseDeclaration.correctResponse[i]
        $('#answer').find(`.response_${i + 1}`).val(correctResponse.value)
      }
    }
    $(document).on('keydown', 'input.fill-in-the-blank', function () {
      $(this).removeClass('is-invalid-border')
    })
  },
  methods: {
    isCorrect (index) {
      return this.qtiJson.studentResponse[index].answeredCorrectly
    },
    removeUnderline (item) {
      return item.replace('<u>', '').replace('</u>', '').replace('<p>', '').replace('</p>', '')
    },
    getFillInTheBlankResponseDeclarations () {
      let responseDeclarations = []
      console.log(this.uTags)
      if (this.uTags) {
        for (let i = 0; i < this.uTags.length; i++) {
          let uTag = this.uTags[i]
          console.log(uTag)
          let responseDeclaration = {
            'value': uTag,
            'matchingType': this.textEntryInteractions[i].matchingType,
            'caseSensitive': this.textEntryInteractions[i].caseSensitive
          }
          responseDeclarations.push(responseDeclaration)
        }
      }
      return responseDeclarations
    }
  }
}
</script>
