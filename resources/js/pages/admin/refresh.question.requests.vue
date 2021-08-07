<template>
  <div>
    <RefreshQuestionOrDenyRequest ref="compareQuestions"
                                  :parent-get-refresh-questions="parentGetRefreshQuestions"
    />
    <div v-if="hasAccess">
      <PageTitle title="Question Refresh Requests"/>
      <div class="vld-parent">
        <loading :active.sync="isLoading"
                 :can-cancel="true"
                 :is-full-page="true"
                 :width="128"
                 :height="128"
                 color="#007BFF"
                 background="#FFFFFF"
        />
        <div>
          <b-tabs content-class="mt-3">
            <b-tab title="Pending" active>
              <div v-if="pending.length">
                <b-table striped hover :fields="fields" :items="pending"
                         class="border border-1 rounded"
                >
                  <template v-slot:cell(title)="data">
                    <a href="" @click.prevent="openCompareQuestionsModal(data.item.question_id,'pending')">
                      {{ data.item.title }}
                    </a>
                  </template>
                  <template v-slot:cell(updated_at)="data">
                    {{ $moment(data.item.updated_at, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') }}
                  </template>
                </b-table>
              </div>
              <div v-else>
                <b-alert variant="info" :show="true">
                  <span class="font-weight-bold">There are no pending question requests.</span>
                </b-alert>
              </div>
            </b-tab>
            <b-tab title="Approved">
              <div v-if="approved.length">
                <b-table striped hover :fields="fields" :items="approved"
                         class="border border-1 rounded"
                >
                  <template v-slot:cell(title)="data">
                    <a href="" @click.prevent="openCompareQuestionsModal(data.item.question_id,'approved')">
                      {{ data.item.title }}</a>
                  </template>
                  <template v-slot:cell(updated_at)="data">
                    {{ $moment(data.item.updated_at, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') }}
                  </template>
                </b-table>
              </div>
              <div v-else>
                <b-alert variant="info" :show="true">
                  <span class="font-weight-bold">There are no approved question requests.</span>
                </b-alert>
              </div>
            </b-tab>
            <b-tab title="Denied">
              <div v-if="denied.length">
                <b-table striped hover :fields="fields" :items="denied"
                         class="border border-1 rounded"
                >
                  <template v-slot:cell(title)="data">
                    <a href="" @click.prevent="openCompareQuestionsModal(data.item.question_id,'denied')">
                      {{ data.item.title }}</a>
                  </template>
                  <template v-slot:cell(updated_at)="data">
                    {{ $moment(data.item.updated_at, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') }}
                  </template>
                </b-table>
              </div>
              <div v-else>
                <b-alert variant="info" :show="true">
                  <span class="font-weight-bold">There are no denied question requests.</span>
                </b-alert>
              </div>
            </b-tab>
          </b-tabs>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import RefreshQuestionOrDenyRequest from '~/components/RefreshQuestionOrDenyRequest'

export default {
  components: {
    RefreshQuestionOrDenyRequest,
    Loading
  },
  data: () => ({
    compareQuestionsKey: 0,
    pendingRefreshQuestionId: 0,
    isLoading: true,
    hasAccess: false,
    pending: [],
    approved: [],
    denied: [],
    fields: [
      {
        key: 'question_id',
        label: 'Question ID'
      },
      {
        key: 'title',
        label: 'Title'
      },
      'instructor',
      'email',
      {
        key: 'updated_at',
        label: 'Last Updated'
      }
    ],
    status: ''
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  mounted () {
    this.hasAccess = this.isMe && (this.user !== null)
    if (!this.hasAccess) {
      this.$noty.error('You do not have access to the question refresh page.')
      return false
    }
    this.parentGetRefreshQuestions()
    this.isLoading = false
  },
  methods: {
    openCompareQuestionsModal (questionId, status) {
      this.pendingRefreshQuestionId = questionId
      this.compareQuestionsKey = questionId
      this.$refs.compareQuestions.getQuestionsToCompare(this.pendingRefreshQuestionId, status)
      this.$bvModal.show('modal-refresh-question-or-deny-request')
    },
    async parentGetRefreshQuestions () {
      try {
        const { data } = await axios.get('/api/refresh-question-requests')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        this.pending = data.refresh_question_requests_by_status.pending
        this.approved = data.refresh_question_requests_by_status.approved
        this.denied = data.refresh_question_requests_by_status.denied
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
