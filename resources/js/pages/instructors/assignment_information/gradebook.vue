<template>
  <div>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading">
        <PageTitle title="Assignment Gradebook"/>
        <span class="pr-1">Time Spent   <QuestionCircleTooltip :id="'time-spent-tooltip'"/>
          <b-tooltip target="time-spent-tooltip"
                     delay="250"
                     triggers="hover focus"
          >
            The timer starts when a student visits a question and stops on submission.  If they revisit the question, time will be added to the
            current value.
          </b-tooltip></span>
        <toggle-button
          v-show="items.length"
          class="mt-1"
          :width="84"
          :value="showTimeSpent"
          :sync="true"
          :font-size="14"
          :margin="4"
          :color="toggleColors"
          :labels="{checked: 'Shown', unchecked: 'Hidden'}"
          @change="showTimeSpent = !showTimeSpent;getAssignmentQuestionScoresByUser(showTimeSpent)"
        />
        <b-table
          v-show="items.length"
          aria-label="Assignment Gradebook"
          striped
          hover
          responsive
          sticky-header="800px"
          :no-border-collapse="true"
          :fields="fields"
          :items="items"
        />
        <b-alert :show="!items.length">
          <span class="font-weight-bold">
            This course has no enrolled users so there is no gradebook to show.
          </span>
        </b-alert>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'
import { ToggleButton } from 'vue-js-toggle-button'

export default {
  middleware: 'auth',
  components: {
    Loading,
    ToggleButton
  },
  metaInfo () {
    return { title: 'Assignment Gradebook' }
  },
  data: () => ({
    fields: [],
    items: [],
    isLoading: true,
    showTimeSpent: false,
    toggleColors: window.config.toggleColors
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentQuestionScoresByUser(0)
  },
  methods: {
    async getAssignmentQuestionScoresByUser (showTimeSpent) {
      try {
        const { data } = await axios.get(`/api/scores/assignment/get-assignment-questions-scores-by-user/${this.assignmentId}/${+showTimeSpent}`)
        console.log(data)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.items = data.rows
        this.fields = data.fields
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
