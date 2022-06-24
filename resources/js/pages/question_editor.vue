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
            <CreateQuestion :modal-id="'question_editor-question-to-view-questions-editor'"/>
          </b-card-text>
        </b-card>
      </b-tab>
      <b-tab
        :key="`my-questions-${numClicksMyQuestions}`"
        title="My Questions"
        @click="numClicksMyQuestions++;resetCheckboxes"
      >
        <QuestionsGet :parent-question-source="'my_questions'"/>
      </b-tab>
      <b-tab
        :key="`my-favorites-${numClicksMyQuestions}`"
        title="My Favorites"
        @click="numClicksMyQuestions++;resetCheckboxes"
      >
        <QuestionsGet :parent-question-source="'my_favorites'"/>
      </b-tab>
      <b-tab v-if="user.role === 2"
             :key="`bulk-import-${numClicksMyQuestions}`"
             title="Bulk Import"
      >
        <BulkImportQuestions :key="`bulk-import-${numClicksMyQuestions}`"/>
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
import CreateQuestion from '~/components/questions/CreateQuestion'
import BulkImportQuestions from '~/components/questions/BulkImportQuestions'
import QuestionsGet from '~/components/questions/QuestionsGet'
import { mapGetters } from 'vuex'

export default {
  metaInfo () {
    return { title: 'Question Editor' }
  },
  components: {
    CreateQuestion,
    QuestionsGet,
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
    },
    resetCheckboxes () {
      let checkboxes = document.querySelectorAll('input:checked')
      console.log(checkboxes)
      for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = false
      }
    }
  }
}
</script>

<style scoped>

</style>
