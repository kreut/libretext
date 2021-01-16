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
        <PageTitle title="Questions" />
        <div v-if="items.length">
          <b-table
            striped
            hover
            :no-border-collapse="true"
            :fields="fields"
            :items="items"
          >
            <template v-slot:cell(question_number)="data">
              <div class="mb-0">
                <a href="" @click.stop.prevent="viewQuestion(data.item.question_id)">{{ data.item.question_number }}</a>
              </div>
            </template>
          </b-table>
        </div>
        <div v-else>
          <b-alert variant="warning" show>
            <span class="font-weight-bold">This assignment doesn't have any questions.</span>
            <strong />
          </b-alert>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'

export default {
  middleware: 'auth',
  components: {
    Loading
  },
  data: () => ({

    fields: [
      {
        key: 'question_number',
        label: 'Question'
      },
      {
        key: 'open_ended_submission_type',
        label: 'Open Ended Submission Type'
      },
      'points',
      'solution'
    ],
    items: [],
    isLoading: true
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$noty.error('You do not have access to the assignment questions page.')
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentInfo()
  },
  methods: {
    viewQuestion (questionId) {
      this.$router.push({ path: `/assignments/${this.assignmentId}/questions/view/${questionId}` })
      return false
    },
    async getAssignmentInfo () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/summary`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.items = data.rows
        console.log(data)
      } catch (error) {

      }
      this.isLoading = false
    }
  }
}
</script>
