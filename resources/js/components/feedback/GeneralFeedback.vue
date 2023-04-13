<template>
  <div class="pb-3">
    <div v-if="[2,4,5].includes(user.role) && feedback && (feedback.correct || feedback.incorrect || feedback.any)">
      <hr>
      <b-card border-variant="info"
              :header="user.role === 3 ? 'Feedback' : 'General Feedback'"
              header-bg-variant="info"
              header-text-variant="white"
              header-class="pt-2 pb-2 pl-3"
      >
        <ul style="list-style:none;" class="p-0">
          <li v-if="feedback.any">
            <span class="font-weight-bold">Any Response</span> <span v-html="feedback.any"/>
          </li>
          <li v-if="feedback.correct">
            <span class="font-weight-bold">Correct Response</span> <span v-html="feedback.correct"/>
          </li>
          <li v-if="feedback.incorrect">
            <span class="font-weight-bold">Incorrect Response</span> <span v-html="feedback.incorrect"/>
          </li>
        </ul>
      </b-card>
    </div>
    <div v-if="user.role === 3 && feedback && (feedback[feedbackType] || feedback.specific || feedback.any)">
      <hr>
      <b-card border-variant="info"
              header="Feedback"
              header-bg-variant="info"
              header-text-variant="white"
              header-class="pt-2 pb-2 pl-3"
      >
        <div v-if="feedback.specific" v-html="feedback.specific"/>
        <div v-if="feedback.any" v-html="feedback.any"/>
        <div v-html="feedback[feedbackType]"/>
      </b-card>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  name: 'GeneralFeedback',
  props: {
    feedback: {
      type: Object,
      default: () => {
      }
    },
    feedbackType: {
      type: String,
      default: ''
    }
  },
  computed: {
    ...mapGetters({
      user: 'auth/user'
    })
  }
}
</script>
