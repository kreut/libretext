<template>
  <div>
    {{ qtiJson }}
    <form class="form-inline">
      <span v-html="addSelectOptions"/>
    </form>
  </div>
</template>

<script>
import $ from 'jquery'

export default {
  name: 'DragAndDropClozeViewer',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    }
  },
  computed: {
    addSelectOptions () {
      if (this.qtiJson.prompt) {
        let reg = /\[(.*?)\]/g
        let selectOptionsArray = this.qtiJson.prompt.split(reg)
        let html = ''
        for (let i = 0; i < selectOptionsArray.length; i++) {
          let part = selectOptionsArray[i]
          if (i % 2 === 0) {
            html += part
          } else {
            html += `<select style="margin:3px"
class="identifier-${part} select-choice custom-select custom-select-sm form-control inline-form-control drop-down-cloze-select"
aria-label="combobox ${Math.ceil(i / 2)} of ${Math.floor(selectOptionsArray.length / 2)}">
<option value="">Please select an option</option>`
            for (let i = 0; i < this.qtiJson.selectOptions.length; i++) {
              let selectOption = this.qtiJson.selectOptions[i]
              html += `<option value="${selectOption.value}" selected="">${selectOption.text}</option>`
            }
            html += '</select>'
          }
        }
        return html
      } else {
        return []
      }
    }
  },
  mounted () {
    let selecteds = this.qtiJson.selecteds
    if (selecteds.length) {
      this.$nextTick(() => {
        $('.drop-down-cloze-select').each(function (index) {
          let selected = selecteds[index]
          $(this).val(selected)
        })
      })
    }
  }
}
</script>
