<template>
  <div>
    <div v-show="['audio','video'].includes(commentType)" class="mb-2">
      <div v-if="showSatisfiesRequirementTimer">
        <div v-if="!requirementSatisfied">
          <countdown ref="satisfied-requirement-countdown" :time="millisecondsTimeUntilRequirementSatisfied"
                     @end="setRequirementSatisfied"
          >
            <template v-slot="props">
              <span class="text-danger" v-html="getTimeLeftMessage(props)"/>
            </template>
          </countdown>
        </div>
        <div v-if="requirementSatisfied" class="text-success">
          This {{ commentType }} comment is long enough to be submitted.
        </div>
      </div>
      <div v-if="!showSatisfiesRequirementTimer">
        <span class="text-danger"
        >To be accepted, your recording should be at least {{ humanReadableTimeUntilRequirementSatisfied }}.</span>
      </div>
    </div>
    <div v-show="commentType === 'text'">
      <div v-if="minNumberOfWords > 0" class="mb-2">
        <span v-if="minNumberOfWords > countWords(commentFormText)" class="text-danger"
        >{{ Math.max(minNumberOfWords - countWords(commentFormText), 0) }} word<span
          v-if="minNumberOfWords - countWords(commentFormText) !== 1"
        >s</span> needed to satisfy the comment length requirement</span>
        <span v-if="minNumberOfWords <= countWords(commentFormText)" class="text-success">This comment fulfills the comment length requirement.</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DiscussItSatisfiesRequirement',
  props: {
    showSatisfiesRequirementTimer: {
      type: Boolean,
      default: false
    },
    millisecondsTimeUntilRequirementSatisfied: {
      type: Number,
      default: 0
    },
    humanReadableTimeUntilRequirementSatisfied: {
      type: String,
      default: ''
    },
    commentType: {
      type: String,
      default: ''
    },
    minNumberOfWords: {
      type: Number,
      default: 0
    },
    commentFormText: {
      type: String,
      default: ''
    }
  },
  data: () => ({
    timeLeft: 0,
    requirementSatisfied: false
  }),
  mounted () {
    this.timeLeft = this.millisecondsTimeUntilRequirementSatisfied
  },
  methods: {
    setReqiurementSatisfied() {
      this.requirementSatisfied = true
      this.$emit('setRequirementSatisfied')
    },
    endCountDown () {
      if (this.$refs['satisfied-requirement-countdown']) {
        this.$refs['satisfied-requirement-countdown'].abort()
      }
    },
    getTimeLeftMessage (props) {
      let message = ''
      let timeLeft = parseInt(this.timeLeft) / 1000
      if (timeLeft > 60) {
        message += `${props.minutes} minutes, ${props.seconds} seconds`
      } else {
        message = `${props.seconds} seconds`
      }
      message += ` until this ${this.commentType} satisfies the length requirement.`
      return message
    },
    countWords (str) {
      if (!str) {
        return 0
      }
      // Create a temporary element to parse the HTML string
      let tempDiv = document.createElement('div')
      tempDiv.innerHTML = str

      // Count MathJax formulas and images as one word each
      let mathjaxCount = tempDiv.querySelectorAll('.math-tex').length
      let imageCount = tempDiv.querySelectorAll('img').length

      // Remove MathJax formulas and images from the content
      tempDiv.querySelectorAll('.math-tex, img').forEach(node => node.remove())

      // Get the text content and split into words
      let textContent = tempDiv.textContent || tempDiv.innerText || ''
      let words = textContent.trim().split(/\s+/).filter(Boolean)

      // Total word count
      return words.length + mathjaxCount + imageCount
    }
  }
}
</script>

<style scoped>

</style>
