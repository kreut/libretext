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
          <table class="table table-striped">
            <thead>
              <tr>
                <th scope="col">
                  Question Number
                </th>
                <th scope="col">
                  Open Ended Submission Type
                </th>
                <th scope="col">
                  Points
                </th>
                <th scope="col">
                  Solution
                </th>
              </tr>
            </thead>
            <tbody is="draggable" v-model="items" tag="tbody" @end="saveNewOrder">
              <tr v-for="item in items" :key="item.id">
                <td>
                  <b-icon icon="list" />
                  <a href="" @click.stop.prevent="viewQuestion(item.question_id)">{{ item.question_number }}</a>
                </td>
                <td>
                  {{ item.open_ended_submission_type }}
                </td>
                <td>{{ item.points }}</td>
                <td>{{ item.solution }}</td>
              </tr>
            </tbody>
          </table>
        </div>
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
import draggable from 'vuedraggable'

export default {
  middleware: 'auth',
  components: {
    Loading,
    draggable
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
    async saveNewOrder () {
      let orderedQuestions = []
      for (let i = 0; i < this.items.length; i++) {
        orderedQuestions.push(this.items[i].question_id)
      }
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/questions/order`, { ordered_questions: orderedQuestions })
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    },
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
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
