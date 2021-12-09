<template>
  <div>
    <b-tabs v-show="!isLoading" content-class="mt-3">
      <b-tab title="New Question" :active="$route.params.tab === 'new-question'">
        <b-card header-html="<h2 class=&quot;h7&quot;>Create Question</h2>"
                class="mb-4"
        >
          <b-card-text>
            <CreateQuestion/>
          </b-card-text>
        </b-card>
      </b-tab>
      <b-tab :key="`my-questions-${numClicks}`" title="My Questions" :active="$route.params.tab === 'my-questions'" @click="numClicks++">
        <MyQuestions/>
      </b-tab>
      <b-tab title="Bulk Import">
        <BulkImportQuestions/>
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
  components: {
    CreateQuestion,
    MyQuestions,
    BulkImportQuestions
  },
  data: () => ({
    isLoading: true,
    numClicks: 0
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  mounted () {
    this.hasAccess = (this.user !== null) && (this.isMe || this.isQuestionEditor())
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
