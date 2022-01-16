<template>
  <div>
    <b-tabs v-show="!isLoading" content-class="mt-3">
      <b-tab :key="`new-questions-${numClicksNewQuestions}`"
             title="New Question"
             :active="$route.params.tab === 'new-question'"
             @click="numClicksNewQuestions++"
      >
        <b-card header-html="<h2 class=&quot;h7&quot;>Create Question</h2>"
                class="mb-4"
        >
          <b-card-text>
            <CreateQuestion/>
          </b-card-text>
        </b-card>
      </b-tab>
      <b-tab :key="`my-questions-${numClicksMyQuestions}`"
             title="My Questions"
             :active="$route.params.tab === 'my-questions'"
             @click="numClicksMyQuestions++"
      >
        <MyQuestions :key="`my-questions-${questionId}`"
                     :question-id="questionId"
        />
      </b-tab>
      <b-tab :key="`bulk-import-${numClicksMyQuestions}`"
             title="Bulk Import">
        <BulkImportQuestions :key="`bulk-import-${numClicksMyQuestions}`"/>
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
import CreateQuestion from '~/components/questions/CreateQuestion'
import MyQuestions from '~/components/questions/MyQuestions'
import BulkImportQuestions from '~/components/questions/BulkImportQuestions'
import { mapGetters } from 'vuex'

export default {
  metaInfo () {
    return { title: 'Question Editor' }
  },
  components: {
    CreateQuestion,
    MyQuestions,
    BulkImportQuestions
  },
  data: () => ({
    numClicksNewQuestions: 0,
    isLoading: true,
    numClicksMyQuestions: 0,
    questionId: 0
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  mounted () {
    if (this.$route.params.questionId) {
      this.questionId = parseInt(this.$route.params.questionId)
    }
    this.hasAccess = (this.user !== null) && (this.user.role === 2 || this.isQuestionEditor())
    if (!this.hasAccess) {
      this.$noty.error('You do not have access to this page.')
    } else {
      this.isLoading = false
    }
  },
  methods: {
    isQuestionEditor () {
      return this.user.role === 5
    }
  }
}
</script>

<style scoped>

</style>
