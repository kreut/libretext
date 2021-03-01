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
                  Order
                </th>
                <th scope="col">
                  Title
                </th>
                <th scope="col" style="width: 150px;">
                  Adapt ID
                  <b-icon id="adapt-id-tooltip"
                          v-b-tooltip.hover
                          class="text-muted"
                          icon="question-circle"
                  />
                  <b-tooltip target="adapt-id-tooltip" triggers="hover">
                    This ID is of the form {Assignment ID}-{Question ID} and is unique at the assignment level.
                  </b-tooltip>
                </th>
                <th scope="col" style="width: 200px;">
                  Open Ended Type
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
                  {{ item.order }}
                </td>
                <td><a href="" @click.stop.prevent="viewQuestion(item.question_id)">{{ item.title }}</a></td>
                <td>
                  {{ item.assignment_id_question_id }}
                  <span class="text-info">
                    <font-awesome-icon :icon="copyIcon" @click="doCopy(item.assignment_id_question_id)" />
                  </span>
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

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'

export default {
  middleware: 'auth',
  components: {
    FontAwesomeIcon,
    Loading,
    draggable
  },
  data: () => ({
    adaptId: 0,
    copyIcon: faCopy,
    currentOrderedQuestions: [],
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
    doCopy (adaptId) {
      this.$copyText(adaptId).then((e) => {
        this.$noty.success('The Adapt ID has been copied to your clipboard.')
      }, function (e) {
        this.$noty.error('We could not copy the Adapt ID to your clipboard.')
      })
    },
    async saveNewOrder () {
      let orderedQuestions = []
      for (let i = 0; i < this.items.length; i++) {
        orderedQuestions.push(this.items[i].question_id)
      }

      let noChange = true
      for (let i = 0; i < this.currentOrderedQuestions.length; i++) {
        if (this.currentOrderedQuestions[i] !== this.items[i]) {
          noChange = false
        }
      }
      if (noChange) {
        return false
      }
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/questions/order`, { ordered_questions: orderedQuestions })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          for (let i = 0; i < this.items.length; i++) {
            this.items[i].order = i + 1
          }
          this.currentOrderedQuestions = this.items
        }
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
        for (let i = 0; i < this.items.length; i++) {
          this.currentOrderedQuestions.push(this.items[i].question_id)
        }
        console.log(data)
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
