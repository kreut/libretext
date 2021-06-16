<template>
  <div>
    <b-modal
      id="modal-remove-question"
      ref="modal"
      title="Confirm Remove Question"
    >
      <RemoveQuestion/>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-remove-question')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitRemoveQuestion()"
        >
          Yes, remove question!
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-non-h5p"
      ref="h5pModal"
      title="Non-H5P assessments in clicker assignment"
    >
      <b-alert :show="true" variant="danger">
        <span class="font-weight-bold font-italic">
          This assignment has non-H5P assessments. Clicker assignments can only be used with H5P true-false
          and H5P multiple choice assessments. Please remove any non-H5P assessments.
        </span>
      </b-alert>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary" @click="$bvModal.hide('modal-non-h5p')">
          OK
        </b-button>
      </template>
    </b-modal>

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
        <PageTitle title="Questions"/>
        <b-alert :show="assessmentType === 'clicker'">
          <span class="font-italic font-weight-bold">
            Important: clicker assignments can only be used in conjunction with H5P true-false and multiple choice assessments.
          </span>
        </b-alert>
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
              <th scope="col">
                Submission
              </th>
              <th scope="col">
                Points
              </th>
              <th scope="col">
                Solution
              </th>
              <th scope="col">
                Actions
              </th>
            </tr>
            </thead>
            <tbody is="draggable" v-model="items" tag="tbody" @end="saveNewOrder">
            <tr v-for="item in items" :key="item.id">
              <td>
                <b-icon icon="list"/>
                {{ item.order }}
              </td>
              <td><a href="" @click.stop.prevent="viewQuestion(item.question_id)">{{ item.title }}</a></td>
              <td>
                {{ item.assignment_id_question_id }}
                <span class="text-info">
                    <font-awesome-icon :icon="copyIcon" @click="doCopy(item.assignment_id_question_id)"/>
                  </span>
              </td>
              <td>
                {{ item.submission }}
              </td>
              <td>{{ item.points }}</td>
              <td><span v-html="item.solution"/></td>
              <td>
                  <span class="pr-1" @click="editQuestionSource(item.mind_touch_url)">
                    <b-tooltip :target="getTooltipTarget('edit',item.question_id)"
                               delay="500"
                    >
                      Edit question source
                    </b-tooltip>
                    <b-icon :id="getTooltipTarget('edit',item.question_id)" icon="pencil"/>
                  </span>
                <span class="pr-1" @click="openRemoveQuestionModal(item.question_id)">
                    <b-tooltip :target="getTooltipTarget('remove',item.question_id)"
                               delay="500"
                    >
                      Remove the question from the assignment
                    </b-tooltip>
                    <b-icon :id="getTooltipTarget('remove',item.question_id)" icon="trash"/>
                  </span>
              </td>
            </tr>
            </tbody>
          </table>
        </div>
        <div v-else>
          <b-alert variant="warning" show>
            <span class="font-weight-bold">This assignment doesn't have any questions.</span>
            <strong/>
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

import RemoveQuestion from '~/components/RemoveQuestion'
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import { viewQuestion, doCopy } from '~/helpers/Questions'

export default {
  middleware: 'auth',
  components: {
    FontAwesomeIcon,
    Loading,
    draggable,
    RemoveQuestion
  },
  data: () => ({
    assessmentType: '',
    adaptId: 0,
    copyIcon: faCopy,
    currentOrderedQuestions: [],
    items: [],
    isLoading: true,
    questionId: 0
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$noty.error('You do not have access to the assignment questions page.')
      return false
    }
    this.getTooltipTarget = getTooltipTarget
    this.viewQuestion = viewQuestion
    this.doCopy = doCopy
    initTooltips(this)
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentInfo()
  },
  methods: {
    async submitRemoveQuestion () {
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${this.questionId}`)
        this.$bvModal.hide('modal-remove-question')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.$noty.info(data.message)
        await this.getAssignmentInfo()
      } catch (error) {
        this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
      }
    },
    openRemoveQuestionModal (questionId) {
      this.questionId = questionId
      this.$bvModal.show('modal-remove-question')
    },
    editQuestionSource (mindTouchUrl) {
      window.open(mindTouchUrl)
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
    async getAssignmentInfo () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/summary`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.assessmentType = data.assessment_type
        this.items = data.rows
        let hasNonH5P
        for (let i = 0; i < this.items.length; i++) {
          if (this.items[i].submission !== 'h5p') {
            hasNonH5P = true
          }
          this.currentOrderedQuestions.push(this.items[i].question_id)
        }
        console.log(data)
        if (this.assessment_type === 'clicker' && hasNonH5P) {
          this.$bvModal.show('modal-non-h5p')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
