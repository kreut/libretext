<template>
  <div>
    <div v-show="['audio','video'].includes(commentType)" class="mb-2">
      <div v-if="showSatisfiesRequirementTimer">
        <div v-if="!requirementSatisfied">
          <countdown ref="satisfied-requirement-countdown" :time="timeUntilRequirementSatisfied"
                     @end="requirementSatisfied = true"
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
        <span class="text-danger">Some message you need to format with minutes and seconds {{ commentType }} satisfies the length requirement.</span>
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
    timeUntilRequirementSatisfied: {
      type: Number,
      default: 0
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
    this.timeLeft = this.timeUntilRequirementSatisfied
  },
  methods: {
    endCountDown () {
      this.$refs['satisfied-requirement-countdown'].abort()
    },
    getTimeLeftMessage (props) {
      let message = ''
      let timeLeft = parseInt(this.timeLeft) / 1000
      if (timeLeft > 60) {
        message += `${props.minutes} minutes, ${props.seconds} seconds`
      } else {
        message += `${props.seconds} seconds`
      }
      message += ` until this ${this.commentType} satisfies the length requirement.`
      return message
    },
    countWords (str) {
      if (!str) {
        return 0
      }
      return str.trim().split(/\s+/).length
    }
  }
}
</script>

<style scoped>

</style>
