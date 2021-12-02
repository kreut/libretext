<template>
  <b-modal
    id="modal-refresh-question-or-deny-request"
    ref="modal"
    :title="title"
    size="xl"
    :hide-footer="status === 'approved'"
  >
    <b-card v-if="status !== 'approved' && $route.name !== 'questions.view'" header-html="<strong>Nature of the Update</strong>" class="mb-4">
      <b-card-text>
        {{ natureOfUpdate }}
      </b-card-text>
    </b-card>
    <b-card header-html="<strong>As Viewed In ADAPT</strong>" class="mb-4">
      <h4 class="text-primary">
        {{ cachedQuestion.title }}
      </h4>
      <div>
        <div v-if="cachedQuestion.non_technology">
          <iframe
            :key="`non-technology-iframe-${pendingRefreshQuestionId}`"
            v-resize="{ log: false }"
            width="100%"
            :src="cachedQuestion.non_technology_iframe_src"
            frameborder="0"
          />
        </div>
        <div
          v-if="cachedQuestion.technology_iframe && cachedQuestion.technology_iframe.length"
        >
          <iframe
            :key="`technology-iframe-${pendingRefreshQuestionId}`"
            v-resize="{ log: false }"
            width="100%"
            :src="cachedQuestion.technology_iframe"
            frameborder="0"
          />
        </div>
      </div>
    </b-card>

    <b-card v-if="status !== 'approved'" header-html="<strong>As Viewed In Libretext</strong>">
      <div v-if="uncachedQuestionSrc">
        <iframe
          :key="`uncached-question-src-${pendingRefreshQuestionId}`"
          v-resize="{ log: false }"
          width="100%"
          :src="uncachedQuestionSrc"
          frameborder="0"
        />
      </div>
    </b-card>
    <div class="p-2">
      <hr>
    </div>
    <p v-if="status !== 'approved' || (isAdmin && $route.name === 'questions.view')">
      <b-alert variant="warning" :show="true">
        This question has submissions from students over multiple assignments. Because of this, if you choose to accept the
        request to
        refresh the question, student scores will <span class="font-weight-bold">not</span> be updated. In order to
        minimize student
        confusion, the question should only be refreshed for cosmetic reasons.
      </b-alert>
    </p>
    <template #modal-footer>
      <b-button

        size="sm"
        class="float-right"
        @click="$bvModal.hide('modal-refresh-question-or-deny-request')"
      >
        Cancel
      </b-button>
      <b-button
        v-if="status !== 'denied' && isAdmin && $route.name !== 'questions.view'"
        size="sm"
        class="float-right"
        variant="danger"
        @click="denyRequest()"
      >
        Deny Request
      </b-button>
      <b-button
        variant="primary"
        size="sm"
        class="float-right"
        @click="refreshQuestion()"
      >
        <span v-if="!processingQuestionRefresh">Refresh Question</span>
        <span v-if="processingQuestionRefresh"><b-spinner small type="grow" />
          Refreshing...
        </span>
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import axios from 'axios'

export default {
  name: 'RefreshQuestionOrDenyRequest',
  props: {
    parentGetRefreshQuestions: {
      type: Function,
      default: function () {
      }
    }
  },
  data: () => ({
    pendingRefreshQuestionId: 0,
    uncachedQuestionSrc: '',
    cachedQuestion: {},
    natureOfUpdate: '',
    status: '',
    title: '',
    processingQuestionRefresh: false
  }),
  computed: {
    isAdmin: () => window.config.isAdmin
  },
  methods: {
    async refreshQuestion () {
      this.processingQuestionRefresh = true
      try {
        const { data } = await axios.post(`/api/questions/${this.pendingRefreshQuestionId}/refresh`,
          { update_scores: false })
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.processingQuestionRefresh = false
          return false
        }
        this.$noty[data.type](data.message)
        this.getRefreshQuestions()
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.processingQuestionRefresh = false
    },
    getRefreshQuestions () {
      if (this.isAdmin && this.$route.name === 'questions.view') {
        this.$router.go()
      } else {
        this.$bvModal.hide('modal-refresh-question-or-deny-request')
        this.parentGetRefreshQuestions()
      }
    },
    async denyRequest () {
      try {
        const { data } = await axios.post(`/api/refresh-question-requests/deny/${this.pendingRefreshQuestionId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.getRefreshQuestions()
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getQuestionsToCompare (questionId, status) {
      this.pendingRefreshQuestionId = questionId
      this.cachedQuestion = {}
      this.status = status
      switch (this.status) {
        case ('pending'):
          this.title = 'Refresh Question'
          break
        case ('approved'):
          this.title = 'Refreshed Question'
          break
        case ('denied'):
          this.title = 'Refresh Question'
          break
      }

      try {
        const { data } = await axios.get(`/api/questions/compare-cached-and-non-cached/${this.pendingRefreshQuestionId}`)
        console.log(data)
        this.uncachedQuestionSrc = data.uncached_question_src
        this.cachedQuestion = data.cached_question
        this.natureOfUpdate = data.nature_of_update
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
